<?php
/// Copyright (c) 2004-2016, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)
///
/// ---- Modification Notice (GPL §2(a)) ----
/// Modified 2026 by @deokio for PHP 8.5 compatibility,
/// performed with AI assistance (Anthropic Claude) under human review.
/// Changes consist primarily of mechanical PHP migration transformations
/// per the official PHP upgrade documentation.
/// No additional copyright is asserted over these modifications.
/// See CHANGELOG.md and SECURITY.md for full modification history.

function login($loginid, $password, $preKnownPassword = null) {
	$ctx = Model_Context::getInstance();
	$loginid = POD::escapeString($loginid);
	$blogid = getBlogId();
	$userid = Auth::authenticate($blogid , $loginid, $password );

	if( $userid === false ) {
		return false;
	}

	if (empty($_POST['save'])) {
		setcookie('TSSESSION_LOGINID', '', array('expires' => time() - 31536000, 'path' => $ctx->getProperty('service.path') . '/', 'domain' => $ctx->getProperty('service.domain'), 'secure' => (bool)$ctx->getProperty('service.useSSL', false), 'httponly' => true, 'samesite' => 'Lax'));
	} else {
		setcookie('TSSESSION_LOGINID', $loginid, array('expires' => time() + 31536000, 'path' => $ctx->getProperty('service.path') . '/', 'domain' => $ctx->getProperty('service.domain'), 'secure' => (bool)$ctx->getProperty('service.useSSL', false), 'httponly' => true, 'samesite' => 'Lax'));
	}

	if( in_array( "group.writers", Acl::getCurrentPrivilege() ) ) {
		Session::authorize($blogid, $userid);
	}
	return true;
}

function logout() {
	fireEvent("Logout");
	Acl::clearAcl();
	Transaction::clear();
	session_destroy();
}

function requireLogin() {
	global $service, $hostURL, $blogURL;
	if(isset($_POST['refererURI'])) $_GET['refererURI'] = $_POST['refererURI'];
	else if(isset($_SESSION['refererURI'])) {
		$_GET['refererURI'] = $_SESSION['refererURI'];
		unset($_SESSION['refererURI']);
	}
	if (!empty($service['loginURL'])) {
		header("Location: {$service['loginURL']}?requestURI=" . rawurlencode("{$hostURL}{$_SERVER['REQUEST_URI']}") . (isset($_GET['refererURI']) && !empty($_GET['refererURI']) ? "&refererURI=". rawurlencode($_GET['refererURI']) : ''));
	} else {
		$requestURI = rawurlencode("{$hostURL}{$_SERVER['REQUEST_URI']}") .  (isset($_GET['refererURI']) && !empty($_GET['refererURI']) ? "&refererURI=". rawurlencode($_GET['refererURI']) : '');

		header ("Location: $hostURL$blogURL/login?requestURI=" . $requestURI );
	}
	exit;
}

function doesHaveMembership() {
	return Acl::getIdentity('textcube') !== null;
}

function requireMembership() {
	global $hostURL;
	if( doesHaveMembership() ) return true;
	$_SESSION['refererURI'] = $hostURL.$_SERVER['REQUEST_URI'];
	requireLogin();
}

function getUserId() {
	return intval(Acl::getIdentity('textcube'));
}

/*
function getBlogId() {
	global $blogid;
	return intval($blogid);
}*/

function setBlogId($id) {
	global $blogid;
	$blogid = $id;
}

function doesHaveOwnership($extra_aco=null) {
	return Acl::check( array("group.administrators","group.writers"), $extra_aco);
}

function requireOwnership() {
	if (doesHaveOwnership())
		return true;
	requireLogin();
	return false;
}

/// CSRF 강화 (본 포트 개선, upstream 미존재): 기존 host-only Referer 검증은 단일 host
/// path-모드 멀티블로그의 cross-blog CSRF를 막지 못함. host 동일 시 블로그 스코프까지 검증.
/// single/domain 모드는 기존 동작 유지(회귀 0), path 모드만 blogname 비교(추출 실패 시 fail-open).
/// 회귀+강화 검증: _sectest/csrf_strictroute_test.php (18 케이스 ALL PASS).
function __referentBlogScopeStatus($referer, $hostHeader, $serviceType, $basePath, $blogName) {
	if (!is_string($referer) || $referer === '') return 'block';
	$url = parse_url($referer);
	if ($url === false || empty($url['host'])) return 'block';
	$refHost = strtolower($url['host']);
	$srvHost = strtolower(explode(':', (string)$hostHeader)[0]);
	if ($refHost !== $srvHost) return 'block';
	if ($serviceType !== 'path') return 'pass';
	$refPath = isset($url['path']) ? $url['path'] : '';
	if ($basePath !== '' && strpos($refPath, $basePath) === 0)
		$refPath = substr($refPath, strlen($basePath));
	$seg = explode('/', ltrim($refPath, '/'));
	$refBlog = isset($seg[0]) ? urldecode($seg[0]) : '';
	if ($refBlog === '') return 'failopen';
	return ($refBlog === (string)$blogName) ? 'pass' : 'block';
}

