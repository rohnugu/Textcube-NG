<?php
// Textile formatter for Textcube 1.10.3
// Library by Threshold state.
// Driver by Jeongkyu Shin. (inureyes@gmail.com)
// 2008.1.21
// Last updated : 2015. 2. 16

if(!class_exists('Textile')) require_once 'classTextile.php';

function FM_Textile_format($blogid, $id, $content, $keywords = array(), $useAbsolutePath = true, $bRssMode = false) {
	$context = Model_Context::getInstance();
	$textile = new Textile();
	$path = __TEXTCUBE_ATTACH_DIR__."/$blogid";
	$url = $context->getProperty("service.path")."/attach/$blogid";
	if(!function_exists('FM_TTML_bindAttachments')) { // To reduce the amount of loading code!
		require_once 'ttml.php';
	}
	$view = FM_TTML_bindAttachments($id, $path, $url, $content, $useAbsolutePath, $bRssMode);
	$view = FM_TTML_preserve_TTML_type_tags($view);
	$view = $textile->TextileThis($view);
	$view = FM_TTML_restore_TTML_type_tags($view);
	$view = FM_TTML_bindTags($id, $view);
	return $view;
}

function FM_Textile_summary($blogid, $id, $content, $keywords = array(), $useAbsolutePath = true) {
	$context = Model_Context::getInstance();

	$view = FM_Textile_format($blogid, $id, $content, $keywords, $useAbsolutePath, true);
	if (!$context->getProperty("blog.publishWholeOnRSS")) $view = UTF8::lessen(removeAllTags(stripHTML($view)), 255);
	return $view;
}

// 플러그인 설정 탭에 Textile 에디터 조작 방법 및 문법 사용 방법을 표시하기 위한 manifestHandler.
// handleConfig() 가 이 함수를 호출하고, 반환된 XML 을 파싱해 설정 폼으로 렌더링한다.
// field caption 의 .value 는 raw HTML 로 출력되므로 CDATA 내 HTML 태그 사용 가능.
// title="항목" + value="..." 으로 입력칸에 섹션 요약을 표시, 중요 항목만 caption 내 <b> 태그 적용.
function FM_Textile_ConfigHandler($plugin) {
	return '<?xml version="1.0" encoding="utf-8"?>'
		. '<config>'

		. '<fieldset legend="에디터 조작">'
		. '<field type="text" name="_textile_help_editor" title="항목" value="포맷 선택 · 텍스트 직접 입력 · 툴바 이미지 삽입 · 첨부 파일 · 미리보기" size="52">'
		. '<caption><![CDATA['
		. '포맷 선택: 글쓰기 하단 포맷 드롭다운 → Textile<br>'
		. '입력 방식: 순수 텍스트 직접 입력 (WYSIWYG 없음)<br>'
		. '<b>이미지 삽입: 정렬 버튼(왼쪽/가운데/오른쪽) 클릭 → [##_..._##] 태그 자동 삽입</b><br>'
		. '첨부 경로 직접 참조: [##_ATTACH_PATH_##] 또는 http://tt_attach_path/<br>'
		. '미리보기: 임시저장 후 미리보기 버튼 이용'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '<fieldset legend="기본 문법">'
		. '<field type="text" name="_textile_help_basic" title="항목" value="제목 · 강조 · 링크/이미지 · 목록 · 인용 · 코드" size="52">'
		. '<caption><![CDATA['
		. 'h1. 제목1 &nbsp; h2. 제목2 &nbsp; h3. 제목3 &nbsp;&nbsp; (h1~h6)<br>'
		. '_기울임_ &nbsp; *강조* &nbsp; **굵게** &nbsp; -취소선- &nbsp; ^윗첨자^ &nbsp; ~아래첨자~<br>'
		. '"링크텍스트":URL &nbsp;/&nbsp; "링크(설명)":URL &nbsp;&nbsp; (링크)<br>'
		. '!이미지URL! &nbsp;/&nbsp; !이미지URL(대체텍스트)! &nbsp;/&nbsp; !이미지URL!:링크URL<br>'
		. '* 항목 (비순서 목록) &nbsp;/&nbsp; # 항목 (순서 목록) &nbsp;/&nbsp; ** 또는 ## 하위 항목<br>'
		. 'bq. 인용 &nbsp;/&nbsp; bq.:URL 출처있는 인용 &nbsp;/&nbsp; @인라인 코드@'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '<fieldset legend="고급 문법">'
		. '<field type="text" name="_textile_help_advanced" title="항목" value="표 · 각주 · 약어 · 정렬 · 클래스/ID · notextile" size="52">'
		. '<caption><![CDATA['
		. '<b>표:</b> |셀1|셀2|셀3| &nbsp;/&nbsp; 헤더 행: |_. 헤더1|_. 헤더2|<br>'
		. '<b>각주:</b> fn1. 각주 내용 (별도 단락) &nbsp;/&nbsp; 본문: [1]<br>'
		. '<b>약어:</b> ABC(Always Be Closing) → &lt;acronym&gt;<br>'
		. '정렬: h2&lt;. 왼쪽 &nbsp;/&nbsp; h2&gt;. 오른쪽 &nbsp;/&nbsp; h2=. 가운데 &nbsp;/&nbsp; h2&lt;&gt;. 양쪽<br>'
		. '클래스/ID: p(클래스). 단락 &nbsp;/&nbsp; p(#아이디). 단락 &nbsp;/&nbsp; p{color:red}. 스타일<br>'
		. '<b>==서식 없음==</b>: notextile 영역 지정 (HTML 태그 직접 삽입 시 사용)'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '<fieldset legend="Textcube 확장 태그">'
		. '<field type="text" name="_textile_help_tc" title="항목" value="더보기([#M_..._M#]) · 첨부 파일 경로" size="52">'
		. '<caption><![CDATA['
		. '<b>더보기:</b> [#M_ 더보기 버튼 | 접기 버튼 | 펼쳐질 내용 _M#]<br>'
		. '<b>첨부 경로:</b> [##_ATTACH_PATH_##] &nbsp;/&nbsp; http://tt_attach_path/'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '</config>';
}
?>
