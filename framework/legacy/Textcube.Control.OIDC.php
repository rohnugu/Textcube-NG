<?php
/// OpenID Connect (OIDC) client — replaces legacy OpenID 2.0 (EOL: providers dropped 2.0 support ~2015).
/// Dependency-free: uses only openssl + JSON + curl/streams (no composer / external bundle).
///
/// SECURITY — double opt-in (disabled by default):
///   gate 1: CL_OpenID plugin must be ACTIVE  (getCurrentSetting() is only valid while active)
///   gate 2: plugin config must set oidc_enabled='y' AND provide issuer/client_id/client_secret
/// Neither activating the plugin alone, nor leaving config blank, enables OIDC.
///
/// Stage 1: config gate. Stage 2: Authorization Code flow + RS256 id_token verification
///   (state, nonce, PKCE S256, JWKS signature, iss/aud/exp/nonce checks).
/// Stage 3 (this file): guest comment identity mapping — claims → Acl 'openid' identity,
///   reusing the legacy single source of truth so the whole guest-comment flow works unchanged.

class OIDCClient {

	static $error = null;

	// ---------- Stage 1: double opt-in gate ----------

	static function config() {
		if (!class_exists('Setting') || !method_exists('Setting', 'fetchConfigVal')) return array();
		if (!function_exists('getCurrentSetting')) return array();
		$raw = getCurrentSetting('CL_OpenID');
		if (empty($raw)) return array();
		$cfg = Setting::fetchConfigVal($raw);
		return is_array($cfg) ? $cfg : array();
	}

	static function isEnabled() {
		$cfg = self::config();
		if (!isset($cfg['oidc_enabled']) || $cfg['oidc_enabled'] !== 'y') return false;
		foreach (array('oidc_issuer', 'oidc_client_id', 'oidc_client_secret') as $k) {
			if (empty($cfg[$k])) return false;
		}
		return true;
	}

	static function setting($key, $default = null) {
		$cfg = self::config();
		return isset($cfg[$key]) && $cfg[$key] !== '' ? $cfg[$key] : $default;
	}

	static function redirectUri() {
		$r = self::setting('oidc_redirect_uri');
		if (!empty($r)) return $r;
		$ctx = Model_Context::getInstance();
		return $ctx->getProperty('uri.host') . $ctx->getProperty('uri.blog') . '/login/openid/callback';
	}

	// ---------- Stage 2: Authorization Code flow ----------

	/// GET {issuer}/.well-known/openid-configuration → endpoints array (cached per request).
	static function discover() {
		static $cache = null;
		if ($cache !== null) return $cache;
		$issuer = self::setting('oidc_issuer');
		if (empty($issuer)) return ($cache = false);
		$json = self::httpGet(rtrim($issuer, '/') . '/.well-known/openid-configuration');
		$d = json_decode((string)$json, true);
		if (!is_array($d) || empty($d['authorization_endpoint']) || empty($d['token_endpoint']) || empty($d['jwks_uri'])) {
			return ($cache = false);
		}
		return ($cache = $d);
	}

	/// Build the authorization redirect URL; stashes state/nonce/PKCE-verifier in the session.
	static function buildAuthUrl($extra = array()) {
		$d = self::discover();
		if (!$d) return self::fail('discovery');
		$state    = self::randTok();
		$nonce    = self::randTok();
		$verifier = self::b64urlEncode(random_bytes(32));
		$challenge = self::b64urlEncode(hash('sha256', $verifier, true));
		$_SESSION['oidc_state']    = $state;
		$_SESSION['oidc_nonce']    = $nonce;
		$_SESSION['oidc_verifier'] = $verifier;
		$params = array(
			'response_type'         => 'code',
			'client_id'             => self::setting('oidc_client_id'),
			'redirect_uri'          => self::redirectUri(),
			'scope'                 => 'openid email profile',
			'state'                 => $state,
			'nonce'                 => $nonce,
			'code_challenge'        => $challenge,
			'code_challenge_method' => 'S256',
		) + $extra;
		return $d['authorization_endpoint'] . '?' . http_build_query($params);
	}

	/// Exchange code → tokens → verify id_token. Returns claims [sub,email,name] or false (self::$error set).
	static function handleCallback($code, $state) {
		if (empty($code)) return self::fail('no_code');
		if (empty($_SESSION['oidc_state']) || !hash_equals((string)$_SESSION['oidc_state'], (string)$state)) {
			return self::fail('state_mismatch');
		}
		$d = self::discover();
		if (!$d) return self::fail('discovery');
		$resp = self::httpPost($d['token_endpoint'], array(
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => self::redirectUri(),
			'client_id'     => self::setting('oidc_client_id'),
			'client_secret' => self::setting('oidc_client_secret'),
			'code_verifier' => isset($_SESSION['oidc_verifier']) ? $_SESSION['oidc_verifier'] : '',
		));
		$tok = json_decode((string)$resp, true);
		if (!is_array($tok) || empty($tok['id_token'])) return self::fail('token_exchange');
		return self::verifyIdToken($tok['id_token'], $d, isset($_SESSION['oidc_nonce']) ? $_SESSION['oidc_nonce'] : null);
	}

