<?php

requireComponent( "Needlworks.Mail.Pop3" );
requireModel( "common.setting" );

#[AllowDynamicProperties]
class Moblog
{
	function __construct( $options )
	{
		global $pop3logs;
		if( !isset($debugLogs) ) {
			$pop3logs = array();
		}
		foreach( $options as $k => $v ) {
			$this->$k = $v;
		}

		$this->recentCount = 100;
		$visibilities = array( "private", "protected", "public", "syndicated" );
		$this->visibility = $visibilities[$this->visibility];
		// 빈 문자열 항목 제거 — preg_split('', '') 은 [''] 을 반환하며 strstr($from, '') 가 PHP 8 에서 ValueError 를 일으킴
		$this->allow = array_values(array_filter(preg_split("@[,\s]+@", $this->allow), 'strlen'));

		// 프로토콜에 따라 POP3 또는 IMAP 클라이언트 생성 — 외부 인터페이스 동일
		if (!empty($this->protocol) && $this->protocol === 'imap') {
			$this->pop3 = new Imap();
			$folder = !empty($this->imapfolder) ? $this->imapfolder : 'INBOX';
			$this->pop3->setFolder($folder);
		} else {
			$this->pop3 = new Pop3();
		}
		$this->pop3->setLogger( array(&$this,'log') );
		$this->pop3->setStatCallback( array(&$this,'statCallback') );
		$this->pop3->setUidFilter( array(&$this,'checkUid') );
		$this->pop3->setSizeFilter( array(&$this,'checkSize') );
		$this->pop3->setRetrCallback( array(&$this,'retrieveCallback') );

		$this->uidl_file = ROOT.DS."cache".DS."pop3uidl.txt";

		if( file_exists( $this->uidl_file ) ) {
			$this->stored_uidl = file_get_contents( $this->uidl_file );
		} else {
			$this->stored_uidl = '';
		}

		global $service;
		$fallback_charsets = array( 'ko' => 'euc-kr' );
		if( isset( $fallback_charsets[$service['language']] ) ) {
			$this->pop3->setFallbackCharset( $fallback_charsets[$service['language']] );
		}
	}

	function decorate_log($s)
	{
		global $blogURL;
		$s = htmlspecialchars($s);
		$s = preg_replace( "/\(SLOGAN:([^)]+)\)/", "<a href=\"$blogURL/entry/\\1\" target=\"_blank\">click</a>", $s );
		return $s;
	}

	function log($msg)
	{
		$f = fopen( ROOT.DS."cache".DS."moblog.txt", "a" );
		fwrite( $f, date('Y-m-d H:i:s')." $msg\r\n" );
		fclose($f);
		if( $msg[0] == '*' ) {
			global $pop3logs;
			array_push( $pop3logs, Moblog::decorate_log(substr($msg,2)) );
		}
	}

	function saveUidl()
	{
		$f = fopen( $this->uidl_file, "w" );
		fwrite( $f, $this->stored_uidl );
		fclose($f);
		return true;
	}

	function check()
	{
		$sslLabel = ['none' => '', 'ssl' => '(SSL/TLS)', 'starttls' => '(STARTTLS)'][$this->ssl] ?? '';
		if( !$this->pop3->connect( $this->host, $this->port, $this->ssl ) ) {
			$err = $this->pop3->getLastError();
			$this->log( "* "._t("접속 실패")." : ".$this->host.":".$this->port.$sslLabel.($err ? " [$err]" : "") );
			return false;
		}
		$this->log( "* "._t("접속 성공")." : ".$this->host.":".$this->port.$sslLabel );
		if( !$this->pop3->authorize( $this->username, $this->password ) ) {
			$this->log( "* "._t("인증 실패") );
			return false;
		}
		$this->log( "* "._t("인증 성공") );

		$this->pop3->run();

		if( !$this->pop3->quit() ) {
			return false;
		}
		return true;
	}

	function appendUid( $uid )
	{
		$today = date( 'Y-m-d H:i:s' );
		$this->stored_uidl .= "[$uid] $today\r\n";
		$this->saveUidl();
	}

	function checkUid( $uid, $number )
	{
		$ret = !!strstr( $this->stored_uidl, "[$uid]" );
		if( $ret ) {
			$this->log( "Msg $number: "._t("이미 확인한 메일")." : $uid" );
		}
		return $ret;
	}

