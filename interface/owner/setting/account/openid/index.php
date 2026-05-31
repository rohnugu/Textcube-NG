<?php
/// Copyright (c) 2004-2016, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)
///
/// ---- Modification Notice ----
/// OpenID 2.0 (EOL) 제거 → OIDC 전용. 계정에 OIDC 식별자를 연결(add)/해제(del)한다.
/// 연결은 이미 id_token 으로 검증된 현재 OIDC 식별자(Acl 'openid')만 사용하므로 Yadis fetch/재인증이 없다.
define('OPENID_REGISTERS', 10); /* check also ../index.php */

$IV = array(
	'GET' => array(
		'openid_identifier' => array('string', 'default'=>''),
		'mode' => array('string')
	)
);

require ROOT . '/library/preprocessor.php';
require_once ROOT . '/framework/legacy/Textcube.Control.OIDC.php';

global $openid_list;
$openid_list = array();
for( $i=0; $i<OPENID_REGISTERS; $i++ )
{
	$openid = Setting::getUserSetting( "openid." . $i ,null,true);
	if( !empty($openid) ) {
		array_push( $openid_list, $openid );
	}
}

function exitWithError($msg)
{
	$context = Model_Context::getInstance();
	$safeMsg      = json_encode($msg);
	$safeRedirect = json_encode($context->getProperty('uri.blog') . '/owner/setting/account');
	echo "<html><head><script type=\"text/javascript\">//<![CDATA[".CRLF
		."alert($safeMsg); document.location.href=$safeRedirect; //]]></script></head></html>";
	exit;
}

/// OIDC 식별자 연결 — 현재 로그인된 OIDC 신원(Acl 'openid')을 본 계정(UserSettings openid.*)에 연결한다.
/// id_token 으로 이미 서명·iss·aud 검증된 식별자라 추가 인증이 불필요하다. 미로그인 시 OIDC 로그인으로 유도한다.
/// 연결은 owner 세션(requireOwnership)에서만 일어나고 식별자는 항상 현재 세션의 본인 OIDC 이므로 타인 식별자를 주입할 수 없다.
function addOIDCIdentity()
{
	global $openid_list;
	$context = Model_Context::getInstance();
	$currentOpenID = Acl::getIdentity( 'openid' );

	if( empty($currentOpenID) || strpos($currentOpenID, 'oidc:') !== 0 ) {
		$return = $context->getProperty('uri.blog') . '/owner/setting/account/openid?mode=add';
		header( "Location: " . $context->getProperty('uri.blog') . "/login/openid?action=try_auth&requestURI=" . urlencode($return) );
		exit(0);
	}

	if( in_array($currentOpenID, $openid_list) ) {
		exitWithError( _t('이미 연결된 오픈아이디 입니다') . " : " . OpenID::getDisplayName($currentOpenID) );
	}

	for( $i=0; $i<OPENID_REGISTERS; $i++ )
	{
		$openid = Setting::getUserSetting( "openid." . $i, null, true );
		if( empty($openid) ) {
			Setting::setUserSetting( "openid." . $i, $currentOpenID, true );
			break;
		}
	}

	$safeMsg      = json_encode(_t('연결하였습니다.') . ' : ' . OpenID::getDisplayName($currentOpenID));
	$safeRedirect = json_encode($context->getProperty('uri.blog') . '/owner/setting/account');
	echo "<html><head><script type=\"text/javascript\">//<![CDATA[".CRLF
		."alert($safeMsg); document.location.href=$safeRedirect; //]]></script></head></html>";
}

function deleteOpenID($openidForDel)
{
	$context = Model_Context::getInstance();
	for( $i=0; $i<OPENID_REGISTERS; $i++ )
	{
		$openid = Setting::getUserSetting( "openid." . $i , null, true);
		if( $openid == $openidForDel ) {
			Setting::removeUserSetting( "openid." . $i, true);
			break;
		}
	}

	$safeMsg      = json_encode(_t('삭제되었습니다.'));
	$safeRedirect = json_encode($context->getProperty('uri.blog') . '/owner/setting/account');
	echo "<html><head><script type=\"text/javascript\">//<![CDATA[".CRLF
		."alert($safeMsg); document.location.href=$safeRedirect; //]]></script></head></html>";
}

switch( $_GET['mode'] ) {
	case 'del':
		deleteOpenID($_GET['openid_identifier']);
		break;
	case 'add':
	default:
		addOIDCIdentity();
		break;
}
?>
