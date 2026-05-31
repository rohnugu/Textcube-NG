<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)
///
/// ---- Modification Notice ----
/// OpenID 2.0 (protocol EOL — 주요 Provider 가 2015 년경 지원 종료) 제거.
/// 인증은 OpenID Connect(OIDC, Textcube.Control.OIDC.php)로 일원화한다.
/// phpopenid(library/contrib/phpopenid) 의존과 Yadis/Consumer/Transaction 기반 2.0 인증 흐름
///   (fetch/tryAuth/finishAuth/setAcl/update/setDelegate/setComment 등)을 모두 제거하고,
///   OIDC 흐름과 댓글 표시가 공유하는 경량 헬퍼만 남긴다.
/// 모든 헬퍼를 static 으로 통일한다 — 기존 코드는 non-static 메서드를 정적(::)으로 호출했고,
///   이는 PHP 8 에서 치명적 오류이므로 호출 형태와 정의를 일치시킨다.

class OpenID {
	public static function setCookie( $key, $value )
	{
		$context = Model_Context::getInstance();
		$session_cookie_path = "/";
		$savedPath = $context->getProperty('service.session_cookie_path');
		if( !empty($savedPath)) {
			$session_cookie_path = $context->getProperty('service.session_cookie_path');
		}
		if( !headers_sent() ) {
			setcookie( $key, $value, array('expires' => time()+3600*24*30, 'path' => $session_cookie_path, 'secure' => (bool)$context->getProperty('service.useSSL', false), 'httponly' => true, 'samesite' => 'Lax'));
		}
	}

	public static function clearCookie( $key )
	{
		$context = Model_Context::getInstance();
		$session_cookie_path = "/";
		$savedPath = $context->getProperty('service.session_cookie_path');
		if( !empty($savedPath)) {
			$session_cookie_path = $context->getProperty('service.session_cookie_path');
		}
		if( !headers_sent() ) {
			setcookie( $key, '', array('expires' => time()-3600, 'path' => $session_cookie_path, 'secure' => (bool)$context->getProperty('service.useSSL', false), 'httponly' => true, 'samesite' => 'Lax'));
		}
	}

	public static function getDisplayName( $openid )
	{
		// OIDC 신원(oidc:{iss}#{sub})은 issuer 만 표시한다(식별자 원문 노출 방지).
		if( strpos($openid, 'oidc:') === 0 ) {
			$issuer = substr($openid, 5);
			$hash = strpos($issuer, '#');
			if( $hash !== false ) { $issuer = substr($issuer, 0, $hash); }
			if( strlen($issuer) > 40 ) { $issuer = substr($issuer, 0, 36) . "..."; }
			return $issuer;
		}
		$s = explode( '#', $openid );
		$openid = $s[0];
		if( strlen($openid) > 40 ) {
			$openid = substr($openid,0,36) . "...";
		}
		return $openid;
	}
}

/// 과거 OpenID 2.0 컨슈머. 2.0 프로토콜 구현은 제거되었고, OIDC 흐름/댓글 처리와 공유하는
/// 정적 헬퍼(세션 표시정보·로그아웃·에러 출력·리다이렉트)만 유지한다. OpenID 헬퍼를 상속한다.
class OpenIDConsumer extends OpenID {

	public static function logout()
	{
		Acl::authorize('openid', null );
		OpenID::setCookie( 'openid_auto', 'n' );
		self::clearUserInfo();
	}

	public static function clearUserInfo()
	{
		unset( $_SESSION['openid'] );
	}

	public static function setUserInfo( $nickname, $homepage )
	{
		if( !isset( $_SESSION['openid'] ) || !is_array( $_SESSION['openid'] ) ) {
			$_SESSION['openid'] = array();
		}
		$_SESSION['openid']['nickname'] = $nickname;
		$_SESSION['openid']['homepage'] = $homepage;
	}

	/// 게스트 댓글 작성자의 표시정보 갱신. 세션을 갱신하고, 과거 OpenID 2.0 사용자(OpenIDUsers)
	/// 레코드가 있을 때만 그 표시정보를 동기화한다. OIDC 식별자는 OpenIDUsers 레코드가 없어 no-op.
	public static function updateUserInfo( $nickname, $homepage )
	{
		$openid = Acl::getIdentity( 'openid' );
		if( empty($openid) ) {
			return false;
		}
		self::setUserInfo( $nickname, $homepage );

		if( !class_exists('DBModel') ) {
			return true;
		}
		$pool = DBModel::getInstance();
		$pool->reset('OpenIDUsers');
		$pool->setQualifier('openid','equals',$openid,true);
		$result = $pool->getCell('openidinfo');
		if( empty($result) ) {
			return true;   // 연결된 OpenIDUsers 레코드 없음(OIDC 게스트 등) → no-op
		}
		$data = unserialize( $result );
		if( !is_array($data) ) {
			$data = array();
		}
		if( !empty($nickname) ) $data['nickname'] = $nickname;
		if( !empty($homepage) ) $data['homepage'] = $homepage;

		$pool->reset('OpenIDUsers');
		$pool->setAttribute('openidinfo',serialize($data),true);
		$pool->setQualifier('openid','equals',$openid,true);
		$pool->update();
		return true;
	}

	public static function printErrorReturn( $msg, $location )
	{
		header("HTTP/1.0 200 OK");
		header("Content-type: text/html; charset=utf-8");
		$safeMsg      = json_encode($msg);
		$safeLocation = json_encode($location);
		print "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /></head><body><script type=\"text/javascript\">//<![CDATA[" . CRLF . "alert($safeMsg);";
		if( $location ) {
			print "document.location.href=$safeLocation;";
		}
		print "//]]>" . CRLF . "</script></body></html>";
		exit(0);
	}

	public static function redirect( $location )
	{
		header("HTTP/1.0 302 Moved Temporarily");
		header("Location: $location");
		print( "<html><body></body></html>" );
		exit(0);
	}
}
?>
