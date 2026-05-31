<?php
/// Copyright (c) 2004-2016, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)

if (!defined('ROOT')) {
	header('HTTP/1.1 403 Forbidden');
	header("Connection: close");
	exit;
}

if( !defined( 'OPENID_REGISTERS' ) ) {
	define('OPENID_REGISTERS', 10);
}

global $hostURL, $service;

function openid_Logout($target)
{
	OpenIDConsumer::logout();
	return $target;
}

/// OpenID 2.0 (EOL) 제거에 따라 자동로그인(openid_hardcore_login)과
/// 위임 head 출력(openid_add_delegate)은 삭제되었다. 인증은 OIDC(login/openid)로 일원화된다.

function openid_ViewCommenter($name, $comment)
{
	$context = Model_Context::getInstance();

	if( $comment['secret'] ) {
		return $name;
	}
	if( empty($comment['openid']) ) {
		return $name;
	}

	// OIDC(OpenID Connect) 신원: 식별자가 URL 이 아니므로(oidc:{iss}#{sub}) 하이퍼링크 대신
	// title 만 부여한다(깨진 href·href 인젝션 방지). 표시명은 댓글 작성자명을 그대로 사용.
	if( strpos($comment['openid'], 'oidc:') === 0 ) {
		$issuer = substr($comment['openid'], 5);
		$hash = strpos($issuer, '#');
		if( $hash !== false ) { $issuer = substr($issuer, 0, $hash); }
		$title = htmlspecialchars(_textf("OIDC(%1)로 작성하였습니다", $issuer), ENT_QUOTES);
		preg_match_all('@<a(.*)>(.*)</a>@Usi', $name, $temp);
		for ($i=0; $i<count($temp[0]); $i++) {
			if (strip_tags($temp[2][$i]) == $comment['name']) {
				$name = str_replace($temp[0][$i], "<a{$temp[1][$i]} title='".$title."'>".$temp[2][$i]."</a>", $name);
			}
		}
		return $name;
	}

	$openidlogodisplay = Setting::getBlogSettingGlobal( "OpenIDLogoDisplay", 0 );
	if( $openidlogodisplay ) {
		$name = "<a href=\"".$comment['openid']."\" class=\"openid\"><img src=\"" .$context->getProperty('service.path'). "/resources/image/icon_openid.gif\" alt=\"OpenID Logo\" title=\"" .
			_textf("오픈아이디(%1)로 작성하였습니다", $comment['openid'] ) . "\" /></a>" . $name;
	} else {
		preg_match_all('@<a(.*)>(.*)</a>@Usi', $name, $temp);
		
		for ($i=0; $i<count($temp[0]); $i++) {
			if (strip_tags($temp[2][$i]) == $comment['name'])
				$name = str_replace($temp[0][$i], "<a{$temp[1][$i]} title='" ._textf("오픈아이디(%1)로 작성하였습니다", $comment['openid'] )."'>".$temp[2][$i]."</a>", $name);
		}
		$name .= "<a href=\"".$comment['openid']."\" class=\"openid\">&nbsp;</a>";
	}
	return $name;
}

function openid_OpenIDAffiliateLinks( $links, $requestURI )
{
	include_once "affiliate.php";
	return array( $openid_help_link, $openid_signup_link );
}

/// OIDC (OpenID Connect) 설정 탭 — 이중 옵트인 게이트2.
/// 플러그인 활성화만으로는 동작하지 않으며, 여기서 "활성"으로 바꾸고 Provider 정보를 모두 입력해야
/// OIDCClient::isEnabled() 가 true 가 되어 OIDC 로그인이 동작한다(인증 흐름은 단계 2~에서 구현).
function CL_OpenID_ConfigHandler($plugin)
{
	return '<?xml version="1.0" encoding="utf-8"?>'
		. '<config>'
		. '<fieldset legend="OpenID Connect (OIDC) — 기본 비활성. 명시적으로 활성화 + Provider 설정을 모두 입력해야 동작합니다.">'
		. '<field type="select" name="oidc_enabled" title="OIDC 활성화">'
		. '<op value="n" checked="checked">비활성 (기본)</op>'
		. '<op value="y">활성</op>'
		. '<caption>플러그인 활성화만으로는 동작하지 않습니다. 이 항목을 "활성"으로 바꾸고 아래 Provider 정보를 모두 입력해야 OIDC 로그인이 동작합니다.</caption>'
		. '</field>'
		. '<field type="text" name="oidc_issuer" title="Issuer URL" value="" size="60">'
		. '<caption>OIDC Provider issuer. 예: https://accounts.google.com (자동으로 /.well-known/openid-configuration 을 조회합니다)</caption>'
		. '</field>'
		. '<field type="text" name="oidc_client_id" title="Client ID" value="" size="60"></field>'
		. '<field type="text" name="oidc_client_secret" title="Client Secret" value="" size="60">'
		. '<caption>Provider 에 등록한 클라이언트 자격증명.</caption>'
		. '</field>'
		. '<field type="text" name="oidc_redirect_uri" title="Redirect URI (선택)" value="" size="60">'
		. '<caption>Provider 에 등록한 콜백 URL. 미입력 시 {블로그}/login/openid/callback 자동 사용(단계 2).</caption>'
		. '</field>'
		. '</fieldset>'
		. '</config>';
}
?>
