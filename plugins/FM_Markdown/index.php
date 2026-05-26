<?php
// Markdown formatter for Textcube 1.10
// By Jeongkyu Shin. (inureyes@gmail.com)

if(!function_exists('Markdown')) require_once 'markdown.php';

function FM_Markdown_format($blogid, $id, $content, $keywords = array(), $useAbsolutePath = true, $bRssMode = false) {
	$context = Model_Context::getInstance();
	$path = __TEXTCUBE_ATTACH_DIR__."/$blogid";
	$url = $context->getProperty("service.path")."/attach/$blogid";
	if(!function_exists('FM_TTML_bindAttachments')) { // To reduce the amount of loading code!
		require_once 'ttml.php';
	}
	$view = FM_TTML_bindAttachments($id, $path, $url, $content, $useAbsolutePath, $bRssMode);
	$view = FM_TTML_preserve_TTML_type_tags($view);
	$view = Markdown($view, $id);
	$view = FM_TTML_restore_TTML_type_tags($view);
	$view = FM_TTML_bindTags($id, $view);
	return $view;
}

function FM_Markdown_summary($blogid, $id, $content, $keywords = array(), $useAbsolutePath = true) {
	$context = Model_Context::getInstance();
	$view = FM_Markdown_format($blogid, $id, $content, $keywords, $useAbsolutePath, true);
    if (!$context->getProperty("blog.publishWholeOnRSS")) $view = UTF8::lessen(removeAllTags(stripHTML($view)), 255);
	return $view;
}

// 플러그인 설정 탭에 Markdown 에디터 조작 방법 및 문법 사용 방법을 표시하기 위한 manifestHandler.
// handleConfig() 가 이 함수를 호출하고, 반환된 XML 을 파싱해 설정 폼으로 렌더링한다.
// field caption 의 .value 는 raw HTML 로 출력되므로 CDATA 내 HTML 태그 사용 가능.
// title="항목" + value="..." 으로 입력칸에 섹션 요약을 표시, 중요 항목만 caption 내 <b> 태그 적용.
function FM_Markdown_ConfigHandler($plugin) {
	return '<?xml version="1.0" encoding="utf-8"?>'
		. '<config>'

		. '<fieldset legend="에디터 조작">'
		. '<field type="text" name="_md_help_editor" title="항목" value="포맷 선택 · 입력 방식 · 툴바 주의 · 첨부 파일 · 미리보기" size="52">'
		. '<caption><![CDATA['
		. '포맷 선택: 글쓰기 하단 포맷 드롭다운 → Markdown<br>'
		. '입력 방식: 순수 텍스트 직접 입력 (WYSIWYG 없음)<br>'
		. '<b>⚠ 툴바 Bold/Italic 버튼 → HTML 태그(&lt;strong&gt;/&lt;em&gt;) 삽입 — 사용 금지</b><br>'
		. '첨부: 정렬 버튼 클릭 → img 삽입 &nbsp;/&nbsp; 직접: [##_ATTACH_PATH_##]/파일명<br>'
		. '미리보기: 임시저장 후 미리보기 버튼 이용'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '<fieldset legend="기본 문법">'
		. '<field type="text" name="_md_help_basic" title="항목" value="제목 · 강조 · 링크/이미지 · 목록 · 인용 · 코드 · 수평선" size="52">'
		. '<caption><![CDATA['
		. '# H1 &nbsp; ## H2 &nbsp; ### H3 &nbsp; #### H4 &nbsp;&nbsp; (제목)<br>'
		. '**굵게** &nbsp; *기울임* &nbsp; ~~취소선~~<br>'
		. '[링크텍스트](URL) &nbsp;/&nbsp; ![대체텍스트](URL) &nbsp;&nbsp; (링크 / 이미지)<br>'
		. '- 항목 또는 * 항목 (비순서 목록) &nbsp;/&nbsp; 1. 항목 (순서 목록)<br>'
		. '하위 목록: 4칸 들여쓰기<br>'
		. '&gt; 인용 &nbsp;/&nbsp; &gt;&gt; 중첩 인용<br>'
		. '`인라인 코드` &nbsp;/&nbsp; 코드 블록: 4칸 들여쓰기<br>'
		. '--- 또는 *** &nbsp;&nbsp; (수평선, 단독 줄)'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '<fieldset legend="Markdown Extra">'
		. '<field type="text" name="_md_help_extra" title="항목" value="표 · 각주 · 정의 목록 · 펜스 코드 블록 · 약어" size="52">'
		. '<caption><![CDATA['
		. '<b>표:</b> | 헤더1 | 헤더2 | &nbsp;/&nbsp; 구분자: | :--- | ---: | &nbsp;/&nbsp; | 값1 | 값2 |<br>'
		. '<b>각주:</b> 본문[^id] &nbsp;/&nbsp; [^id]: 내용 (별도 줄)<br>'
		. '<b>정의 목록:</b> 단어 → 바로 다음 줄 : 정의 (빈 줄 없이)<br>'
		. '<b>펜스 코드:</b> ``` 언어명 / 코드 / ```<br>'
		. '<b>약어:</b> *[HTML]: HyperText Markup Language'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '<fieldset legend="Textcube 확장 태그">'
		. '<field type="text" name="_md_help_tc" title="항목" value="더보기([#M_..._M#]) · 첨부 파일 경로" size="52">'
		. '<caption><![CDATA['
		. '<b>더보기:</b> [#M_ 더보기 버튼 | 접기 버튼 | 펼쳐질 내용 _M#]<br>'
		. '<b>첨부 경로:</b> [##_ATTACH_PATH_##] &nbsp;/&nbsp; http://tt_attach_path/'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '</config>';
}
?>
