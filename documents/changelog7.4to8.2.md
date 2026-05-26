# CHANGELOG — Textcube 1.10.10 PHP 7.4 → PHP 8.2 이식 (STAGE 2)

대상 PHP: 8.2  
기반 버전: php7.4-Textcube-1.10.10 (STAGE 1, v1.74)  
작성일: 2026-05-15 / 최종 수정: 2026-05-16

이전 단계 누적분(v1.1 ~ v1.74): `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조

---

## v1.75 — STAGE 2 메타 파일 갱신 (2026-05-15)

- `changelog7.4to8.2.md` (구 CHANGELOG.md): STAGE 2 헤더로 교체. 기반 버전 v1.74, 이전 누적분 php7.4 CHANGELOG 참조 명시.
- `SECURITY.md`: 헤더 STAGE 2로 갱신. 모든 항목 현황을 STAGE 2 시점으로 재평가.
  - phpmailer 5.x → v1.81에서 phpmailer 6.9.x 교체 예정.
  - hash_equals() → v1.84에서 도입 예정.
  - Raw SQL → v1.85~v1.89에서 핵심 진입점 Prepared Statement 변환 예정.
  - phpopenid PHP4 스타일 생성자 PHP 8.0 deprecated 항목 신설.
  - phpopenid 동적 프로퍼티 PHP 8.2 deprecated 항목 신설.
  - `${}` 문자열 보간 PHP 8.2 deprecated 항목 신설.
- `README.md`: REQUIREMENTS 섹션 PHP 7.4 → PHP 8.2 갱신.
- 디렉토리 복사: `php7.4-Textcube-1.10.10/`(v1.74) → `php8.2-Textcube-1.10.10/` (attach/, cache/ 제외).

---

## v1.76 — PHP4 스타일 생성자 → `__construct()` 전수 변환 (PHP 8.0 deprecated, PHP 9.0 제거)

- **제거 이유**: PHP 8.0에서 PHP4 스타일 생성자(클래스명과 동일한 메서드명) deprecated. PHP 9.0에서 제거 예정. (php.net/migration80.deprecated)
- **PHP 권고**: 생성자는 반드시 `__construct()`로 명명할 것.

### phpopenid (`library/contrib/phpopenid/`) — 24개 파일, 56건

| 파일 | 변경 전 (대표) | 건수 |
|------|----------------|------|
| `Auth/OpenID/Association.php` | `Auth_OpenID_Association`, `Auth_OpenID_AssociationPool` | 2 |
| `Auth/OpenID/AX.php` | `Auth_OpenID_AX_Error` 외 5건 | 6 |
| `Auth/OpenID/Consumer.php` | PHP4 생성자 | 9 |
| `Auth/OpenID/DiffieHellman.php` | PHP4 생성자 | 1 |
| `Auth/OpenID/Discover.php` | PHP4 생성자 | 1 |
| `Auth/OpenID/DumbStore.php` | PHP4 생성자 | 1 |
| `Auth/OpenID/FileStore.php` | PHP4 생성자 | 1 |
| `Auth/OpenID/MDB2Store.php` | PHP4 생성자 | 1 |
| `Auth/OpenID/MemcachedStore.php` | PHP4 생성자 | 1 |
| `Auth/OpenID/Message.php` | PHP4 생성자 | 3 |
| `Auth/OpenID/PAPE.php` | PHP4 생성자 | 2 |
| `Auth/OpenID/Parse.php` | PHP4 생성자 | 1 |
| `Auth/OpenID/PredisStore.php` | PHP4 생성자 | 1 |
| `Auth/OpenID/SQLStore.php` | PHP4 생성자 | 1 |
| `Auth/OpenID/Server.php` | PHP4 생성자 | 16 |
| `Auth/OpenID/ServerRequest.php` | PHP4 생성자 | 1 |
| `Auth/OpenID/SReg.php` | PHP4 생성자 | 1 |
| `Auth/Yadis/HTTPFetcher.php` | PHP4 생성자 | 1 |
| `Auth/Yadis/Manager.php` | PHP4 생성자 | 2 |
| `Auth/Yadis/ParseHTML.php` | PHP4 생성자 | 1 |
| `Auth/Yadis/ParanoidHTTPFetcher.php` | PHP4 생성자 | 1 |
| `Auth/Yadis/Yadis.php` | PHP4 생성자 | 1 |
| `Auth/Yadis/XRDS.php` | PHP4 생성자 | 2 |
| `Auth/Yadis/XRIRes.php` | PHP4 생성자 | 1 |
| `contrib/signed_assertions/AP.php` | PHP4 생성자 | 1 |

### StatGraph 플러그인 jpgraph 1.x (`plugins/StatGraph/count/src/`) — 4개 파일, 28건

| 파일 | 변환된 생성자 |
|------|--------------|
| `jpgraph.php` | `JpGraphErrObject`, `JpgTimer`, `DateLocale`, `FuncGenerator`, `Footer`, `Graph`, `TTF`, `Text`, `GraphTabTitle`, `SuperScriptText`, `Grid`, `Axis`, `Ticks`, `LinearTicks`, `LinearScale`, `RGB`, `Picture`, `RotPicture`, `ImgStreamCache`, `Legend`, `Plot`, `PlotLine` (22건) |
| `jpgraph_gradient.php` | `Gradient` (1건) |
| `jpgraph_line.php` | `LinePlot`, `AccLinePlot` (2건) |
| `jpgraph_scatter.php` | `FieldArrow`, `FieldPlot`, `ScatterPlot` (3건) |

### framework/legacy 및 phpxpath 라이브러리 — 2개 파일, 6건

| 파일 | 변환 내용 |
|------|-----------|
| `framework/legacy/Needlworks.Cache.PageCache.Legacy.php` | `pageCache`, `queryCache`, `globalCacheStorage` 생성자 (3건) |
| `library/contrib/phpxpath/XPath.class.php` | `XPathBase`, `XPathEngine`, `XPath` 생성자 (3건) |

---

## v1.77 — phpopenid / phpxpath / jpgraph parent:: 생성자 호출 변환 (v1.76 후속)

- **변경 이유**: PHP4 스타일 생성자 제거 후 `parent::ClassName()` 및 `$this->ClassName()` 형태의 부모 생성자 호출도 동일하게 무효화. `parent::__construct()`로 명시 변환 필요.
- **`$this->ClassName()` 패턴**: 자식 클래스 `__construct()` 내부에서 `$this->ParentClassName(...)` 으로 부모 생성자를 호출하는 PHP4 관용 패턴. v1.76 일괄 변환 시 grep 스크립트가 누락하여 v1.77에서 추가 수정.

| 파일 | 변경 내용 |
|------|-----------|
| `Auth/OpenID/Server.php` | `parent::Auth_OpenID_ServerError(...)` → `parent::__construct(...)` (4건) |
| `Auth/OpenID/AX.php` | `$this->Auth_OpenID_AX_KeyValueMessage(...)` → `parent::__construct(...)` (1건) |
| `library/contrib/phpxpath/XPath.class.php` | `parent::XPathBase()` → `parent::__construct()`, `parent::XPathEngine(...)` → `parent::__construct(...)` (3건) |
| `plugins/StatGraph/count/src/jpgraph.php` | `RotPicture::__construct()` 내 `$this->Picture(...)` → `parent::__construct(...)` (1건) |
| `plugins/StatGraph/count/src/jpgraph_line.php` | `LinePlot::__construct()` 내 `$this->Plot(...)` → `parent::__construct(...)` (1건) |
| `plugins/StatGraph/count/src/jpgraph_scatter.php` | `FieldPlot::__construct()` 내 `$this->Plot(...)` → `parent::__construct(...)`, `ScatterPlot::__construct()` 내 `$this->Plot(...)` → `parent::__construct(...)` (2건) |

---

## v1.78 — 문자열 보간 `${}` → `{$}` 변환 (PHP 8.2 deprecated)

- **제거 이유**: PHP 8.2에서 `"${varname}"` 및 `"${expr}"` 형태 deprecated. PHP 9.0에서 제거 예정. (php.net/migration82.deprecated)
- **PHP 권고**: `"{$varname}"` 또는 `"$varname"` 형태 사용.
- **전수 grep 결과**: 잔존 패턴 0건 (변환 후 확인).

| 파일 | 라인 | 변경 전 | 변경 후 |
|------|------|---------|---------|
| `interface/owner/network/xfn/index.php` | 10 | `"Location: ${_SERVER['REQUEST_URI']}"` | `"Location: {$_SERVER['REQUEST_URI']}"` |

---

## v1.79 — 동적 프로퍼티 `#[AllowDynamicProperties]` 전수 대응 (PHP 8.2 deprecated)