function requireStrictRoute() {
	$context = Model_Context::getInstance();
	$__scope = __referentBlogScopeStatus(
			isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
			isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
			$context->getProperty('service.type'),
			(string)$context->getProperty('service.path'),
			(string)$context->getProperty('blog.name'));
	if ($__scope === 'failopen')
		trigger_error('requireStrictRoute: path-mode blogname unresolved, fail-open allowed (referer=' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') . ')', E_USER_NOTICE);
	if ($__scope !== 'block')
		return;
	header('HTTP/1.1 412 Precondition Failed');
	header('Content-Type: text/html');
	header("Connection: close");
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php echo _t('Precondition Failed');?></title>
</head>
<body>
	<h1><?php echo _t('Precondition Failed');?></h1>
</body>
</html>
<?php
	exit;
}

function requireStrictBlogURL() {
	$context = Model_Context::getInstance();
	if($context->getProperty('uri.isStrictBlogURL') == true) return;
	header('HTTP/1.1 404 Not found');
	exit;
}

function requirePrivilege($AC) {
	if(Acl::check($AC)) return true;
	else header('HTTP/1.1 404 Not found');
	exit;
}

function validateAPIKey($blogid, $loginid, $key) {
	global $service;
	$loginid = POD::escapeString($loginid);
	$key = POD::escapeString($key);
	$userid = User::getUserIdByEmail($loginid);
	if( $userid === false ) { return false; }
	$currentAPIKey = Setting::getUserSettingGlobal('APIKey',null,$userid);
	if($currentAPIKey == null) {
		if(!User::confirmPassword($userid, $key)) {
			header('HTTP/1.1 403 Forbidden');
			exit;
		}
	} else if($currentAPIKey != $key) {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
	return true;
}

function isLoginId($blogid, $loginid) {
	global $database;
	// Prepared statement: blogid(i) loginid(s) — SQL Injection 대응 (v1.86)
	$stmt = POD::prepare("SELECT u.userid
			FROM {$database['prefix']}Users u,
				{$database['prefix']}Privileges t
			WHERE t.blogid = ?
				AND u.loginid = ?
				AND t.userid = u.userid");
	if (!$stmt) return false;
	POD::bindAndExecute($stmt, 'is', (int)$blogid, (string)$loginid);
	$rows = POD::fetchAllStmt($stmt);
	$stmt->close();
	return (count($rows) === 1);
}

function generatePassword() {
	return strtolower(substr(base64_encode(random_int(0x10000000, 0x70000000)), 3, 8));
}

function resetPassword($blogid, $loginid) {
	global $service, $blog, $hostURL, $blogURL, $serviceURL;
	if (!isLoginId($blogid, $loginid))
		return false;
	$userid = User::getUserIdByEmail($loginid);
	$password = POD::queryCell("SELECT password FROM {$database['prefix']}Users WHERE userid = $userid",'password',false);
	$authtoken = md5(generatePassword());

	$query = DBModel::getInstance();
	$query->reset('UserSettings');
	$query->setAttribute('userid',$userid);
	$query->setAttribute('name','Authtoken',true);
	$query->setAttribute('value',$authtoken,true);
	$query->setQualifier('userid',$userid);
	$query->setQualifier('name','Authtoken',true);
	$query->replace();

	if(empty($result)) {
		return false;
	}
	//$headers = "From: Your Textcube Blog <textcube@{$service['domain']}>\n" . 'X-Mailer: ' . TEXTCUBE_NAME . "\n" . "MIME-Version: 1.0\nContent-Type: text/html; charset=utf-8\n";
	$message = file_get_contents(ROOT . "/resources/style/letter/letter.html");
	$message = str_replace('[##_title_##]', _text('텍스트큐브 블로그 로그인 정보'), $message);
	$message = str_replace('[##_content_##]', _text('블로그 로그인을 위한 임시 암호가 생성 되었습니다. 이 이메일에 로그인할 수 있는 인증 정보가 포함되어 있습니다.'), $message);
	$message = str_replace('[##_images_##]', $serviceURL."/resources/style/letter", $message);
	$message = str_replace('[##_link_##]', "$hostURL$blogURL/login?loginid=" . rawurlencode($loginid) . '&password=' . rawurlencode($authtoken) . '&requestURI=' . rawurlencode("$hostURL$blogURL/owner/setting/account?password=" . rawurlencode($password)), $message);
	$message = str_replace('[##_link_title_##]', _text('여기를 클릭하시면 로그인하여 암호를 변경하실 수 있습니다.'), $message);
	$message = str_replace('[##_sender_##]', '', $message);
	$ret = sendEmail('Your Textcube Blog',"textcube@{$service['domain']}",'',$loginid, encodeMail(_text('블로그 로그인 암호가 초기화되었습니다.')), $message );
	if (true !== $ret) {
		return false;
	}
	return true;
}
?>
