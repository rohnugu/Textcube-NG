<?php
function EmoticonOnComment_main($target, $mother) {
	global $pluginURL;
	$emoticons = array(
		':)' => '<img src="' . $pluginURL . '/emoticon01.gif" alt=":)" />',
		';)' => '<img src="' . $pluginURL . '/emoticon01.gif" alt=";)" />',
		':P' => '<img src="' . $pluginURL . '/emoticon02.gif" alt=":P" />',
		'8D' => '<img src="' . $pluginURL . '/emoticon03.gif" alt="8D" />',
		':(' => '<img src="' . $pluginURL . '/emoticon04.gif" alt=":(" />',
		'--;' => '<img src="' . $pluginURL . '/emoticon05.gif" alt="--;" />'
	);
	foreach ($emoticons as $key => $value)
		$target = str_replace($key, $value, $target);
	return $target;
}

// 플러그인 설정 탭에 지원 이모티콘 코드와 실제 표출 이미지를 표기하기 위한 manifestHandler.
// handleConfig() 가 이 함수 호출 전에 $pluginURL 을 service.path/plugins/EmoticonOnComment 로 설정함.
function EmoticonOnComment_ConfigHandler($plugin) {
	global $pluginURL;
	$b = htmlspecialchars($pluginURL, ENT_QUOTES, 'UTF-8');
	return '<?xml version="1.0" encoding="utf-8"?>'
		. '<config>'

		. '<fieldset legend="지원 이모티콘 목록">'
		. '<field type="text" name="_emo_help" title="코드" value=":)  ;)  :P  8D  :(  --;" size="52">'
		. '<caption><![CDATA['
		. '<table style="border-collapse:collapse;line-height:1.8;">'
		. '<tr>'
		.   '<th style="text-align:left;padding:2px 10px 2px 0;">입력 코드</th>'
		.   '<th style="padding:2px 14px;">표출 이미지</th>'
		.   '<th style="text-align:left;padding:2px 0 2px 10px;">설명</th>'
		. '</tr>'
		. '<tr><td style="padding:2px 10px 2px 0;"><b>:)</b></td><td style="text-align:center;padding:2px 14px;"><img src="' . $b . '/emoticon01.gif" alt=":)" /></td><td style="padding:2px 0 2px 10px;">웃는 얼굴</td></tr>'
		. '<tr><td style="padding:2px 10px 2px 0;"><b>;)</b></td><td style="text-align:center;padding:2px 14px;"><img src="' . $b . '/emoticon01.gif" alt=";)" /></td><td style="padding:2px 0 2px 10px;">윙크 <span style="color:#888;">(웃는 얼굴과 동일 이미지)</span></td></tr>'
		. '<tr><td style="padding:2px 10px 2px 0;"><b>:P</b></td><td style="text-align:center;padding:2px 14px;"><img src="' . $b . '/emoticon02.gif" alt=":P" /></td><td style="padding:2px 0 2px 10px;">혀 내밀기</td></tr>'
		. '<tr><td style="padding:2px 10px 2px 0;"><b>8D</b></td><td style="text-align:center;padding:2px 14px;"><img src="' . $b . '/emoticon03.gif" alt="8D" /></td><td style="padding:2px 0 2px 10px;">큰 웃음</td></tr>'
		. '<tr><td style="padding:2px 10px 2px 0;"><b>:(</b></td><td style="text-align:center;padding:2px 14px;"><img src="' . $b . '/emoticon04.gif" alt=":(" /></td><td style="padding:2px 0 2px 10px;">슬픈 얼굴</td></tr>'
		. '<tr><td style="padding:2px 10px 2px 0;"><b>--;</b></td><td style="text-align:center;padding:2px 14px;"><img src="' . $b . '/emoticon05.gif" alt="--;" /></td><td style="padding:2px 0 2px 10px;">당황/식은땀</td></tr>'
		. '</table>'
		. ']]></caption>'
		. '</field>'
		. '</fieldset>'

		. '</config>';
}
?>