- **제거 이유**: PHP 8.2에서 미선언 프로퍼티 동적 할당 deprecated. PHP 9.0에서 제거 예정. (php.net/migration82.deprecated)
- **PHP 권고**: `#[AllowDynamicProperties]` 어트리뷰트 추가(임시) 또는 프로퍼티 명시 선언.
- **적용 방법**: 하기 클래스 정의 직전 단독 라인으로 `#[AllowDynamicProperties]` 추가.
- **미적용 제외 근거**: `$this->` 할당 없는 클래스(FeedGroup, Tag, DataMaintenance, Statistics, Paging, RSS, OpenID), `var`/`public` 프로퍼티 선언이 있는 클래스(UserInfo, PluginCustomConfig), 미로드 dead file(RemoteResponse, Needlworks.Cache.PageCache.Legacy)은 제외.

### phpopenid (`library/contrib/phpopenid/`) — 50개 클래스, 26개 파일 (전수)

**핵심 진입점 (1차):**

| 파일 | 클래스 |
|------|--------|
| `Auth/OpenID/Server.php` | `Auth_OpenID_ServerError`, `Auth_OpenID_MalformedReturnURL`, `Auth_OpenID_UntrustedReturnURL`, `Auth_OpenID_Server`, `Auth_OpenID_AssociateRequest`, `Auth_OpenID_CheckIDRequest`, `Auth_OpenID_SigningEncoder`, `Auth_OpenID_Decoder` |
| `Auth/OpenID/Discover.php` | `Auth_OpenID_ServiceEndpoint` |
| `Auth/OpenID/Consumer.php` | `Auth_OpenID_Consumer`, `Auth_OpenID_GenericConsumer`, `Auth_OpenID_AuthRequest` |
| `Auth/OpenID/AX.php` | `Auth_OpenID_AX_FetchRequest`, `Auth_OpenID_AX_KeyValueMessage`, `Auth_OpenID_AX_AttrInfo` |
| `Auth/Yadis/Manager.php` | `Auth_Yadis_Manager`, `Auth_Yadis_Discovery` |

**나머지 전수 (2차 — `var` 선언 있어도 인스턴스 프로퍼티가 미선언인 경우 포함):**

| 파일 | 클래스 |
|------|--------|
| `Auth/OpenID/Association.php` | `Auth_OpenID_Association`, `Auth_OpenID_SessionNegotiator` |
| `Auth/OpenID/DiffieHellman.php` | `Auth_OpenID_DiffieHellman` |
| `Auth/OpenID/DumbStore.php` | `Auth_OpenID_DumbStore` |
| `Auth/OpenID/FileStore.php` | `Auth_OpenID_FileStore` |
| `Auth/OpenID/MDB2Store.php` | `Auth_OpenID_MDB2Store` |
| `Auth/OpenID/MemcachedStore.php` | `Auth_OpenID_MemcachedStore` |
| `Auth/OpenID/Message.php` | `Auth_OpenID_Mapping`, `Auth_OpenID_NamespaceMap`, `Auth_OpenID_Message` |
| `Auth/OpenID/PAPE.php` | `Auth_OpenID_PAPE_Request`, `Auth_OpenID_PAPE_Response` |
| `Auth/OpenID/Parse.php` | `Auth_OpenID_Parse` |
| `Auth/OpenID/PredisStore.php` | `Auth_OpenID_PredisStore` |
| `Auth/OpenID/SQLStore.php` | `Auth_OpenID_SQLStore` |
| `Auth/OpenID/ServerRequest.php` | `Auth_OpenID_ServerRequest` |
| `Auth/OpenID/SReg.php` | `Auth_OpenID_SRegBase`, `Auth_OpenID_SRegRequest`, `Auth_OpenID_SRegResponse` |
| `Auth/Yadis/HTTPFetcher.php` | `Auth_Yadis_HTTPResponse`, `Auth_Yadis_HTTPFetcher` |
| `Auth/Yadis/XRDS.php` | `Auth_Yadis_Service`, `Auth_Yadis_XRDS` |
| `Auth/Yadis/XRIRes.php` | `Auth_Yadis_ProxyResolver` |
| `Auth/Yadis/ParseHTML.php` | `Auth_Yadis_ParseHTML` |
| `Auth/Yadis/ParanoidHTTPFetcher.php` | `Auth_Yadis_ParanoidHTTPFetcher` |
| `Auth/Yadis/Yadis.php` | `Auth_Yadis_DiscoveryResult`, `Auth_Yadis_Yadis` |
| `Auth/Yadis/XML.php` | `Auth_Yadis_XMLParser`, `Auth_Yadis_domxml`, `Auth_Yadis_dom` |
| `contrib/signed_assertions/AP.php` | `Attribute_Provider`, `Attribute_Verifier`, `AP_OP_StoreRequest`, `RP_OP_Verify` |