	function checkSize( $size, $number, $total )
	{
		if( $number < $total - $this->recentCount ) {
			return true;
		}
		$ret = $size < $this->minsize;
		if( $ret ) {
			$this->log( "Msg $number: "._t("메일크기가 작음")." : $size" );
		}
		return $ret;
	}

	function isAllowed( & $mail )
	{
		// 실제 발신 주소($mail['from'])만 허용 목록과 대조 — 표시 이름($mail['sender'])은 발신자가 임의로 설정 가능하므로 검사 제외
		if( isset( $mail['from'] ) && !empty($this->allow) ) {
			foreach( $this->allow as $a ) {
				if( strstr( $mail['from'], $a ) !== false ) return true;
			}
		}
		if( $this->allowonly ) {
			$this->logMail( $mail, "SKIP, Not in ALLOW list" );
			return false;
		}

		if( !empty($mail['mms']) ) {
			return true;
		}
		if( isset($mail['return_path']) && strstr( $mail['return_path'], 'mms' ) ) {
			/* KTF: ktfmms, SKT: vmms */
			return true;
		}
		return false;
	}

	function _getDecoratedContent( & $mail, $docid ) {
		if( !empty($mail['text']) && strstr($mail['text'],'magicn.com') ) {
			$mail['text'] = preg_replace( '@.*<BODY>(.*?)<style.*@smi', '$1', $mail['text']);
			$mail['text'] = preg_replace( '@<IMG[^>]*?cid:[^>]*?>@smi', '', $mail['text']);
			$mail['text'] = preg_replace( '@<BR>\s*<BR>\s*<BR>@smi', '<BR>', $mail['text']);
		}
		$text = "<h3 class=\"moblog-title\" id=\"$docid\">$docid</h3>\r\n";
		//$text .= empty($mail['subject']) ? '' : ("<p>".$mail['subject']."</p>\r\n");
		if( !empty($mail['text']) ) {
			$content = $mail['text'];
			if($mail['content_type'] == 'text/plain') {
				$newLineFrom = array("￦n","\\n");
				$newLineTo = "<br />\r\n";
				$content = str_replace($newLineFrom, $newLineTo, $content); 
			}
			$text .= "<div class=\"moblog-body\">".$content."</div>\r\n";
		}
		return $text;
	}

	function statCallback( $total, $totalsize )
	{
		$this->log( "* ".sprintf( _t("총 %d개의 메시지"),$total) );
		$lastStat = Setting::getBlogSettingGlobal( 'MmsPop3stat', '' );
		$stat = "$total $totalsize";
		if( $stat == $lastStat ) {
			$this->log( "* "._t("새로운 메시지가 없습니다") );
			return false;
		}
		setBlogSetting( 'MmsPop3stat', $stat );
		return true;
	}

	function logMail( $mail, $result )
	{
		$date = isset( $mail['date_string'] ) ? "{$mail['date_string']} {$mail['time_string']} " : '';
		$from = isset( $mail['from'] ) ? "({$mail['sender']} <{$mail['from']}>) " : '';
		$this->log( "* "._t("메일").": {$mail['subject']} {$date} {$from}[{$result}]" );
	}

	/**
	 * Extract tags from {tag1,tag2,tag3,...} format string in the mms message.
	 * $mail will be changed to be removed tags.
	 */
	function extractTags( & $mail )
	{
		$p = '/{([^}:;]*)}/';
		$tags = array();
		if( preg_match( $p, $mail['text'], $m ) ) {
			$tags = explode( ',', $m[1] );
			$mail['text'] = preg_replace( $p, '', $mail['text'] );
		}
		return $tags;
	}