	/// Verify an id_token JWT (RS256 only). $expectedNonce: session nonce. Returns claims or false.
	static function verifyIdToken($jwt, $disco, $expectedNonce) {
		$parts = explode('.', (string)$jwt);
		if (count($parts) !== 3) return self::fail('jwt_format');
		$header  = json_decode(self::b64urlDecode($parts[0]), true);
		$payload = json_decode(self::b64urlDecode($parts[1]), true);
		$sig     = self::b64urlDecode($parts[2]);
		if (!is_array($header) || !is_array($payload)) return self::fail('jwt_decode');
		if (!isset($header['alg']) || $header['alg'] !== 'RS256') return self::fail('alg');   // RS256 only — reject 'none'/HS256

		$jwks = json_decode((string)self::httpGet($disco['jwks_uri']), true);
		$pem = self::jwkToPem($jwks, isset($header['kid']) ? $header['kid'] : null);
		if (!$pem) return self::fail('no_jwk');

		$ok = openssl_verify($parts[0] . '.' . $parts[1], $sig, $pem, OPENSSL_ALGO_SHA256);
		if ($ok !== 1) return self::fail('bad_signature');

		if (!isset($payload['iss']) || $payload['iss'] !== self::setting('oidc_issuer')) return self::fail('iss');
		$aud = isset($payload['aud']) ? $payload['aud'] : '';
		$aud = is_array($aud) ? $aud : array($aud);
		if (!in_array(self::setting('oidc_client_id'), $aud, true)) return self::fail('aud');
		if (!isset($payload['exp']) || (int)$payload['exp'] < time()) return self::fail('expired');
		if ($expectedNonce !== null) {
			if (!isset($payload['nonce']) || !hash_equals((string)$expectedNonce, (string)$payload['nonce'])) return self::fail('nonce');
		}

		return array(
			'sub'   => isset($payload['sub']) ? $payload['sub'] : '',
			'email' => isset($payload['email']) ? $payload['email'] : '',
			'name'  => isset($payload['name']) ? $payload['name'] : (isset($payload['preferred_username']) ? $payload['preferred_username'] : ''),
		);
	}

	// ---------- Stage 3: guest comment identity ----------

	/// OIDC claims → 게스트 댓글 작성자 신원. legacy OpenIDConsumer::setAcl() 의 게스트 경로를 OIDC로 적응.
	/// Acl 'openid' identity 하나가 blog.comment(저장/조회/삭제)·view·comment/add 의 단일 출처이므로,
	/// 식별자만 주입하면 기존 게스트 댓글 흐름 전체가 그대로 동작한다(다운스트림 무수정).
	/// 식별자 = OIDC subject 'oidc:{iss}#{sub}' (provider별 불투명·안정·고유). 표시명은 name/email.
	/// 사용자 계정 매핑(관리자 로그인)은 단계 4에서 이 메서드를 확장한다.
	static function establishGuestIdentity($claims) {
		if (!is_array($claims) || empty($claims['sub'])) return self::fail('no_subject');
		$identifier = self::subjectId($claims);
		Acl::authorize('openid', $identifier);
		$name = !empty($claims['name']) ? $claims['name'] : (!empty($claims['email']) ? $claims['email'] : $identifier);
		// 표시명을 세션에 직접 기록(legacy setUserInfo 와 동일 구조). OpenIDConsumer 의 인스턴스 메서드를
		// 정적 호출하지 않는다 — PHP 8 에서 non-static 메서드의 정적 호출은 치명적 오류이기 때문.
		if (!isset($_SESSION['openid']) || !is_array($_SESSION['openid'])) $_SESSION['openid'] = array();
		$_SESSION['openid']['nickname'] = $name;
		$_SESSION['openid']['homepage'] = '';
		if (class_exists('Session') && method_exists('Session', 'authorize') && defined('SESSION_OPENID_USERID')) {
			$ctx = Model_Context::getInstance();
			Session::authorize(intval($ctx->getProperty('blog.id')), SESSION_OPENID_USERID);
		}
		return $identifier;
	}

	/// 안정·고유 게스트 식별자. legacy openid URL 과 구분되도록 'oidc:' 접두(ViewCommenter 가 링크 생략에 사용).
	static function subjectId($claims) {
		return 'oidc:' . self::setting('oidc_issuer') . '#' . $claims['sub'];
	}

	// ---------- Stage 4: user/admin login mapping ----------