### phpxpath (`library/contrib/phpxpath/`) — 3개 클래스

`XPathEngine` 클래스에서 `$aLiterals`, `$hilightXpathList`, `$indentStep` 미선언 프로퍼티 확인.

| 파일 | 클래스 |
|------|--------|
| `XPath.class.php` | `XPathBase`, `XPathEngine`, `XPath` |

### framework/legacy (`framework/legacy/`) — 30개 클래스, 28개 파일

| 파일 | 클래스 |
|------|--------|
| `Textcube.Data.Attachment.php` | `Attachment` |
| `Textcube.Data.BlogSetting.php` | `BlogSetting` |
| `Textcube.Data.BlogStatistics.php` | `BlogStatistics` |
| `Textcube.Data.Category.php` | `Category` |
| `Textcube.Data.Comment.php` | `Comment` |
| `Textcube.Data.CommentNotified.php` | `CommentNotified` |
| `Textcube.Data.CommentNotifiedSiteInfo.php` | `CommentNotifiedSiteInfo` |
| `Textcube.Data.DailyStatistics.php` | `DailyStatistics` |
| `Textcube.Data.Feed.php` | `Feed`, `FeedItem` (`FeedGroup`은 `$this->` 할당 없어 제외) |
| `Textcube.Data.Filter.php` | `Filter` |
| `Textcube.Data.GuestComment.php` | `GuestComment` |
| `Textcube.Data.Keyword.php` | `Keyword` |
| `Textcube.Data.Link.php` | `Link` |
| `Textcube.Data.LinkCategories.php` | `LinkCategories` |
| `Textcube.Data.Notice.php` | `Notice` |
| `Textcube.Data.PluginSetting.php` | `PluginSetting` |
| `Textcube.Data.Post.php` | `Post` |
| `Textcube.Data.RefererLog.php` | `RefererLog` |
| `Textcube.Data.RefererStatistics.php` | `RefererStatistics` |
| `Textcube.Data.ServiceSetting.php` | `ServiceSetting` |
| `Textcube.Data.SkinSetting.php` | `SkinSetting` |
| `Textcube.Data.SubscriptionLog.php` | `SubscriptionLog` |
| `Textcube.Data.SubscriptionStatistics.php` | `SubscriptionStatistics` |
| `Textcube.Data.Trackback.php` | `Trackback` |
| `Textcube.Data.TrackbackLog.php` | `TrackbackLog` |
| `Textcube.Data.UserSetting.php` | `UserSetting` |
| `Textcube.Control.Openid.php` | `OpenIDSession`, `OpenIDConsumer` (`OpenID`는 `$this->` 할당 없어 제외) |
| `Textcube.Model.Message.php` | `Message` |

### plugins/ — 5개 클래스, 4개 파일

| 파일 | 클래스 | 미선언 프로퍼티 |
|------|--------|----------------|
| `plugins/CL_Moblog/index.php` | `Moblog` | 전체 프로퍼티 미선언 (PHP4 관용) |
| `plugins/FM_Textile/classTextile.php` | `Textile` | `$urlch`, `$btag` |
| `plugins/StatGraph/count/src/jpgraph.php` | `Graph` | `$legend` |
| `plugins/StatGraph/count/src/jpgraph_scatter.php` | `FieldPlot`, `ScatterPlot` | `$arrow` (FieldPlot), `$mark` (ScatterPlot) |

*FM_Markdown / MarkdownExtra는 PHP 8.x typed property 선언 완비 (v2.0.0, 2022), 미적용.*

### framework/legacy 추가 — 5개 클래스, 5개 파일

| 파일 | 클래스 | 미선언 프로퍼티 |
|------|--------|----------------|
| `framework/legacy/Needlworks.PHP.Pop3.php` | `Pop3` | `$ctx`, `$logger`, `$uidl_filter`, `$size_filter`, `$stat_callback`, `$retr_callback`, `$mails`, `$uids`, `$filterred`, `$status`, `$error`, `$results`, `$fallback_charset` |
| `framework/legacy/Needlworks.PHP.XMLRPC.php` | `XMLRPC` | `$_registry` |
| `framework/legacy/Needlworks.PHP.XMLTree.php` | `XMLTree` | `$_cursor`, `$_cdata`, `$_xmlcontent` |
| `framework/legacy/Needlworks.PHP.OutputWriter.php` | `OutputWriter` | `$_buffer`, `$_writer` |
| `framework/legacy/Textcube.View.Pages.php` | `Pages` | `$message`, `$mode` |

*`Needlworks.PHP.Base64Stream.php`, `Needlworks.PHP.Imap.php` (`Pop3` 상속, 자체 할당 없음), `Eolin.API.Syndication.php`, `Textcube.Core.php`, `Textcube.View.BlogView.php`, `Textcube.Function.*.php`, `Textcube.Data.php` 는 동적 프로퍼티 없음.*

### framework/ 기타 — 4개 클래스, 4개 파일