	function retrieveCallback( $lines, $uid )
	{
		$this->appendUid( $uid );
		$mail = $this->pop3->parse( $lines );

		if( isset( $mail['date_string'] ) ) {
			$slogan = $mail['date_string'];
			$docid = $mail['time_string'];
		} else {
			$slogan = date( "Y-m-d" );
			$docid = date( "H:i:s" );
		}
		if( in_array( $mail['subject'], array( '제목없음' ) ) ) {
			$mail['subject'] = '';
		}
		if( !$this->isAllowed($mail) ) {
			return false;
		}
		if( false && empty($mail['attachments']) ) {
			$this->logMail( $mail, "SKIP" );
			return false;
		}
		requireComponent( "Textcube.Data.Post" );

		$post = new Post();
		$moblog_begin = "\n<div class=\"moblog-entry\">";
		$moblog_end = "\n</div>\n";

		if( $post->open( "slogan = '$slogan'" ) ) {
			$post->loadTags();
			$this->log( "* 기존 글을 엽니다. (SLOGAN:$slogan)" );
			if( empty($post->tags) ) {
				$post->tags = array();
			}
			$tags = $this->extractTags( $mail ); /* mail content will be changed */
			$post->tags = array_merge( $post->tags, $tags );
			$post->content .= $moblog_begin.$this->_getDecoratedContent( $mail, $docid );
			$post->modified = $mail['date'];
			$post->visibility = $this->visibility;
		} else {
			$this->log( "* 새 글을 작성합니다. (SLOGAN:$slogan)" );
			if( isset( $mail['date_year'] ) ) {
				$post->title = str_replace( array('%Y','%M','%D'), 
					array( $mail['date_year'], $mail['date_month'], $mail['date_day'] ), $this->subject );
			} else {
				$post->title = str_replace( array('%Y','%M','%D'), 
					array( date("Y"), date("m"), date("d") ), $this->subject );
			}
			$post->userid = $this->userid;
			$post->category = $this->category;
			$post->tags = $this->extractTags( $mail ); /* Go with csv string, Tag class supports both string and array */
			$post->content = $moblog_begin.$this->_getDecoratedContent( $mail, $docid );
			$post->contentformatter = getDefaultFormatter();
			$post->contenteditor = getDefaultEditor();
			$post->created = time();
			$post->acceptcomment = true;
			$post->accepttrackback = true;
			$post->visibility = $this->visibility;
			$post->published = time();
			$post->modified = $mail['date'];
			$post->slogan = $slogan;
			if( !$post->add() ) {
				$this->logMail( $mail, "ERROR" );
				$this->log( _t("실패: 글을 추가하지 못하였습니다")." : " . $post->error );
				return false;
			} else {
				CacheControl::flushCategory($post->category);
			}
		}
		/* 슬로건을 지워야만 문제가 발생하지 않습니다. */
		//unset($post->slogan);

		if( isset( $mail['attachments'] ) && count($mail['attachments']) ) {
			requireModel( "blog.api" );
			$post->content .= "<div class=\"moblog-attachments\">\n";
			foreach( $mail['attachments'] as $mail_att ) {
				$this->log( "* ". _t("첨부")." : {$mail_att['filename']}" );
				$att = api_addAttachment( getBlogId(), $post->id, 
							array( 
									'name' => $mail_att['filename'], 
									'content' => $mail_att['decoded_content'], 
									'size' => $mail_att['length']
							) 
					);
				if( !$att ) {
					$this->logMail( $mail, "ERROR" );
					$this->log( _t("실패: 첨부파일을 추가하지 못하였습니다")." : " . $post->error );
					return false;
				}
				$alt = htmlentities($mail_att['filename'],ENT_QUOTES,'utf-8');
				$content ='[##_1C|$FILENAME|width="$WIDTH" height="$HEIGHT" alt="'.$alt.'"|_##]';
				$content = str_replace( '$FILENAME', $att['name'], $content );
				$content = str_replace( '$WIDTH', $att['width'], $content );
				$content = str_replace( '$HEIGHT', $att['height'], $content );
				$post->content .= $content;
			}
			$post->content .= "\n</div>";
		}
		$post->content .= $moblog_end;

		if( !$post->update() ) {
			$this->logMail( $mail, "ERROR" );
			$this->log( _t("실패: 첨부파일을 본문에 연결하지 못하였습니다").". : " . $post->error );
			return false;
		}
		$this->logMail( $mail, "OK" );
		return true;
	}
}