	/// 게스트 신원 설정 + 연결된 사용자 계정으로의 승격. legacy OpenIDConsumer::setAcl() 의 사용자 경로를 OIDC로 적응.
	/// 사용자가 owner/setting/account 에서 자신의 계정에 OIDC 식별자를 명시적으로 연결(UserSettings openid.*)한 경우에만
	/// 해당 계정으로 로그인된다. 자동 계정생성은 하지 않는다(계정탈취 방지).
	/// 식별자는 sub 기반(iss#sub)이라 email 공유/변경·provider 사칭으로는 매핑을 가로챌 수 없다.
	static function establishIdentity($claims) {
		$identifier = self::establishGuestIdentity($claims);   // Acl 'openid' + 표시명 + 게스트 세션
		if ($identifier === false) return false;
		$userid = self::mapToUser($identifier);
		if ($userid === null) return $identifier;              // 연결된 계정 없음 → 게스트 유지
		Acl::authorize('textcube', $userid);
		$ctx = Model_Context::getInstance();
		$blogid = intval($ctx->getProperty('blog.id'));
		// writers 권한이 있는 계정에 한해 실제 사용자 세션으로 승격(legacy setAcl 과 동일 판정).
		if (method_exists('Acl', 'getCurrentPrivilege') && in_array('group.writers', Acl::getCurrentPrivilege())) {
			Session::authorize($blogid, $userid);
		}
		return $identifier;
	}

	/// UserSettings 의 openid.* 항목 중 값이 식별자와 정확히 일치하는 사용자 id(없으면 null).
	/// escape=true 를 명시해 식별자(iss·sub)에 의한 SQL 인젝션을 방어한다.
	static function mapToUser($identifier) {
		if (!class_exists('DBModel')) return null;
		$pool = DBModel::getInstance();
		$pool->reset('UserSettings');
		$pool->setQualifier('name', 'like', 'openid.', true);
		$pool->setQualifier('value', 'equals', $identifier, true);
		$pool->setOrder('userid', 'ASC');
		$result = $pool->getCell('userid');
		return !empty($result) ? $result : null;
	}

	// ---------- helpers ----------

	/// RSA JWK (n,e) → PEM SubjectPublicKeyInfo via minimal ASN.1 DER. Picks key by kid (or first RSA key).
	static function jwkToPem($jwks, $kid) {
		if (!is_array($jwks) || empty($jwks['keys'])) return null;
		$jwk = null;
		foreach ($jwks['keys'] as $k) {
			if (!isset($k['kty']) || $k['kty'] !== 'RSA') continue;
			if ($kid !== null && isset($k['kid']) && $k['kid'] !== $kid) continue;
			$jwk = $k;
			if ($kid === null || (isset($k['kid']) && $k['kid'] === $kid)) break;
		}
		if (!$jwk || empty($jwk['n']) || empty($jwk['e'])) return null;
		$n = self::b64urlDecode($jwk['n']);
		$e = self::b64urlDecode($jwk['e']);
		$rsaPubKey = self::asn1Seq(self::asn1Int($n) . self::asn1Int($e));
		$bitString = "\x03" . self::asn1Len(strlen($rsaPubKey) + 1) . "\x00" . $rsaPubKey;
		$algId = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00"; // rsaEncryption OID + NULL
		$spki = self::asn1Seq($algId . $bitString);
		return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
	}

	static function asn1Len($len) {
		if ($len < 0x80) return chr($len);
		$bytes = '';
		while ($len > 0) { $bytes = chr($len & 0xff) . $bytes; $len >>= 8; }
		return chr(0x80 | strlen($bytes)) . $bytes;
	}
	static function asn1Int($bytes) {
		if (strlen($bytes) === 0) $bytes = "\x00";
		if (ord($bytes[0]) & 0x80) $bytes = "\x00" . $bytes; // force positive
		return "\x02" . self::asn1Len(strlen($bytes)) . $bytes;
	}
	static function asn1Seq($content) {
		return "\x30" . self::asn1Len(strlen($content)) . $content;
	}

	static function b64urlDecode($s) {
		$s = strtr((string)$s, '-_', '+/');
		$pad = strlen($s) % 4;
		if ($pad) $s .= str_repeat('=', 4 - $pad);
		return base64_decode($s);
	}
	static function b64urlEncode($s) {
		return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
	}
	static function randTok() {
		return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
	}

	static function httpGet($url) {
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
				CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_FOLLOWLOCATION => false,
			));
			$r = curl_exec($ch); curl_close($ch);
			return $r;
		}
		return @file_get_contents($url);
	}
	static function httpPost($url, $params) {
		$body = http_build_query($params);
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
				CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body,
				CURLOPT_HTTPHEADER => array('Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'),
			));
			$r = curl_exec($ch); curl_close($ch);
			return $r;
		}
		$opts = array('http' => array(
			'method'  => 'POST',
			'header'  => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
			'content' => $body, 'timeout' => 10,
		));
		return @file_get_contents($url, false, stream_context_create($opts));
	}

	static function fail($code) { self::$error = $code; return false; }
}
?>