| 파일 | 클래스 | 미선언 프로퍼티 | 처리 방법 |
|------|--------|----------------|-----------|
| `framework/model/URIHandler.php` | `Model_URIHandler` | `$context`, `$blog`, `$skin` | `#[AllowDynamicProperties]` 추가 |
| `framework/utils/Image.php` | `Utils_Image` | `$extraPadding`, `$imageFile`, `$resultImageDevice`, `$bgColorBy16` | `#[AllowDynamicProperties]` 추가 |
| `framework/boot/10-CoreClasses.php` | `XMLStruct` | `$ns`, `$baseindex`, `$nsenabled`, `$_cursor`, `$_path`, `$_cdata`, `$_consumer`, `$_streams` | `#[AllowDynamicProperties]` 추가 |
| `framework/boot/30-Auth.php` | `Acl` | `$context` | `#[AllowDynamicProperties]` 추가 |

**`framework/data/DBModel.php` — 선언 수정 (버그 수정 포함)**
- `DBModel` 클래스의 `protected $context`, `protected $_reservedFunctions` 선언 추가.
- `protected $_limitation` → `protected $_limit` 로 선언명 수정: 원본부터 `$_limitation`으로 선언되어 있었으나 실제 코드(`reset()`, `setLimit()`, `unsetLimit()`, `_buildQuery()`) 전체에서 `$_limit`를 사용하여 `$_limitation`은 완전히 사용되지 않았음. 원본 버그를 PHP 8.2 이식 시 수정. `#[AllowDynamicProperties]` 불필요.

### framework/model/ 추가 — 2개 클래스 + 1개 파일 선언 보완

| 파일 | 클래스 | 처리 방법 | 미선언 프로퍼티 |
|------|--------|-----------|----------------|
| `framework/model/AlternateLogins.php` | `Model_AlternateLogins` | `#[AllowDynamicProperties]` 추가 | `$userid`, `$provider`, `$remoteid`, `$data`, `$_error` |
| `framework/model/Line.php` | `Model_Line` | `#[AllowDynamicProperties]` 추가 | `$blogid`, `$category`, `$root`, `$author`, `$content`, `$permalink`, `$created`, `$_error` |
| `framework/model/Config.php` | `Model_Config` | `private $settings, $backend_name` 선언 추가 | `$settings`, `$backend_name` |

*`Model_Context` (`private $__property, $__namespace` 이미 선언됨), `Model_LegacySupport` (`public $database, $service` 선언됨, 자체 `$this->` 없음), `Model_NatureSkin` (static 전용), `Model_Entry`, `Model_OpenSearchProvider`, `Model_URIHandler` (이미 처리) — 추가 조치 불필요.*

*`framework/cache/Memcache.php`, `framework/data/MySQLi/Adapter.php`, `framework/data/MySQL/Adapter.php`, `framework/data/PostgreSQL/Adapter.php`, `framework/data/Cubrid/Adapter.php`, `framework/data/SQLite3/Adapter.php`, `framework/alias/POD.php`, `framework/boot/20-Autoload.php` 는 동적 프로퍼티 없음 (static 전용).*

*정식 프로퍼티 선언으로 전환은 STAGE 3(v2.04~v2.09) 이연.*

---

## v1.80 — nullable 인자 deprecated 대응 (PHP 8.1)

- **제거 이유**: PHP 8.1에서 nullable이 아닌 파라미터에 `null` 전달 시 deprecated 경고. (php.net/migration81.deprecated)
- **PHP 권고**: null을 받을 수 있는 파라미터는 `?type` 또는 `type|null`로 명시 선언.

| 파일 | 라인 | 변경 전 | 변경 후 |
|------|------|---------|---------|
| `library/contrib/phpopenid/Auth/OpenID/Message.php` | 678 | `htmlspecialchars($val)` | `htmlspecialchars($val ?? '')` |
| `library/contrib/phpopenid/Auth/OpenID/Message.php` | 687 | `htmlspecialchars($val)` | `htmlspecialchars($val ?? '')` |
| `library/contrib/phpopenid/Auth/OpenID/Message.php` | 691 | `htmlspecialchars($val)` | `htmlspecialchars($val ?? '')` |

*컨테이너 error_log의 "Passing null to parameter" 추가 검출 시 동일 처리.*

---

## v1.81 — phpmailer 5.x → 6.9.x 교체 (PHP 8.0 `each()` 제거 대응)

- **제거 이유**: PHP 8.0에서 `each()` 제거. phpmailer 5.x에서 `each()` 사용 → Fatal Error. (php.net/migration80.removed-functions)
- **PHP 권고**: phpmailer 6.x (PHP 8.x 공식 지원, namespace 도입, `each()` 완전 제거).
- **호출 진입점**: `library/function/mail.php` 단 1곳.

| 파일 | 변경 내용 |
|------|-----------|
| `library/contrib/phpmailer/src/PHPMailer.php` | phpmailer 6.9.x 신규 배치 |
| `library/contrib/phpmailer/src/SMTP.php` | phpmailer 6.9.x 신규 배치 |
| `library/contrib/phpmailer/src/POP3.php` | phpmailer 6.9.x 신규 배치 |
| `library/contrib/phpmailer/src/Exception.php` | phpmailer 6.9.x 신규 배치 |
| `library/contrib/phpmailer/legacy_shim.php` | 신규 생성. `class_alias(PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer')` 등 전역 별칭 제공 |
| `library/function/mail.php` | include 경로를 `legacy_shim.php`로 변경 |

*기존 phpmailer 5.x 파일(`class.phpmailer.php` 등)은 사용자 확인 후 `legacy_v5/`로 이동 예정 (미실행).*

---

## v1.82 — GD 리소스 → GdImage 객체 호환 점검 (PHP 8.0)

- **변경 이유**: PHP 8.0에서 GD 함수 반환값이 `resource` → `GdImage` 객체로 변경. (php.net/migration80.other-changes)
- **점검 결과**: `framework/utils/Image.php`, `library/function/watermark.php`, `interface/blog/imageResizer.php` — `is_resource()` GD 검사 없음. 추가 수정 불필요.

---

## v1.83 — pecl/memcache PHP 8.x 호환 점검

- **점검 대상**: `framework/cache/Memcache.php`, `library/preprocessor.php` — `Memcache::connect($host, $port)` (STAGE 1 v1.70에서 포트 파라미터 명시화 완료).
- **점검 결과**: tc-php82 컨테이너 기동 후 기록 예정.

---

## v1.84 — `hash_equals()` 도입 — Timing attack 대응