function moblog_check()
{
	if( isset($_GET['check']) && $_GET['check'] == 1 ) {
		echo "<html><body style=\"font-size:0.9em\">";
		echo "<style>.emplog{color:red}.oklog{color:blue}</style>";
		echo "<ul>";
		$logFile = ROOT.DS."cache".DS."moblog.txt";
		$logContent = file_exists($logFile) ? file_get_contents($logFile) : '';
		echo join( "",
			array_map(
				function($li) {
					return preg_match('/^\S+\s+\S+\s+\*/', $li) ?
						(preg_match('/\[OK\]/', $li) ? "<li class=\"oklog\">$li</li>" : "<li class=\"emplog\">$li</li>")
						: "<li>$li</li>";
				},
				explode( "\n", Moblog::decorate_log($logContent) )
			)
		);
		echo "</ul>";
		echo "</body></html>";
		exit;
	}

	if( isset($_GET['clearlog']) && $_GET['clearlog'] == 1 ) {
		echo "<html><body style=\"font-size:0.9em\">";
		if( Acl::check('group.administrators') ) {
			$logFile = ROOT.DS."cache".DS."moblog.txt";
			file_put_contents($logFile, '');
			echo "<p style=\"color:green;\">&#10003; " . _t('로그가 삭제되었습니다.') . "</p>";
		} else {
			echo "<p style=\"color:red;\">" . _t('권한이 없습니다.') . "</p>";
		}
		echo "</body></html>";
		exit;
	}

	header( "Content-type: text/html; charset:utf-8" );
	echo '<html>';
	echo '<head><meta http-equiv="content-type" content="text/html; charset=utf-8" />';
	echo '<body style="font-size:0.9em"><ul><li>';
	$moblog = new Moblog(
		array(
			'username'   => Setting::getBlogSettingGlobal( 'MmsPop3Username', '' ),
			'password'   => Setting::getBlogSettingGlobal( 'MmsPop3Password', '' ),
			'host'       => Setting::getBlogSettingGlobal( 'MmsPop3Host', 'localhost' ),
			'port'       => Setting::getBlogSettingGlobal( 'MmsPop3Port', 110 ),
			'ssl'        => Setting::getBlogSettingGlobal( 'MmsPop3Ssl', 'none' ),
			'protocol'   => Setting::getBlogSettingGlobal( 'MmsPop3Protocol', 'pop3' ),
			'imapfolder' => Setting::getBlogSettingGlobal( 'MmsPop3ImapFolder', 'INBOX' ),
			'userid'     => Setting::getBlogSettingGlobal( 'MmsPop3Fallbackuserid', 1 ),
			'minsize'    => Setting::getBlogSettingGlobal( 'MmsPop3MinSize', 0 )*1024,
			'visibility' => Setting::getBlogSettingGlobal( 'MmsPop3Visibility', '2' ),
			'category'   => Setting::getBlogSettingGlobal( 'MmsPop3Category', 0 ),
			'allowonly'  => Setting::getBlogSettingGlobal( 'MmsPop3AllowOnly', '0' ),
			'allow'      => Setting::getBlogSettingGlobal( 'MmsPop3Allow', '' ),
			'subject'    => Setting::getBlogSettingGlobal( 'MmsPop3Subject', '%Y-%M-%D' ) )
	);
	$moblog->log( "--BEGIN--" );
	$moblog->check();
	$moblog->log( "-- END --" );
	if( Acl::check( 'group.administrators' ) ) {
		global $pop3logs;
		print join("</li>\n<li>",$pop3logs);
	}
	echo "</li>\n</ul></body></html>";
	return true;
}

function moblog_logrotate()
{
	requireLibrary( "function/logrotate" );
	cutlog( ROOT.DS."cache".DS."moblog.txt", 1024*1024 );
	cutlog( ROOT.DS."cache".DS."pop3uidl.txt", 1024*1024 );
}

