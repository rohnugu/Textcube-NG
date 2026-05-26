<?php
// TTML Formatter for Textcube 1.10.3
// (C) 2004-2016 Needlworks / Tatter Network Foundation

if(!function_exists('FM_TTML_bindAttachments')) require_once 'ttml.php';

function FM_TTML_format($blogid, $id, $content, $keywords = array(), $useAbsolutePath = true, $bRssMode = false) {
	$context = Model_Context::getInstance();
	$path = __TEXTCUBE_ATTACH_DIR__."/$blogid";
	$url = $context->getProperty("service.path")."/attach/$blogid";
	$view = FM_TTML_bindAttachments($id, $path, $url, $content, $useAbsolutePath, $bRssMode);
//	if (is_array($keywords)) $view = FM_TTML_bindKeywords($keywords, $view);
	$view = FM_TTML_bindTags($id, $view);
	return $view;
}

function FM_TTML_summary($blogid, $id, $content, $keywords = array(), $useAbsolutePath = true) {
	$context = Model_Context::getInstance();
	$view = FM_TTML_format($blogid, $id, $content, $keywords, $useAbsolutePath, true);
	if (!$context->getProperty("blog.publishWholeOnRSS")) $view = UTF8::lessen(removeAllTags(stripHTML($view)), 255);
	return $view;
}

// 플러그인 설정 탭에 TTML 에디터 조작 방법 및 태그 사용 방법을 표시하기 위한 manifestHandler.
// handleConfig() 가 이 함수를 호출하고, 반환된 XML 을 파싱해 설정 폼으로 렌더링한다.
// field caption 의 .value 는 raw HTML 로 출력되므로 CDATA 내 HTML 태그 사용 가능.
// title="항목" + value="..." 으로 입력칸에 섹션 요약을 표시, 중요 항목만 caption 내 <b> 태그 적용.
function FM_TTML_ConfigHandler($plugin) {
	return '<?xml version="1.0" encoding="utf-8"?>'
		. '<config>'

		. '<fieldset legend="에디터 조작">'
		. '<field type="text" name="_ttml_help_editor" title="항목" value="기본 포맷 · WYSIWYG 에디터 · 툴바 이미지 삽입 · 첨부 파일" size="52">'
		. '<caption><![CDATA['
		. 'TTML 은 Textcube 기본 포맷 — 포맷 드롭다운에서 별도 선택 불필요<br>'
		. 'WYSIWYG 에디터(TinyMCE)로 직접 HTML 작성 가능<br>'
		. '<b>이미지 삽입: 정렬 버튼(왼쪽/가운데/오른쪽) 클릭 → [##_..._##] 태그 자동 삽입</b><br>'
		. '첨부 경로 직접 참조: [##_ATTACH_PATH_##] 또는 http://tt_attach_path/<br>'
		. '미리보기: 임시저장 후 미리보기 버튼 이용'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '<fieldset legend="이미지 삽입 태그">'
		. '<field type="text" name="_ttml_help_img" title="항목" value="단일(1C/1L/1R) · 2열 · 3열 이미지 · 캡션" size="52">'
		. '<caption><![CDATA['
		. '<b>단일:</b> [##_ 1C|파일명||캡션 _##] &nbsp;(1C: 가운데, 1L: 왼쪽, 1R: 오른쪽)<br>'
		. '<b>2열:</b> [##_ 2C|파일1||캡션1|파일2||캡션2 _##]<br>'
		. '<b>3열:</b> [##_ 3C|파일1||캡션1|파일2||캡션2|파일3||캡션3 _##]<br>'
		. '세 번째 항목(|| 사이)은 크기 속성 — 보통 비워 둠 (자동 조절)<br>'
		. '복잡한 태그는 에디터 툴바에서 이미지 정렬 버튼으로 자동 생성 권장'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '<fieldset legend="미디어 태그">'
		. '<field type="text" name="_ttml_help_media" title="항목" value="Gallery(슬라이드쇼) · Jukebox(음악 플레이어)" size="52">'
		. '<caption><![CDATA['
		. '<b>Gallery (슬라이드쇼):</b> [##_ Gallery|파일1|캡션1|파일2|캡션2|...|width=N&amp;height=M _##]<br>'
		. '<b>Jukebox (음악 플레이어):</b> [##_ Jukebox|파일1|이름1|파일2|이름2|...|속성|캡션 _##]<br>'
		. '파일명은 첨부 파일 목록의 파일명 그대로 입력<br>'
		. 'Gallery/Jukebox 태그는 에디터 툴바 버튼으로 자동 생성 권장'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '<fieldset legend="Textcube 확장 태그">'
		. '<field type="text" name="_ttml_help_tc" title="항목" value="더보기([#M_..._M#]) · 첨부 파일 경로" size="52">'
		. '<caption><![CDATA['
		. '<b>더보기:</b> [#M_ 더보기 버튼 | 접기 버튼 | 펼쳐질 내용 _M#]<br>'
		. '<b>첨부 경로:</b> [##_ATTACH_PATH_##] &nbsp;/&nbsp; http://tt_attach_path/'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '</config>';
}
?>