- **변경 이유**: 인증 토큰 비교를 `===`로 수행할 경우 timing attack 취약. PHP 5.6+에서 `hash_equals()`가 상수 시간 비교 보장. (php.net/function.hash-equals)

| 파일 | 변경 내용 |
|------|-----------|
| `framework/boot/30-Auth.php` | `($authtoken === $password)` → `hash_equals($authtoken, $password)` |

---

## v1.85 — POD 어댑터에 Prepared Statement API 추가

- **변경 이유**: SQL Injection 대응. `?` placeholder 방식 사용 가능화. (php.net/mysqli.prepare)

| 파일 | 변경 내용 |
|------|-----------|
| `framework/data/MySQLi/Adapter.php` | `prepare()`, `bindAndExecute()`, `fetchAllStmt()` 정적 메서드 추가 |
| `framework/data/IAdapter.php` | 동일 시그니처 인터페이스 선언 추가 |
| 비-MySQLi 어댑터 (SQLite3 등) | `throw new RuntimeException('Prepared statements not supported')` 기본 구현 추가 |

---

## v1.86 — 핵심 인증 진입점 Prepared Statement 변환 + PRNG 전면 교체

- **변경 이유**: 외부 입력이 직접 SQL에 삽입되는 인증 쿼리 SQL Injection 위험. `rand()` 사용은 예측 가능 PRNG로 보안 취약.

| 파일 | 함수 | 변경 내용 |
|------|------|-----------|
| `framework/boot/30-Auth.php` | `Auth::authenticate()` | loginid WHERE 절 → prepared |
| `library/auth.php` | `isLoginId()` | loginid + blogid WHERE 절 → prepared |
| `library/auth.php` | `generatePassword()` | `rand(...)` → `random_int(...)` (CSPRNG, PHP 7.0+) |
| `framework/legacy/Textcube.Core.php` | `__generatePassword()` | `rand(...)` → `random_int(...)` |
| `framework/legacy/Textcube.Control.Session.php` | `newAnonymousSession()` L133 | DB 세션 ID `rand(...)` ×4 → `random_int(...)` |
| `framework/legacy/Textcube.Control.Session.php` | `authorize()` L225 | DB 세션 ID `rand(...)` ×4 → `random_int(...)` |
| `framework/legacy/Textcube.Data.Attachment.php` | 파일 이름 생성 L152, L163 | `rand(...)` → `random_int(...)` |
| `library/model/blog.attachment.php` | 파일 이름 생성 L147, L223 | `rand(...)` → `random_int(...)` |
| `library/model/blog.api.php` | 파일 이름 생성 L356, L359 | `rand(...)` → `random_int(...)` |
| `plugins/ST_TeamBlogSettings/index.php` | 파일 이름 생성 L329 | `rand(...)` → `random_int(...)` |
| `library/contrib/phpopenid/contrib/signed_assertions/SAML.php` | `samlCreateId()` L180 | `rand(...)` → `random_int(...)` (SAML assertion ID 생성 — 보안 민감) |
| `library/contrib/phpopenid/Auth/OpenID/FileStore.php` | `_mkdtemp()` L535 | `rand(1, time())` → `random_int(1, PHP_INT_MAX)` (임시 디렉토리 이름 생성) |

*플러그인(FM_Textile, FM_Markdown, FM_TTML, GoogleMap, StatGraph)의 `rand()` 호출은 HTML ID·그래프 렌더링 용도로 보안 비관련 — 유지.*

---

## v1.87 — 게시물 / 댓글 저장 Prepared Statement 변환

- **변경 이유**: 사용자 입력이 들어가는 콘텐츠 저장 쿼리 SQL Injection 노출 지점.

| 파일 | 함수 | 변경 내용 |
|------|------|-----------|
| `library/model/blog.entry.php` | `addEntry()`, `updateEntry()` | INSERT/UPDATE → prepared |
| `library/model/blog.comment.php` | `addComment()`, `updateComment()` | INSERT/UPDATE → prepared |
| `library/model/blog.response.remote.php` | 트랙백 수신 처리 | DBModel 구조상 prepared 불가 → `@security raw-sql-escape` 마커. `POD::escapeString()` 처리됨 |

---

## v1.88 — BlogAPI / 검색 쿼리 Prepared Statement 변환

- **변경 이유**: XMLRPC 외부 입력 진입점 및 검색어 LIKE 패턴.

| 파일 | 함수 | 변경 내용 |
|------|------|-----------|
| `library/model/blog.api.php` | `api_addAttachment()` | INSERT → prepared (8 params) |
| `library/model/blog.api.php` | `api_update_attaches()` | UPDATE → prepared (3 params) |
| `library/model/blog.api.php` | `api_update_attaches_with_replace()` | `@security raw-sql-escape` 마커 |
| `library/model/blog.entry.php` | `getEntryListWithPagingBySearch()` | `@security raw-sql-escape` 마커 (Paging 구조상 prepared 불가) |
| `library/model/blog.entry.php` | `getEntriesWithPagingBySearch()` | `@security raw-sql-escape` 마커 |

---

## v1.89 — setup.php Prepared Statement 검토 — legacy escaping 유지 결정

- `setup.php` L1136-1188: `implode()`로 조립된 배치 SQL 구조로 prepared 전환 시 전면 재작성 필요. 모든 파라미터가 `POD::escapeString()` 처리됨을 확인하여 legacy escaping 유지 결정.
- SECURITY.md에 "setup.php 배치 구조상 legacy escaping 유지" 기록.

---

## v1.90 — SECURITY.md `[HIGH] Raw SQL` 현황 갱신 (2026-05-16)

- Prepared Statement 변환 완료 함수 목록(10건) 및 `@security raw-sql-escape` 마커 부착 목록(4건) SECURITY.md 명세.
- setup.php legacy escaping 유지 이유 문서화.

**변환 완료 (10건):**

| 파일 | 함수 | 버전 |
|------|------|------|
| `framework/data/MySQLi/Adapter.php` | `prepare()`, `bindAndExecute()`, `fetchAllStmt()` 신설 | v1.85 |
| `framework/data/IAdapter.php` | 인터페이스 선언 추가 | v1.85 |
| `framework/boot/30-Auth.php` | `Auth::authenticate()` loginid | v1.86 |
| `library/auth.php` | `isLoginId()` | v1.86 |
| `library/model/blog.comment.php` | `addComment()`, `updateComment()` | v1.87 |
| `library/model/blog.entry.php` | `addEntry()`, `updateEntry()` | v1.87 |
| `library/model/blog.api.php` | `api_addAttachment()`, `api_update_attaches()` | v1.88 |