function moblog_manage()
{
	global $blogURL;
	if( Acl::check('group.administrators') && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
		Setting::setBlogSettingGlobal( 'MmsPop3Email', $_POST['pop3email'] ?? '' );
		Setting::setBlogSettingGlobal( 'MmsPop3Host', $_POST['pop3host'] ?? '' );
		$pop3portVal = (int)($_POST['pop3port'] ?? 110);
		if ($pop3portVal < 1 || $pop3portVal > 65535) $pop3portVal = 110;
		Setting::setBlogSettingGlobal( 'MmsPop3Port', $pop3portVal );
		$sslVal = in_array($_POST['pop3ssl'] ?? '', ['ssl', 'starttls'], true) ? $_POST['pop3ssl'] : 'none';
		Setting::setBlogSettingGlobal( 'MmsPop3Ssl', $sslVal );
		Setting::setBlogSettingGlobal( 'MmsPop3Username', $_POST['pop3username'] ?? '' );
		Setting::setBlogSettingGlobal( 'MmsPop3Password', $_POST['pop3password'] ?? '' );
		$visibilityVal = (int)($_POST['pop3visibility'] ?? 2);
		if (!in_array($visibilityVal, [0, 1, 2, 3], true)) $visibilityVal = 2;
		Setting::setBlogSettingGlobal( 'MmsPop3Visibility', $visibilityVal );
		Setting::setBlogSettingGlobal( 'MmsPop3Category', max(0, (int)($_POST['pop3category'] ?? 0)) );
		Setting::setBlogSettingGlobal( 'MmsPop3Fallbackuserid', getUserId() );
		Setting::setBlogSettingGlobal( 'MmsPop3MinSize', 0 );
		Setting::setBlogSettingGlobal( 'MmsPop3AllowOnly', !empty($_POST['pop3allowonly'])?1:0 );
		Setting::setBlogSettingGlobal( 'MmsPop3Allow', substr(trim($_POST['pop3allow'] ?? ''), 0, 512) );
		Setting::setBlogSettingGlobal( 'MmsPop3Subject', $_POST['pop3subject'] ?? '' );
		$protocolVal = (isset($_POST['pop3protocol']) && $_POST['pop3protocol'] === 'imap') ? 'imap' : 'pop3';
		Setting::setBlogSettingGlobal( 'MmsPop3Protocol', $protocolVal );
		$folderVal = substr(trim($_POST['pop3imapfolder'] ?? 'INBOX'), 0, 64);
		Setting::setBlogSettingGlobal( 'MmsPop3ImapFolder', $folderVal ?: 'INBOX' );
	}
	$pop3email = Setting::getBlogSettingGlobal( 'MmsPop3Email', '' );
	$pop3host = Setting::getBlogSettingGlobal( 'MmsPop3Host', 'localhost' );
	$pop3port = Setting::getBlogSettingGlobal( 'MmsPop3Port', 110 );
	$pop3ssl = Setting::getBlogSettingGlobal( 'MmsPop3Ssl', 'none' );
	if (!in_array($pop3ssl, ['ssl', 'starttls'], true)) $pop3ssl = 'none';
	$pop3username = Setting::getBlogSettingGlobal( 'MmsPop3Username', '' );
	$pop3password = Setting::getBlogSettingGlobal( 'MmsPop3Password', '' );
	$pop3minsize = Setting::getBlogSettingGlobal( 'MmsPop3MinSize', 0 );
	$pop3category = Setting::getBlogSettingGlobal( 'MmsPop3Category', 0 );
	$pop3fallheadercharset = Setting::getBlogSettingGlobal( 'MmsPop3Fallbackcharset', 'euc-kr' );
	$pop3visibility = Setting::getBlogSettingGlobal( 'MmsPop3Visibility', '2' );
	$pop3mmsallowonly = Setting::getBlogSettingGlobal( 'MmsPop3AllowOnly', '0' );
	$pop3mmsallow = Setting::getBlogSettingGlobal( 'MmsPop3Allow', '' );
	$pop3subject = Setting::getBlogSettingGlobal( 'MmsPop3Subject', '%Y-%M-%D' );
	$pop3protocol = Setting::getBlogSettingGlobal( 'MmsPop3Protocol', 'pop3' );
	$pop3imapfolder = Setting::getBlogSettingGlobal( 'MmsPop3ImapFolder', 'INBOX' );
?>
						<hr class="hidden" />
						
						<div id="part-setting-editor" class="part">
							<h2 class="caption"><span class="main-text"><?php echo _t('MMS 메시지 확인용 메일 환경 설정');?></span></h2>
<?php if( !Acl::check( "group.administrators" ) ): ?>
								<div id="editor-section" class="section">
										<dl id="formatter-line" class="line">
											<dt><span class="label"><?php echo _t('MMS 수신 이메일');?></span></dt>
											<dd>
											<?php if( empty($pop3email) ): ?>
												<?php echo _t('비공개') ?>
											<?php else: ?>
												<?php echo $pop3email;?>
											<?php endif ?>
											</dd>
											<dd>
											<?php if( empty($pop3email) ): ?>
												<?php echo _t('이동전화에서 보내는 이메일을 수신할 주소가 공개되지 않았습니다'); ?>
											<?php else: ?>
												<?php echo _t('이동전화를 이용하여 위 메일로 이메일을 보내면 블로그에 게시됩니다'); ?>
											<?php endif ?>
											</dd>
										</dl>
								</div>
<?php else: ?>
							<script type="text/javascript">
							// 프로토콜 라디오에 따라 IMAP 폴더 입력 행 표시/숨김
							function updateProtocolUI(proto) {
								var line = document.getElementById('imap-folder-line');
								if (line) line.style.display = (proto === 'imap') ? '' : 'none';
							}
							// 미리 설정된 서버 선택 시 호스트·포트·암호화·프로토콜을 한 번에 적용
							// 값 형식: "host:port:sslmode:protocol"  sslmode = none | ssl | starttls
							function changehost(packedhost)
							{
								var h = packedhost.split( ':' );
								document.forms['editor-form']['pop3host'].value = h[0];
								document.forms['editor-form']['pop3port'].value = h[1];
								var sslMode = h[2] || 'none';
								var sslRadios = document.querySelectorAll('input[name="pop3ssl"]');
								for( var i = 0; i < sslRadios.length; i++ ) {
									sslRadios[i].checked = (sslRadios[i].value === sslMode);
								}
								if( h[3] ) {
									var protos = document.querySelectorAll('input[name="pop3protocol"]');
									for( var i = 0; i < protos.length; i++ ) {
										protos[i].checked = (protos[i].value === h[3]);
									}
									updateProtocolUI(h[3]);
								}
							}
							// 값 형식: "표시이름:host:port:sslmode:protocol"  sslmode = none | ssl | starttls
							function renderhosts()
							{
								var hosts = "<?php echo _t('PREDEFINED POP3 HOSTS') ?>";
								if( hosts == 'PREDEFINED POP3 HOSTS' ) {
									hosts = "gmail POP3:pop.gmail.com:995:ssl:pop3"
										+ "/gmail IMAP:imap.gmail.com:993:ssl:imap"
										+ "/hanmail POP3:pop.hanmail.net:995:ssl:pop3"
										+ "/hanmail IMAP:imap.daum.net:993:ssl:imap"
										+ "/naver POP3:pop.naver.com:995:ssl:pop3"
										+ "/naver IMAP:imap.naver.com:993:ssl:imap"
										+ "/nate POP3:mail.nate.com:110:none:pop3";
								}
								hosts = "Localhost POP3:localhost:110:none:pop3/" + hosts;
								hosts = hosts.split('/');
								for( var i=0; i<hosts.length; i++ ) {
									var h = hosts[i];
									var n = h.split(':')[0];
									var v = h.substr(n.length+1);
									document.write( "<option value=\""+v+"\">"+n+"</option>" );
								}
							}
							</script>
							<form id="editor-form" class="data-inbox" method="post" action="<?php echo $blogURL;?>/owner/plugin/adminMenu?name=CL_Moblog/moblog_manage">
								<div id="editor-section" class="section">
									<fieldset class="container">
										<legend><?php echo _t('MMS 환경을 설정합니다');?></legend>
										
										<dl id="formatter-line" class="line">
											<dt><span class="label"><?php echo _t('MMS용 이메일');?></span></dt>
											<dd>
												<input type="text" style="width:14em" class="input-text" name="pop3email" value="<?php echo htmlspecialchars($pop3email, ENT_QUOTES, 'UTF-8');?>" />
												<?php echo _t('(필진에 공개 됩니다. 사진을 찍어 이메일로 보내면 포스팅이 됩니다)'); ?>
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('프로토콜');?></span></dt>
											<dd>
												<input type="radio" id="proto_pop3" name="pop3protocol" value="pop3"
													<?php echo ($pop3protocol !== 'imap') ? 'checked="checked"' : ''; ?>
													onclick="updateProtocolUI('pop3')" />
												<label for="proto_pop3">POP3</label>
												&nbsp;&nbsp;
												<input type="radio" id="proto_imap" name="pop3protocol" value="imap"
													<?php echo ($pop3protocol === 'imap') ? 'checked="checked"' : ''; ?>
													onclick="updateProtocolUI('imap')" />
												<label for="proto_imap">IMAP</label>
											</dd>
										</dl>
										<dl id="imap-folder-line" class="line" style="<?php echo ($pop3protocol === 'imap') ? '' : 'display:none'; ?>">
											<dt><span class="label"><?php echo _t('IMAP 폴더');?></span></dt>
											<dd>
												<input type="text" style="width:14em" class="input-text" name="pop3imapfolder"
													value="<?php echo htmlspecialchars($pop3imapfolder, ENT_QUOTES, 'UTF-8');?>" />
												<span style="color:#888;"><?php echo _t('(기본값: INBOX)');?></span>
											</dd>
										</dl>
										<dl id="formatter-line" class="line">
											<dt><span class="label"><?php echo _t('메일 서버');?></span></dt>
											<dd>
												<input type="text" style="width:14em" class="input-text" name="pop3host" value="<?php echo htmlspecialchars($pop3host, ENT_QUOTES, 'UTF-8');?>" />
												<select onchange="changehost(this.value)">
												<option value=""><?php echo _t('선택하세요') ?></option>
												<script type="text/javascript">renderhosts()</script>
												</select>
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('암호화');?></span></dt>
											<dd>
												<input type="radio" name="pop3ssl" id="ssl_none" value="none"
												<?php echo ($pop3ssl === 'none')     ? 'checked="checked"' : ''; ?> />
												<label for="ssl_none"><?php echo _t('없음');?></label>
												&nbsp;&nbsp;
												<input type="radio" name="pop3ssl" id="ssl_ssl" value="ssl"
												<?php echo ($pop3ssl === 'ssl')      ? 'checked="checked"' : ''; ?> />
												<label for="ssl_ssl">SSL/TLS</label>
												&nbsp;&nbsp;
												<input type="radio" name="pop3ssl" id="ssl_starttls" value="starttls"
												<?php echo ($pop3ssl === 'starttls') ? 'checked="checked"' : ''; ?> />
												<label for="ssl_starttls">STARTTLS</label>
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('서버 포트');?></span></dt>
											<dd>
												<input type="text" style="width:14em" class="input-text" name="pop3port" value="<?php echo htmlspecialchars((string)$pop3port, ENT_QUOTES, 'UTF-8');?>" />
												<span style="color:#888;"><?php echo _t('(POP3: 110 없음/STARTTLS, 995 SSL · IMAP: 143 없음/STARTTLS, 993 SSL)');?></span>
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('사용자 아이디');?></span></dt>
											<dd>
												<input type="text" style="width:14em" class="input-text" name="pop3username" value="<?php echo htmlspecialchars($pop3username, ENT_QUOTES, 'UTF-8');?>" />
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('비밀번호');?></span></dt>
											<dd>
												<input type="password" style="width:14em" class="input-text" name="pop3password" value="<?php echo htmlspecialchars($pop3password, ENT_QUOTES, 'UTF-8');?>" />
											</dd>
										</dl>
									<div class="button-box">
										<input type="submit" class="save-button input-button wide-button" value="<?php echo _t('저장하기');?>"  />
									</div>
								</div>
							<h2 class="caption"><span class="main-text"><?php echo _t('글 쓰기 환경 설정');?></span></h2>
								<div id="editor-section" class="section">
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('제목');?></span></dt>
											<dd>
												<input type="text" style="width:24em" class="input-text" id="pop3subject" name="pop3subject" value="<?php echo htmlspecialchars($pop3subject, ENT_QUOTES, 'UTF-8');?>" />
												(<?php echo _t('%Y:년, %M:월, %D:일');?>) <input type="button" value="<?php echo _t("초기화");?>" onclick="document.getElementById('pop3subject').value='%Y-%M-%D';return false;" />
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('공개여부');?></span></dt>
											<dd>
													<span id="status-private" class="status-private"><input type="radio" id="visibility_private" class="radio" name="pop3visibility" value="0"<?php echo ($pop3visibility == 0 ? ' checked="checked"' : '');?> /><label for="visibility_private"><?php echo _t('비공개');?></label></span>
													<span id="status-protected" class="status-protected"><input type="radio" id="visibility_protected" class="radio" name="pop3visibility" value="1"<?php echo ($pop3visibility == 1 ? ' checked="checked"' : '');?> /><label for="visibility_protected"><?php echo _t('보호');?></label></span>
													<span id="status-public" class="status-public"><input type="radio" id="visibility_public" class="radio" name="pop3visibility" value="2"<?php echo ($pop3visibility == 2 ? ' checked="checked"' : '');?> /><label for="visibility_public"><?php echo _t('공개');?></label></span>
													<span id="status-syndicated" class="status-syndicated"><input type="radio" id="visibility_syndicated" class="radio" name="pop3visibility" value="3"<?php echo ($pop3visibility == 3 ? ' checked="checked"' : '');?> /><label for="visibility_syndicated"><?php echo _t('발행');?></label></span>
											</dd>
										</dl>
										<dl id="editor-line" class="line">
											<dt><span class="label"><?php echo _t('분류');?></span></dt>
											<dd>
												<select id="category" name="pop3category">
													<optgroup class="category" label="<?php echo _t('분류');?>">
			<?php foreach (getCategories(getBlogId()) as $category): ?>
			<?php 	if ($category['id'] != 0): ?>
														<option value="<?php echo $category['id'];?>" <?php echo $category['id'] == $pop3category ? 'selected':''?> >
														<?php echo ($category['visibility'] > 1 ? '' : _t('(비공개)')).htmlspecialchars($category['name']);?></option>
			<?php	endif ?>
			<?php 	foreach ($category['children'] as $child): ?>
			<?php 		if ($category['id'] != 0): ?>
														<option value="<?php echo $child['id'];?>" <?php echo $child['id'] == $pop3category ? 'selected':''?> >&nbsp;― <?php echo ($category['visibility'] > 1 && $child['visibility'] > 1 ? '' : _t('(비공개)')).htmlspecialchars($child['name']);?></option>
			<?php 		endif ?>
			<?php 	endforeach ?>
			<?php endforeach ?>
													</optgroup>
												</select>
											</dd>
										</dl>
									<div class="button-box">
										<input type="submit" class="save-button input-button wide-button" value="<?php echo _t('저장하기');?>"  />
									</div>
								</div>
							<h2 class="caption"><span class="main-text"><?php echo _t('메일 필터링 설정');?></span></h2>
								<div id="editor-section" class="section">
										<dl id="formatter-line" class="line">
											<dt><span class="label"><?php echo _t('허용 목록');?></span></dt>
											<dd>
												<input type="radio" id="pop3allowonly" name="pop3allowonly" value="1" <?php echo $pop3mmsallowonly ? 'checked="checked"':'' ?> />
												<label for="pop3allowonly"><?php echo _t('아래 목록의 송신자에게서 온 메일만 MMS로 처리합니다 (목록에 없는 송신자의 메일은 무시됩니다)') ?></label>
											</dd>
											<dd>
												<input type="radio" id="pop3allowlist" name="pop3allowonly" value="0" <?php echo $pop3mmsallowonly ? '':'checked="checked"' ?> />
												<label for="pop3allowlist"><?php echo _t('아래 목록의 송신자에게서 온 메일을 MMS로 추가 처리합니다 (MMS 헤더가 있는 메일은 목록과 무관하게 항상 처리됩니다)'); ?></label>
											</dd>
											<dd>
												<input type="text" maxlength="512" name="pop3allow" value="<?php echo htmlspecialchars($pop3mmsallow, ENT_QUOTES, 'UTF-8');?>" style="width:90%" />
											</dd>
											<dd>
												<?php echo _t('전화번호 또는 이메일 주소를 쉼표(,)나 공백으로 구분하여 입력합니다. 예: 01012345678, user@example.com') ?>
											</dd>
										</dl>
									<div class="button-box">
										<input type="submit" class="save-button input-button wide-button" value="<?php echo _t('저장하기');?>"  />
									</div>
								</div>
							</form>
							<h2 class="caption"><span class="main-text"><?php echo _t('MMS 메시지 테스트');?></span></h2>
								<div id="editor-section" class="section">
									<dl id="formatter-line" class="line">
										<dt><span class="label"><?php echo _t('명령');?></span></dt>
										<dd>
											<input type="button" class="save-button input-button wide-button" value="<?php echo _t('로그보기');?>"
												onclick="document.getElementById('pop3_debug').src='<?php echo $blogURL?>/plugin/moblog/check?check=1&amp;rnd='+((new Date()).getTime())" />
											<input type="button" class="save-button input-button wide-button" value="<?php echo _t('시험하기');?>"
												onclick="document.getElementById('pop3_debug').src='<?php echo $blogURL?>/plugin/moblog/check?rnd='+((new Date()).getTime())" />
											<input type="button" class="input-button wide-button" value="<?php echo _t('로그삭제');?>"
												style="background:#c0392b;color:#fff;border-color:#922b21;"
												onclick="if(!confirm('<?php echo htmlspecialchars(_t('로그를 삭제하시겠습니까?'), ENT_QUOTES, 'UTF-8');?>'))return;var f=document.getElementById('pop3_debug');f.src='<?php echo $blogURL?>/plugin/moblog/check?clearlog=1&amp;rnd='+((new Date()).getTime());" />
										</dd>
									</dl>
								</div>
								<iframe src="about:blank" class="debug_message" id="pop3_debug" style="width:100%; height:400px">
								</iframe>
<?php endif ?>
						</div>
<?php
}
?>