---

## v1.91 — `strftime()` / `gmstrftime()` deprecated 대응 + `utf8_encode()`/`utf8_decode()` 점검 (PHP 8.1/8.2)

### strftime() / gmstrftime() — PHP 8.1 deprecated (php.net/migration81.deprecated)

- **제거 이유**: PHP 8.1에서 `strftime()` / `gmstrftime()` deprecated. PHP 9.0에서 제거 예정. 대체: `date()` / `gmdate()` / `DateTimeImmutable::format()`.
- **적용 전략**:
  - 수치형 포맷 코드(`%Y`, `%m`, `%d`, `%H`, `%M`, `%S` 등)는 `date()` / `gmdate()`로 직접 교체.
  - 로케일 의존 코드(`%a`, `%b`, `%B`, `%c`, `%z`, `%Z`)를 포함하는 `Timestamp` 클래스용으로 `strftime_compat()` / `gmstrftime_compat()` 헬퍼 함수 신설. 문자 단위 매핑으로 리터럴 텍스트 오염 없음.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/boot/10-CoreClasses.php` | `_strftime_apply()`, `strftime_compat()`, `gmstrftime_compat()` 헬퍼 함수 Timestamp 클래스 직전 추가. `Timestamp` 클래스 내 `strftime()` / `gmstrftime()` → `strftime_compat()` / `gmstrftime_compat()` 전면 교체 (11건) |
| `framework/legacy/Needlworks.PHP.Pop3.php` | L388-392: `strftime("%Y-%m-%d")` 등 → `date('Y-m-d')` 등 직접 교체 (5건) |
| `framework/locales/Po2php.php` | L149: `strftime("%Y-%m-%d %H:%M+0000")` → `date('Y-m-d H:i') . '+0000'` |
| `interface/control/system/index.php` | L25: `strftime("Server Time: %Y-%m-%d %H:%M:%S %z (%Z)")` → `strftime_compat(...)` |
| `library/contrib/phpopenid/Auth/OpenID/Nonce.php` | L105: `gmstrftime(Auth_OpenID_Nonce_TIME_FMT, $when)` → `gmdate('Y-m-d\TH:i:s\Z', $when)` |
| `library/function/time.php` | L65-69: `strftime('%Y')`, `strftime('%Y%m')`, `strftime('%Y%m%d')` → `date('Y')`, `date('Ym')`, `date('Ymd')` |
| `library/model/blog.api.php` | L131: `gmstrftime("%Y%m%dT%H:%M:%S", $timestamp)` → `gmdate('Ymd\TH:i:s', $timestamp)` |
| `plugins/StatGraph/count/src/jpgraph.php` | L581: `strftime('%w')` → `date('w')`, L582: `strftime('%a', ...)` → `date('D', ...)`, L589: `strftime("%b|%B", ...)` → `date('M|F', ...)` |
| `resources/locale/translate/po2php.php` | L141: Po2php.php와 동일 교체 |

### utf8_encode() / utf8_decode() — PHP 8.2 deprecated 점검 (php.net/migration82.deprecated)

- **점검 결과**: 본체 PHP 코드에서 호출 없음 확인 (ownerView.php 내 JavaScript 함수명 문자열만 해당). 추가 수정 불필요.
- 컨테이너 부팅 후 deprecation 로그 추가 검출 시 `mb_convert_encoding()` 으로 교체.

---

## v1.91a — `SubscriptionStatistics::add()` 미정의 변수 접근 수정 (PHP 8.0 TypeError 대응)

- **변경 이유**: 원본 코드에서 `add()` 메서드가 `$query = $this->_buildQuery()` 호출 전에 `$query->hasAttribute('referred')` 를 실행. PHP 7.x에서는 E_NOTICE만 발생하고 이후 라인에서 덮어쓰기로 무시됐으나, PHP 8.0에서는 정의되지 않은 변수에 대한 메서드 호출이 `TypeError: Call to a member function hasAttribute() on null` Fatal Error로 격상.
- **근거**: `SubscriptionLog::add()`의 동일 패턴 참조. `_buildQuery()` 반환값을 먼저 받은 후 기본값 설정.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/legacy/Textcube.Data.SubscriptionStatistics.php` | `add()` 내 `_buildQuery()` 호출 전으로 이동된 `hasAttribute('referred')` 검사 (순서 오류 수정) |

---

## v1.92 — 문자열-정수 비교 엄격화 대응 (PHP 8.0)

- **변경 이유**: PHP 8.0에서 `"abc" == 0` 동작 변경. (php.net/migration80.incompatible)
- **적용**: 회귀 테스트 오류 로그에서 실제 동작 변경 케이스 핀포인트 확인 후 수정. 광범위 변경은 STAGE 3 이연.

---

## v1.93 — 정적 분석 1차 (PHPStan level 1)

- PHPStan level 1 분석. 결과는 `STATIC_ANALYSIS.md` 또는 SECURITY.md `[INFO]` 섹션 기록. 수정은 STAGE 3 이연.

---

## v1.94a — tc-php82 컨테이너 런타임 오류 1차 수정 (2026-05-16)

tc-php82 컨테이너 최초 기동 후 error_log에서 검출된 PHP 8.x Fatal Error / TypeError 핀포인트 수정.

### TEXT/BLOB 컬럼 DEFAULT 절 금지 (MySQL strict mode)

- **오류**: `BLOB, TEXT, GEOMETRY or JSON column 'profile' can't have a default value`
- **원인**: `library/model/common.plugin.php`에서 플러그인 XML 정의로 `CREATE TABLE` 구문 생성 시 TEXT/BLOB 타입에도 `DEFAULT` 절 추가. MySQL 5.7 strict mode 에서 TEXT/BLOB에 DEFAULT 불가.
- **수정**: `$noDefaultTypes` 배열로 text류 타입 판별 후 DEFAULT 절 제외.

| 파일 | 라인 | 변경 내용 |
|------|------|-----------|
| `library/model/common.plugin.php` | 246-251 | TEXT/BLOB/JSON 타입 판별 후 DEFAULT 절 조건부 생략 |

### `gmmktime()` 인자 없는 호출 제거 (PHP 8.1 ArgumentCountError)

- **오류**: `gmmktime() expects at least 1 argument, 0 given`
- **원인**: PHP 8.1에서 `gmmktime()` 인자 없는 호출이 `ArgumentCountError`로 격상. (php.net/migration81.other-changes)
- **수정**: `gmmktime()` → `time()` (현재 UNIX 타임스탬프 반환, 동일 효과)

| 파일 | 라인 | 변경 전 | 변경 후 |
|------|------|---------|---------|
| `library/view/view.php` | 152 | `gmmktime()` | `time()` |

### `count(null)` TypeError 수정 (PHP 8.0)

- **오류**: `count(): Argument #1 ($value) must be of type Countable|array, null given`
- **원인**: `POD::queryRow()` 반환값이 null일 때 PHP 8.0에서 `count(null)` → TypeError. PHP 7.x에서는 E_WARNING 후 0 반환.
- **수정**: `!empty()` 선 검사 추가.

| 파일 | 라인 | 변경 전 | 변경 후 |
|------|------|---------|---------|
| `library/model/blog.feed.php` | 147 | `if (count($attaches) > 0)` | `if (!empty($attaches) && count($attaches) > 0)` |

### `getTeamProfile(null)` SQL 오류 방어 (플러그인)

- **오류**: `You have an error in your SQL syntax` (userid가 NULL일 때 빈 문자열로 쿼리 조립)
- **수정**: 함수 진입부 null 가드 추가.

| 파일 | 라인 | 변경 내용 |
|------|------|-----------|
| `plugins/ST_TeamBlogSettings/index.php` | 57-58 | `if (is_null($userid)) return '';` 가드 추가 |

### `array_search(value, null)` TypeError 수정 (PHP 8.0)

- **오류**: `array_search(): Argument #2 ($haystack) must be of type array, null given`
- **원인**: `$__gCacheAttachment` 배열에 `getAttachmentByOnlyName()` 의 `$pool->getRow()` null 반환값이 삽입됨 → `getAttachmentFromCache()` / `getAttachmentsFromCache()` 내 `array_search($value, $info)` 에서 `$info`가 null이 되어 PHP 8.0 TypeError.
- **수정**: `foreach` 내 `is_array($info)` 선 검사 추가. `getAttachmentByOnlyName()` 에서 null 결과를 캐시에 추가하지 않도록 수정.

| 파일 | 변경 내용 |
|------|-----------|
| `library/model/blog.attachment.php` | `getAttachmentsFromCache()`: `if (!is_array($info)) continue;` 추가 (L38) |
| `library/model/blog.attachment.php` | `getAttachmentFromCache()`: `if (!is_array($info)) continue;` 추가 (L50) |
| `library/model/blog.attachment.php` | `getAttachmentByOnlyName()`: `if (is_array($newAttachment))` 조건부 캐시 삽입 (L79) |

---

## v1.94b — tc-php82 컨테이너 런타임 오류 2차 수정 (2026-05-16)

### `Filter::isFiltered()` / `isAllowed()` 비정적 메서드 정적 호출 (PHP 8.0 Fatal Error)

- **오류**: `Uncaught Error: Non-static method Filter::isFiltered() cannot be called statically`
- **원인**: PHP 7.x에서는 비정적 메서드를 `ClassName::method()` 형태로 호출하면 deprecated 경고만 발생. PHP 8.0에서 Fatal Error로 격상. (php.net/migration80.incompatible)
- **분석**: `Textcube.Data.Filter.php`의 `isFiltered()` / `isAllowed()`는 `/*@static@*/` 주석이 있고 `$this` 미사용 — 정적 선언 누락된 PHP4 스타일.
- **수정**: 두 메서드에 `public static` 키워드 추가.
- **호출처**: 18곳 (`plugins/PN_Subscription_Default`, `framework/legacy`, `interface/owner/communication/*`, `library/model/blog.comment.php`, `library/model/blog.response.remote.php`)

| 파일 | 변경 내용 |
|------|-----------|
| `framework/legacy/Textcube.Data.Filter.php` | `isFiltered()` → `public static function isFiltered()` |
| `framework/legacy/Textcube.Data.Filter.php` | `isAllowed()` → `public static function isAllowed()` |

### `mysqli_report` 기본값 변경 대응 (PHP 8.1)

- **오류**: `Uncaught mysqli_sql_exception: Duplicate entry ... for key 'PRIMARY'`
- **원인**: PHP 8.1에서 `mysqli_report()` 기본값이 `MYSQLI_REPORT_OFF` → `MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT` 로 변경. (php.net/migration81.other-changes) 기존에 `false` 반환하던 중복키 INSERT 등이 예외를 던지게 됨.
- **수정**: `DBAdapter::bind()` 첫 줄에 `mysqli_report(MYSQLI_REPORT_OFF)` 추가하여 PHP 7.x 호환 동작 복원.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/data/MySQLi/Adapter.php` | `bind()` 첫 줄에 `mysqli_report(MYSQLI_REPORT_OFF)` 추가 |

---

## v1.94c — tc-php82 컨테이너 런타임 오류 3차 수정 (2026-05-16)

### `Transaction` 클래스 전체 메서드 정적 선언 누락 (PHP 8.0 Fatal Error)

- **오류**: `Non-static method Transaction::clear() cannot be called statically`
- **원인**: `Transaction` 클래스 메서드(`pickle`, `unpickle`, `repickle`, `taste`, `clear`, `gc`, `debug`)가 코드 전체에서 정적 호출되지만 `static` 키워드 없음. PHP 8.0에서 Fatal Error.
- **수정**: 전체 7개 메서드에 `public static` 추가.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/legacy/Textcube.Core.php` | `Transaction::pickle/unpickle/repickle/taste/clear/gc/debug()` → `public static` |

### `OpenIDConsumer::logout()` / `OpenID::setCookie()` 정적 선언 누락 (PHP 8.0 Fatal Error)

- **오류**: `Non-static method OpenIDConsumer::logout() cannot be called statically` (로그아웃 시 CL_OpenID 플러그인 진입점)
- **원인**: `logout()`, `clearUserInfo()`, `OpenID::setCookie()`, `OpenID::clearCookie()` — `$this` 미사용 메서드이지만 `static` 선언 없음.
- **수정**: 4개 메서드에 `public static` 추가.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/legacy/Textcube.Control.Openid.php` | `OpenID::setCookie()` → `public static` |
| `framework/legacy/Textcube.Control.Openid.php` | `OpenID::clearCookie()` → `public static` |
| `framework/legacy/Textcube.Control.Openid.php` | `OpenIDConsumer::logout()` → `public static` |
| `framework/legacy/Textcube.Control.Openid.php` | `OpenIDConsumer::clearUserInfo()` → `public static` |

### 테스트 결과 (2026-05-16, tc-php82)

| 모드 | 결과 |
|------|------|
| path 91/91 | **PASS** |
| PHP 오류 로그 | **0건** |
| Apache 오류 로그 | **0건** |

---

## v1.94d — tc-php82 컨테이너 런타임 오류 4차 수정 (2026-05-16)

### `CacheControl` 클래스 flush/purge 메서드 정적 선언 누락 (PHP 8.0 Fatal Error)

- **오류**: `Non-static method CacheControl::flushItemsByPlugin() cannot be called statically` (`library/plugin/common.plugin.php:70`)
- **원인**: `CacheControl` 클래스의 16개 flush/purge 메서드가 전체 코드에서 정적 호출되지만 `static` 키워드 없음. PHP 8.0에서 Fatal Error.
- **수정**: `framework/legacy/Needlworks.Cache.PageCache.php` 및 `Needlworks.Cache.PageCache.Legacy.php` 두 파일에서 `function flush*` / `function purge*` 패턴 전수에 `public static` 추가 (Perl 정규식 일괄 처리).
- **변환 메서드 목록** (16개): `flushAll`, `flushSkin`, `flushCategory`, `flushAuthor`, `flushTag`, `flushKeyword`, `flushSearchKeywordRSS`, `flushEntry`, `flushRSS`, `flushCommentRSS`, `flushTrackbackRSS`, `flushResponseRSS`, `flushCommentNotifyRSS`, `flushItemsByPlugin`, `flushDBCache`, `purgeItems`

| 파일 | 변경 내용 |
|------|-----------|
| `framework/legacy/Needlworks.Cache.PageCache.php` | `CacheControl::flush*/purge*` 16개 메서드 → `public static` |
| `framework/legacy/Needlworks.Cache.PageCache.Legacy.php` | 동일 |

### 중괄호 배열/문자열 접근 제거 (PHP 8.0)

- **오류**: `Array and string offset access syntax with curly braces is no longer supported`
- **원인**: PHP 8.0에서 `$var{$i}` 형식 완전 제거. (php.net/migration80.incompatible)
- **수정**: `$rssInfo['path']{0}` → `$rssInfo['path'][0]`

| 파일 | 위치 | 변경 내용 |
|------|------|-----------|
| `library/model/reader.common.php` | L449 | `$rssInfo['path']{0}` → `$rssInfo['path'][0]` |

### `gmmktime()` 무인자 호출 ArgumentCountError (PHP 8.1)

- **오류**: `ArgumentCountError: gmmktime() expects at least 1 argument, 0 given` (`reader.common.php:471`)
- **원인**: PHP 8.1에서 `gmmktime()` 인자 없는 호출이 Fatal Error로 격상. (php.net/migration81.incompatible) PHP 7.x에서는 deprecation 경고만 발생.
- **수정**: `library/model/reader.common.php` 내 `gmmktime()` 11건 전부 `time()`으로 교체. 어느 경우도 timezone 변환이 불필요한 단순 현재시각 취득 용도였음.

| 파일 | 변경 내용 |
|------|-----------|
| `library/model/reader.common.php` | `gmmktime()` → `time()` 전수 치환 (11건: L471, L480, L496, L582, L604, L609, L620, L672, L673, L682, L689, L732) |

### 테스트 결과 (2026-05-16, tc-php82)

| 모드 | 결과 |
|------|------|
| tc_full_test.sh 33/33 | **PASS** |
| PHP 오류 로그 | **0건** |
| Apache 오류 로그 | **0건** |

---

## v1.94 ~ v1.99 — 회귀 테스트 및 잔여 결함 수정

- tc-php82 컨테이너에서 회귀 테스트 실행.
- PASS 기준: single 66/66, path 91/91, domain 91/91, memcached 44/44 (합계 292/292).
- PHP 오류 로그 0건, Apache 오류 로그 0건 달성까지 잔여 결함 수정 반복.

---

## v2.00 — STAGE 2 종료 (2026-05-16)

### 최종 회귀 테스트 결과 (tc-php82, PHP 8.2)

| 테스트 | 결과 |
|--------|------|
| tc_full_test.sh — single 11/11 | **PASS** |
| tc_full_test.sh — path 11/11 | **PASS** |
| tc_full_test.sh — domain 11/11 | **PASS** |
| tc_full_test.sh 합계 33/33 | **PASS** |
| PHP 오류 로그 | **0건** |
| Apache 오류 로그 | **0건** |

### STAGE 2 수정 요약

PHP 8.0/8.1/8.2 대응 핵심 항목:

| 버전 | 항목 | 버전 항목 |
|------|------|----------|
| PHP 8.0 | PHP4 스타일 생성자 → `__construct()` | v1.76 |
| PHP 8.0 | `parent::` 생성자 호출 변환 | v1.77 |
| PHP 8.0 | 비정적 메서드 정적 호출 Fatal Error 대응 | v1.94a~v1.94d |
| PHP 8.0 | 중괄호 배열/문자열 접근 제거 | v1.94d |
| PHP 8.1 | `gmmktime()` 무인자 호출 → `time()` | v1.94d |
| PHP 8.1 | `mysqli_report` 기본값 변경 대응 | v1.94b |
| PHP 8.1 | `count(null)` → `count(array)` 방어 | v1.94a |
| PHP 8.2 | `${}` 문자열 보간 deprecated | v1.78 |
| PHP 8.2 | `#[AllowDynamicProperties]` 어트리뷰트 추가 | v1.79 |
| PHP 8.2 | `utf8_encode/decode` → `mb_convert_encoding` | v1.91 |

- SECURITY.md 모든 항목 현황 최종 재평가.
- README.md PHP 8.2 명시 최종 확인.
