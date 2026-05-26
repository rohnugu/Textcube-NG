# SECURITY — Textcube-NG (1.10.10 PHP 8.5 이식본) 보안 현황

## Reporting New Vulnerabilities

**Preferred channel — GitHub Private Vulnerability Reporting:**
Use the "Report a vulnerability" button on this repository's Security tab.
This routes your report directly to maintainers without public disclosure
and requires no email exchange.

**Alternative channel — email:**
For reporters without a GitHub account, contact:
textcube-ng@deok.io

Please **do not** open public issues for unpatched vulnerabilities.
We aim to acknowledge reports within 7 days. Response time may vary as
this is a volunteer-maintained community fork.

---

작성일: 2026-05-16 / 최종 수정: 2026-05-26  
대상 버전: Textcube-NG 1.10.10+php85.r1 (기반: php8.4-Textcube-1.10.10 v2.02)  
범위: PHP 8.5 호환성 이식 + STAGE 4 보안 강화 작업 후 발견/확인된 미해결 보안 취약점  
이전 단계 항목: 각 stage별 SECURITY.md 참조 (STAGE 1/2/3 해소 항목 포함)

---

## [HIGH] 비밀번호 MD5 해싱 (Salt 없음)

- **위치**: `library/auth.php`, `framework/boot/30-Auth.php:367`, `framework/legacy/Textcube.Core.php:246`, `setup.php:1146`
- **내용**: 사용자 비밀번호가 `md5($password)` 단독으로 저장됨. Salt 없음. MD5는 레인보우 테이블 및 GPU 브루트포스에 취약.
- **권고**: `password_hash($password, PASSWORD_BCRYPT)` 저장, `password_verify()` 검증. 기존 사용자 로그인 시 점진적 재해싱(rehash) 적용.
- **현황**: 기존 호스팅 환경과의 단순 이식 호환성을 위해 의도적으로 수정하지 않음. 단순 데이터 마이그레이션 적용 시 기존 사용자의 비밀번호 검증 방식이 달라져 로그인 불가 현상이 발생하며 사용자 혼란을 초래할 우려가 있음. 기존 Textcube 데이터를 그대로 이전하는 환경에서는 로그인 시 점진적 재해싱(on-login rehash) 등 신중한 마이그레이션 전략이 필요하며, 해당 설계 없이 단독 적용은 권장하지 않음.

---

## [HIGH] Raw SQL 쿼리 + 수동 escape — 핵심 진입점 변환 완료, 잔여 쿼리 STAGE 3 이연

- **위치**: `POD::query()` 인터페이스 전체. 대표 예: `library/auth.php:147` (`WHERE u.loginid = '$loginid'`), `library/model/blog.entry.php` 등 수백 곳
- **내용**: SQL 쿼리를 문자열 임베딩 방식으로 조립하고 `mysqli::real_escape_string()`으로 수동 이스케이프. escape 누락 시 SQL Injection 가능.
- **권고**: `prepare()`/`execute()`/`bind_param()` Prepared Statement 전면 도입.
- **현황**: STAGE 2(v1.85~v1.90)에서 핵심 외부 입력 진입점을 prepared statement로 변환 완료. 잔여 내부 쿼리는 STAGE 3(v2.10) 이연. 미변환 외부 입력 함수에 `@security raw-sql-escape` PHPDoc 마커 부착.

### STAGE 2에서 Prepared Statement로 변환 완료된 함수 (v1.85~v1.89)

| 파일 | 함수 | 변환 버전 |
|------|------|-----------|
| `framework/data/MySQLi/Adapter.php` | `prepare()`, `bindAndExecute()`, `fetchAllStmt()` 신설 | v1.85 |
| `framework/data/IAdapter.php` | 위 3개 메서드 인터페이스 선언 추가 | v1.85 |
| `framework/boot/30-Auth.php` | `Auth::authenticate()` — `loginid` WHERE 절 | v1.86 |
| `library/auth.php` | `isLoginId()` — `loginid` + `blogid` WHERE 절 | v1.86 |
| `library/model/blog.comment.php` | `addComment()` — 댓글 INSERT (13 params) | v1.87 |
| `library/model/blog.comment.php` | `updateComment()` — 댓글 UPDATE (10/9 params) | v1.87 |
| `library/model/blog.entry.php` | `addEntry()` — 게시물 INSERT (17/18 params) | v1.87 |
| `library/model/blog.entry.php` | `updateEntry()` — 게시물 UPDATE (published 분기 3종) | v1.87 |
| `library/model/blog.api.php` | `api_addAttachment()` — 첨부 INSERT (8 params) | v1.88 |
| `library/model/blog.api.php` | `api_update_attaches()` — 첨부 UPDATE (3 params) | v1.88 |

### `@security raw-sql-escape` 마커 부착 (미변환 외부 입력 진입점)

| 파일 | 함수 | 사유 |
|------|------|------|
| `library/model/blog.entry.php` | `getEntryListWithPagingBySearch()` | LIKE 패턴 — POD::escapeString 처리됨, STAGE 3 변환 예정 |
| `library/model/blog.entry.php` | `getEntriesWithPagingBySearch()` | LIKE 패턴 — POD::escapeString 처리됨, STAGE 3 변환 예정 |
| `library/model/blog.api.php` | `api_update_attaches_with_replace()` | 복합 문자열 조립 — POD::escapeString 처리됨, STAGE 3 변환 예정 |
| `library/model/blog.response.remote.php` | trackback 수신 처리 | DBModel 구조상 prepared 불가, POD::escapeString 처리됨 |

### setup.php — legacy escaping 유지 (v1.89)

`setup.php` L1136-1188의 관리자 계정 INSERT는 배치 SQL(`implode`로 조립된 다중 VALUES) 구조로 prepared statement로 변환 시 배치 실행 방식을 전면 재작성해야 함. 해당 파라미터(`$loginid`, `$name`, `$blog`)는 모두 `POD::escapeString()` 처리됨. 리스크 수용 후 legacy escaping 유지 결정. STAGE 3에서 재검토 예정.

---

## [FIXED] phpopenid PHP4 스타일 생성자 → `__construct()` 변환 (v1.76)

- **위치**: `library/contrib/phpopenid/` 하위 24개 파일, 총 56건. 추가 발견: `plugins/StatGraph/count/src/` jpgraph 4개 파일, 28건.
- **내용**: 클래스명과 동일한 메서드명을 생성자로 사용하는 PHP4 패턴. PHP 8.0에서 deprecated, PHP 9.0에서 제거 예정.
- **조치**: 전 대상 파일에서 `function ClassName(` → `function __construct(`. (php.net/migration80.deprecated)
- **현황**: 해소됨 (v1.76, jpgraph 추가분 2026-05-16).

---

## [FIXED] 동적 프로퍼티 전수 → `#[AllowDynamicProperties]` (v1.79)

- **위치**:
  - `library/contrib/phpopenid/` — 26개 파일 50개 클래스 전수 (1차 핵심 진입점 13개 + 2차 나머지 37개)
  - `library/contrib/phpxpath/` — XPath.class.php 3개 클래스 (XPathBase, XPathEngine, XPath)
  - `library/blog.skin.php` — Skin 클래스 ($cache 미선언)
  - `framework/legacy/` — Textcube.Data.* 26개 클래스 + Textcube.Control.Openid.php 2개 + Textcube.Model.Message.php 1개 + Needlworks.PHP.Pop3.php(Pop3) + Needlworks.PHP.XMLRPC.php(XMLRPC) + Needlworks.PHP.XMLTree.php(XMLTree) + Needlworks.PHP.OutputWriter.php(OutputWriter) + Textcube.View.Pages.php(Pages) (합계 34개 클래스, 33개 파일)
  - `framework/model/URIHandler.php` — Model_URIHandler ($context, $blog, $skin 미선언)
  - `framework/utils/Image.php` — Utils_Image ($extraPadding, $imageFile, $resultImageDevice, $bgColorBy16 미선언)
  - `framework/boot/10-CoreClasses.php` — XMLStruct ($ns, $baseindex, $nsenabled, $_cursor, $_path, $_cdata, $_consumer, $_streams 미선언)
  - `framework/boot/30-Auth.php` — Acl ($context 미선언)
  - `plugins/` — CL_Moblog::Moblog, FM_Textile::Textile, StatGraph jpgraph.php::Graph, jpgraph_scatter.php::FieldPlot/ScatterPlot (5개 클래스)
  - `framework/model/AlternateLogins.php` — Model_AlternateLogins ($userid, $provider, $remoteid, $data, $_error 미선언)
  - `framework/model/Line.php` — Model_Line ($blogid, $category, $root, $author, $content, $permalink, $created, $_error 미선언)
  - `framework/data/DBModel.php` — `#[AllowDynamicProperties]` 대신 선언 수정: `$context`, `$_reservedFunctions` 추가, `$_limitation` → `$_limit` 명명 불일치 버그 수정 (원본부터 존재하던 버그)
  - `framework/model/Config.php` — `private $settings, $backend_name` 선언 추가 (동적 프로퍼티 → 명시 선언으로 전환)
- **내용**: 클래스 본체에 프로퍼티 선언 없이 `$this->prop = value` 방식으로 동적 할당. PHP 8.2에서 deprecated, PHP 9.0에서 제거 예정.
- **조치**: 각 클래스 정의 직전 단독 라인에 `#[AllowDynamicProperties]` 어트리뷰트 추가 (임시 마이그레이션 대응). `$this->` 할당 없는 클래스(FeedGroup, Tag, DataMaintenance, Statistics, Paging, RSS, OpenID, static-only 클래스), `var`/`public` 선언이 이미 있는 클래스(UserInfo, PluginCustomConfig), typed property 완비 라이브러리(FM_Markdown v2.0.0), 미로드 dead file(RemoteResponse)은 제외. (php.net/migration82.deprecated)
- **현황**: 해소됨 (v1.79, 임시 대응). 정식 프로퍼티 선언으로 전환은 STAGE 3(v2.04~v2.09) 이연.

---

## [FIXED] `${}` 문자열 보간 → `{$}` 형태로 변경 (v1.78)

- **위치**: `interface/owner/network/xfn/index.php:10`. 전수 grep으로 잔존 패턴 없음 확인.
- **내용**: `"${varname}"` / `"${expr}"` 형태의 문자열 보간. PHP 8.2에서 deprecated. (php.net/migration82.deprecated)
- **조치**: `"Location: ${_SERVER['REQUEST_URI']}"` → `"Location: {$_SERVER['REQUEST_URI']}"`.
- **현황**: 해소됨 (v1.78).

---

## [MEDIUM] StatGraph — jpgraph 1.x QPL 라이선스

- **위치**: `plugins/StatGraph/count/src/jpgraph.php`
- **내용**: 번들된 jpgraph 라이브러리가 QPL(Q Public License) 라이선스 적용. 상업적 사용 시 별도 라이선스 필요.
- **권고**: Chart.js/ApexCharts 등 MIT 라이선스 대체 라이브러리로 재구현.
- **현황**: 코드 내 `split()` 수정 완료(PHP 7.4 호환, STAGE 1). 기능 재구현은 별도 작업.

---

## [MEDIUM] OpenID 2.0 지원 중단 (EOL)

- **위치**: `library/contrib/phpopenid/`, `framework/legacy/Textcube.Control.Openid.php`, `plugins/CL_OpenID/`
- **내용**: OpenID 2.0 프로토콜은 대부분의 Provider가 지원 종료. phpopenid(JanRain) 라이브러리는 더 이상 유지보수되지 않음.
- **권고**: OpenID Connect(OIDC) 또는 OAuth 2.0 기반 인증으로 교체.
- **현황**: PHP 8.2 코드 호환성 패치(v1.76~v1.79) 적용 예정. 기능 재구현은 별도 작업.

---

## [LOW] 쿠키 속성 누락

- **위치**: `framework/legacy/Textcube.Control.Session.php:39`
- **내용**: `setcookie()` 호출에 `HttpOnly`, `SameSite`, `Secure` 속성 미설정.
- **권고**: `setcookie($name, $value, ['httponly'=>true,'samesite'=>'Lax','secure'=>true,...])`
- **현황**: STAGE 2 이식 범위 외. STAGE 3(v2.13~v2.29) 점진 처리 예정.

---

## [LOW] 정적 자산 버전 고정 (jQuery 1.11.2 등)

- **위치**: `framework/id/textcube/config.default.php:19-22`
- **내용**: jQuery 1.11.2, Lodash 2.4.1 등 10년+ 전 버전 고정. 알려진 XSS 취약점 포함 가능.
- **권고**: 최신 버전 업데이트.
- **현황**: STAGE 2 이식 범위 외. STAGE 3(v2.13~v2.29) 점진 처리 예정.

---

## [INFO] Clipboard API — HTTPS 필수

- **위치**: `resources/script/common3.js` (`copyUrl()` 함수)
- **내용**: `navigator.clipboard.writeText()` (Clipboard API)는 보안 컨텍스트(HTTPS 또는 localhost)에서만 사용 가능. HTTP 환경에서는 `_legacyCopyUrl()` fallback(IE: `window.clipboardData`, 기타: 텍스트 선택)으로 동작.
- **권고**: 운영 환경에서 HTTPS 적용 권장.
- **현황**: 이식 범위 외.

---

## [FIXED] phpmailer 5.x → 6.9.x 교체 — `each()` PHP 8.0 제거 대응 (v1.81)

- **위치**: `library/contrib/phpmailer/`, `library/function/mail.php`
- **내용**: phpmailer 5.x에서 `each()` 사용(`class.phpmailer.php:342,369,453`, `class.smtp.php`). PHP 8.0에서 `each()` 제거로 Fatal Error 발생.
- **조치**: phpmailer 6.9.x 교체. `legacy_shim.php`로 `PHPMailer\PHPMailer\PHPMailer` → 전역 `PHPMailer` 별칭 제공하여 기존 호출 코드 무수정 유지.
- **현황**: 해소됨 (v1.81).

---

## [FIXED] `hash_equals()` 도입 — Timing attack 대응 (v1.84)

- **위치**: `framework/boot/30-Auth.php`
- **내용**: 인증 토큰 비교 시 `===` 사용. 실행 시간 차이로 비교값 유추 가능(timing attack).
- **조치**: `hash_equals($authtoken, $password)` 로 상수 시간 비교 전환. (php.net/function.hash-equals, PHP 5.6+)
- **현황**: 해소됨 (v1.84).

---

## [FIXED] 임시 비밀번호 / DB 세션 ID — `rand()` 예측 가능 PRNG 전면 교체 (v1.86)

- **위치**:
  - `library/auth.php:159` — `generatePassword()` (임시 비밀번호)
  - `framework/legacy/Textcube.Core.php:288` — `Textcube::__generatePassword()` (임시 비밀번호)
  - `framework/legacy/Textcube.Control.Session.php:133` — `newAnonymousSession()` (DB 세션 ID 4×)
  - `framework/legacy/Textcube.Control.Session.php:225` — `authorize()` (DB 세션 ID 4×)
  - `framework/legacy/Textcube.Data.Attachment.php:152,163` — 첨부파일 이름 생성
  - `library/model/blog.attachment.php:147,223` — 첨부파일 이름 생성
  - `library/model/blog.api.php:356,359` — 첨부파일 이름 생성
  - `plugins/ST_TeamBlogSettings/index.php:329` — 첨부파일 이름 생성
- **내용**: 임시 비밀번호 및 DB 기반 세션 ID 생성 시 `rand()` 사용. `rand()`는 예측 가능한 PRNG이므로 공격자가 세션 또는 임시 비밀번호를 추측 가능. 첨부파일 이름은 예측 가능 시 비공개 첨부 열람 위험. STAGE 1에서 memcached 세션 ID는 수정했으나(v1.64), DB 세션 ID와 나머지 위치는 누락되었음.
- **조치**: 전 위치에서 `rand(...)` → `random_int(...)` (CSPRNG, PHP 7.0+). (php.net/function.random-int)
- **현황**: 해소됨 (v1.86).

---

## [FIXED] memcached 세션 ID 생성 — `rand()` 예측 가능 PRNG 사용 (v1.64, v1.65)

- **위치**: `framework/cache/Memcache.php:100`, `framework/legacy/Textcube.Control.Session.Memcached.php:125,206`
- **내용**: memcached 세션 ID 및 캐시 네임스페이스 해시를 `rand()`(예측 가능한 PRNG)로 생성.
- **조치**: `dechex(rand(...))` → `dechex(random_int(0x10000000, 0x7FFFFFFF))` (CSPRNG) 전환.
- **현황**: 해소됨 (v1.64, v1.65).

---

## [FIXED] memcached 세션 고착 — 로그인 후 세션 데이터가 anonymous ID로 기록 (v1.68)

- **위치**: `framework/legacy/Textcube.Control.Session.Memcached.php` — `authorize()` 메서드
- **내용**: PHP 7.x에서 `@session_id($new_id)` 동작 변경으로 로그인 세션 데이터가 anonymous ID로 기록 → 세션 복원 실패 → 302 리다이렉트.
- **조치**: `@session_id($id)` 제거 후 `session_encode()`로 세션 직렬화 데이터를 authorized_id 키에 직접 기록.
- **현황**: 해소됨 (v1.68).

---

## [FIXED] phpinfo() — PHP Logo 정규식 PCRE 백트래킹 한계 초과로 출력 전체 소실 (v1.69)

- **위치**: `interface/control/system/index.php`
- **내용**: PHP Logo/Zend Logo 추출 정규식이 `phpinfo()` 77KB 출력에서 PCRE backtrack limit 초과 → `preg_replace()` NULL 반환 → 출력 소실.
- **조치**: PHP 7.x 이후 사라진 PHP Logo/Zend Logo 정규식 쌍 주석 처리.
- **현황**: 해소됨 (v1.69).

---

## [FIXED] `Model_Config` — `$memcached` 설정 네임스페이스 누락으로 설정 무시 (v1.67)

- **위치**: `framework/model/Config.php` — `__basicConfigLoader()`
- **내용**: `global $memcached` 누락으로 config.php의 memcached 설정이 항상 무시되고 localhost로 연결.
- **조치**: `global $memcached` 선언 추가, `updateContext()`에 `memcached` 네임스페이스 조건부 로드 추가.
- **현황**: 해소됨 (v1.67).

---

## [FIXED] Memcache::connect() — 포트 파라미터 누락으로 커스텀 포트 설정 무효화 (v1.70)

- **위치**: `library/preprocessor.php:119`, `framework/cache/Memcache.php:24`
- **내용**: `Memcache::connect($host)` 포트 인수 생략. pecl/memcache 4.x에서 기본값 11211 보장 불가.
- **조치**: `$context->getProperty('memcached.port')` 우선 적용, 미설정 시 11211 fallback.
- **현황**: 해소됨 (v1.70).

---

## [FIXED] Flash TSSESSION 세션 노출 제거 (STAGE 1)

- **위치**: `interface/owner/entry/attachmulti/index.php` (기존)
- **내용**: Flash 업로더 경유 GET 파라미터로 세션 토큰 노출.
- **조치**: HTML5 업로더로 교체, GET 파라미터 경유 세션 주입 코드 완전 제거.
- **현황**: 해소됨 (STAGE 1).

---

## STAGE 1 보안 검증 테스트 결과 (2026-05-15)

PHP 7.4 이식 완료 후 single / path / domain 3개 모드 전체에 대해 HTTP 기능 테스트 및 memcached 세션 보안 테스트를 수행하였다.

### HTTP 기능 테스트 (`tc_pagetest.sh`)

| 모드 | 결과 |
|------|------|
| single (127.0.0.1, blog=1) | 66/66 PASS |
| path (서브도메인 기반 도메인, 블로그 3개) | 91/91 PASS |
| domain (secondary domain 3개) | 91/91 PASS |

### memcached 세션 보안 테스트 (`tc_memtest.sh`)

| 테스트 항목 | 결과 |
|-------------|------|
| memcached 연결 확인 (cmd_get/set/hit rate 87%) | PASS |
| 로그인 후 관리자 페이지 접근 (세션 유효) | 9/9 PASS |
| 크로스 블로그 세션 격리 | 4/4 PASS |
| 동시 다중 세션 유지 | 6/6 PASS |
| 로그아웃 후 세션 무효화 | 9/9 PASS |
| 로그아웃 후 공개 콘텐츠 접근성 | 12/12 PASS |
| memcached 세션 항목 증감 확인 | PASS |
| **합계** | **44/44 PASS** |

PHP 오류 로그: 0건, Apache 오류 로그: 0건

---

## [FIXED] `strftime()` / `gmstrftime()` — PHP 8.1 deprecated 전면 교체 (v1.91)

- **위치**: `framework/boot/10-CoreClasses.php` (Timestamp 클래스 11건), `framework/legacy/Needlworks.PHP.Pop3.php`, `interface/control/system/index.php`, `library/contrib/phpopenid/Auth/OpenID/Nonce.php`, `library/function/time.php`, `library/model/blog.api.php`, `plugins/StatGraph/count/src/jpgraph.php`, `framework/locales/Po2php.php`, `resources/locale/translate/po2php.php`
- **내용**: `strftime()` / `gmstrftime()` PHP 8.1에서 deprecated (php.net/migration81.deprecated), PHP 9.0에서 제거 예정. `error_reporting = E_ALL | E_DEPRECATED` 환경에서 E_DEPRECATED 로그 다수 발생.
- **조치**:
  - `framework/boot/10-CoreClasses.php`에 `strftime_compat()` / `gmstrftime_compat()` 헬퍼 함수 신설 (문자 단위 매핑, PHP 8.0+ `match` 표현식 사용). `Timestamp` 클래스 전체 교체.
  - 수치형 포맷 코드만 사용하는 파일(Pop3.php, Nonce.php, blog.api.php, time.php 등)은 `date()` / `gmdate()` 직접 교체.
  - jpgraph의 `%a`, `%b`, `%B` 코드 → `date('D', ...)`, `date('M|F', ...)` 교체 (영문 요일/월 이름). 로케일 의존 이름은 `setlocale()`+`strftime()` 조합이었으나 PHP 8.1 이후 사용 불가.
- **현황**: 해소됨 (v1.91).

---

## [FIXED] PHP 8.0 비정적 메서드 정적 호출 Fatal Error 대응 (v1.94a~v1.94d)

- **위치**: `framework/legacy/Textcube.Data.Filter.php`, `framework/legacy/Textcube.Core.php`, `framework/legacy/Textcube.Control.Openid.php`, `framework/legacy/Needlworks.Cache.PageCache.php`, `framework/legacy/Needlworks.Cache.PageCache.Legacy.php`
- **내용**: PHP4 스타일 `/*@static@*/` 주석만 있고 `static` 키워드 없는 메서드들이 코드 전체에서 정적으로 호출됨. PHP 8.0에서 Fatal Error로 격상.
- **조치**: 대상 메서드에 `public static` 추가.
  - `Filter::isFiltered()`, `Filter::isAllowed()` (v1.94b)
  - `Transaction::pickle/unpickle/repickle/taste/clear/gc/debug()` 7개 (v1.94c)
  - `OpenID::setCookie()`, `OpenID::clearCookie()`, `OpenIDConsumer::logout()`, `OpenIDConsumer::clearUserInfo()` (v1.94c)
  - `CacheControl::flush*()`/`purge*()` 16개 (v1.94d)
- **현황**: 해소됨 (v1.94b~v1.94d).

---

## [FIXED] PHP 8.0 중괄호 배열/문자열 접근 제거 (v1.94d)

- **위치**: `library/model/reader.common.php:449`
- **내용**: `$var{$i}` 형식의 배열/문자열 접근. PHP 8.0에서 완전 제거됨. (php.net/migration80.incompatible)
- **조치**: `$rssInfo['path']{0}` → `$rssInfo['path'][0]`
- **현황**: 해소됨 (v1.94d).

---

## [FIXED] PHP 8.1 `gmmktime()` 무인자 호출 ArgumentCountError (v1.94d)

- **위치**: `library/model/reader.common.php` 11개 호출 지점
- **내용**: `gmmktime()` 인자 없는 호출. PHP 7.x에서 deprecation 경고, PHP 8.1에서 ArgumentCountError로 격상. (php.net/migration81.incompatible)
- **조치**: `gmmktime()` 전수 → `time()` 교체 (단순 현재시각 취득 용도로 timezone 변환 불필요).
- **현황**: 해소됨 (v1.94d).

---

## [FIXED] PHP 8.1 `mysqli_report` 기본값 변경 대응 (v1.94b)

- **위치**: `framework/data/MySQLi/Adapter.php` — `bind()` 메서드
- **내용**: PHP 8.1에서 `mysqli_report()` 기본값이 `MYSQLI_REPORT_OFF` → `MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT`로 변경. 중복키 INSERT 등 기존에 `false` 반환하던 SQL 오류가 예외로 격상됨. (php.net/migration81.other-changes)
- **조치**: `bind()` 첫 줄에 `mysqli_report(MYSQLI_REPORT_OFF)` 추가하여 PHP 7.x 호환 동작 복원.
- **현황**: 해소됨 (v1.94b).

---

## [FIXED] `array_search(value, null)` TypeError — 캐시에 null 삽입 방어 (v1.94a)

- **위치**: `library/model/blog.attachment.php` — `getAttachmentByOnlyName()`, `getAttachmentsFromCache()`, `getAttachmentFromCache()`
- **내용**: DB 조회 결과 null 반환 시 캐시 배열에 null 행 삽입 → 이후 `array_search($value, null)` 호출. PHP 7.x에서는 `false` 반환, PHP 8.0에서 TypeError로 격상.
- **조치**: `is_array($newAttachment)` 조건부 캐시 삽입, 캐시 순회 시 `is_array($info)` guard 추가.
- **현황**: 해소됨 (v1.94a).

---

## STAGE 2 보안 검증 테스트 결과 (2026-05-16)

### 설치/기능 통합 테스트 (`tc_full_test.sh`)

| 모드 | 결과 |
|------|------|
| single (설치 → 스모크 → 로그인/포스트) | 11/11 PASS |
| path (하위 경로 다중 블로그) | 11/11 PASS |
| domain (서브도메인 다중 블로그) | 11/11 PASS |
| **합계** | **33/33 PASS** |

PHP 오류 로그: 0건, Apache 오류 로그: 0건

### STAGE 2 수정 항목 수 요약

| 분류 | 항목 수 |
|------|---------|
| PHP4 생성자 → `__construct()` | 90건 (phpopenid 56 + jpgraph 28 + 기타 6) |
| `parent::` 생성자 호출 변환 | 5건 |
| `${}` 보간 → `{$}` | 1건 |
| `#[AllowDynamicProperties]` 추가 | 50개 클래스 |
| 비정적 메서드 `public static` 추가 | 29개 메서드 |
| `strftime()` → `date()` 교체 | 다수 |
| `gmmktime()` → `time()` | 11건 |
| 중괄호 배열 접근 `{0}` → `[0]` | 1건 |
| `array_search(null)` 방어 | 3개 함수 |
| `mysqli_report(MYSQLI_REPORT_OFF)` 추가 | 1건 |
| Prepared Statement 도입 | 10개 함수 |

---

## [FIXED] mysqli 절차형 API → 객체지향 전환 (v2.02)

- **위치**: `framework/data/MySQLi/Debug.php`, `framework/data/MySQL/Debug.php`, `framework/data/MySQLi/Adapter.php`
- **내용**: `mysqli_error()`, `mysqli_errno()`, `mysqli_num_rows()`, `mysqli_affected_rows()`, `mysqli_character_set_name()`, `mysqli_free_result()`, `mysqli_fetch_*()` 등 mysqli 절차형 함수 사용. PHP 8.5 이후 deprecated 예정.
- **조치**: 각 함수를 mysqli 연결/결과 객체의 프로퍼티·메서드로 교체 (`$conn->error`, `$result->num_rows`, `$handle->fetch_assoc()` 등). 총 3개 파일 19건 변환.
- **현황**: 해소됨 (v2.02).

---

## STAGE 3 보안 검증 테스트 결과 (2026-05-16)

### 설치/기능 통합 테스트 (`tc_full_test.sh`)

| 모드 | 결과 |
|------|------|
| single (설치 → 스모크 → 로그인/포스트) | 11/11 PASS |
| path (하위 경로 다중 블로그) | 11/11 PASS |
| domain (서브도메인 다중 블로그) | 11/11 PASS |
| **합계** | **33/33 PASS** |

PHP 오류 로그: 0건, Apache 오류 로그: 0건

### STAGE 3 수정 항목 수 요약

| 분류 | 항목 수 |
|------|---------|
| mysqli 절차형 API → 객체지향 전환 | 19건 (3개 파일) |

---

## [FIXED] pecl/memcache PHP 8.5 지원 — websupport-sk 포크 설치 (v3.03)

- **위치**: `framework/cache/Memcache.php`, `library/preprocessor.php`
- **내용**: 공식 pecl/memcache 8.2는 PHP 8.5에서 `ext/standard/php_smart_string_public.h` 헤더 제거로 빌드 불가. 단, [websupport-sk/pecl-memcache](https://github.com/websupport-sk/pecl-memcache) 포크가 PHP 8.5+ 지원 제공 (PR #118 — `Zend/zend_smart_string.h` 헤더 교체 적용).
- **조치**: websupport-sk 포크를 소스 빌드(`phpize && ./configure --enable-memcache && make install`)하여 tc-php85 (PHP 8.5.6)에 설치. Debian/Ubuntu 계열 패키지 관리자에서도 제공됨.
- **검증**: memcache 활성화(`$service['memcached'] = true`, `$memcached['server'] = 'tc-memcached'`) 상태에서 tc_full_test.sh 33/33 PASS, PHP 오류 로그 0건.
- **현황**: 해소됨 (v3.03). PHP 8.5에서 memcache 완전 동작 확인.

---

## [FIXED] `control/server/config` / `control/server/rewrite` — 권한 가드 누락으로 비권한 사용자의 서버 설정 및 `.htaccess` 임의 변경 가능 (v3.04, 2026-05-17)

- **위치**:
  - `interface/control/server/config/index.php`
  - `interface/control/server/rewrite/index.php`
- **내용**: 두 파일 모두 `requireStrictRoute()` 후 `requirePrivilege()` 가드가 없어, 동일 Textcube 인스턴스에서 임의 블로그의 소유자(`group.owners`) 계정이 POST 요청을 전송하면 시스템 전역 설정을 변경하거나 `.htaccess` 를 임의 내용으로 덮어쓸 수 있었음.
  - `config/index.php`: `$service['timeout']`, `skin`, `language`, `timezone`, `encoding`, `serviceurl`, `cookie_prefix`, `debug 모드`, `cache 모드` 등 전역 설정 전체가 대상.
  - `rewrite/index.php`: `writeHtaccess($_POST['body'])` 호출로 `.htaccess` 파일을 임의 내용으로 덮어씀 → URL 리라이팅 규칙 파괴로 사이트 전체 접근 불가.
- **확인된 공격 시나리오**: `group.owners`(userid≠1) 계정으로 서버 설정 변경 및 `.htaccess` 임의 덮어쓰기 재현됨 (상세 내용 `../php8.4-Textcube-1.10.10/SECURITY.md` 참조).
- **조치**:
  - `interface/control/server/config/index.php`: `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가.
  - `interface/control/server/rewrite/index.php`: `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가.
  - 이전 수정에서 shell sed 처리 오류로 `requirePrivilege(group.creators)` (따옴표 없음)가 삽입되어 가드가 실질적으로 무효였던 버그도 함께 수정.
  - php7.4, php8.2, php8.4, php8.5 전 버전 동일 적용.
- **현황**: 해소됨 (v3.04). 나머지 `control/*` 가드 미비 뷰 엔드포인트(`control/index.php`, `control/server/index.php`)는 추후 수정 예정. `control/action/user/add|delete|suggest` 가드 누락은 v3.06에서 해소.

---

## STAGE 4 보안 검증 테스트 결과 (2026-05-16)

### 설치/기능 통합 테스트 (`tc_full_test.sh`)

| 모드 | 결과 |
|------|------|
| single (설치 → 스모크 → 로그인/포스트) | 11/11 PASS |
| path (하위 경로 다중 블로그) | 11/11 PASS |
| domain (서브도메인 다중 블로그) | 11/11 PASS |
| **합계** | **33/33 PASS** |

PHP 오류 로그: 0건, Apache 오류 로그: 0건  
비고: pecl/memcache (websupport-sk 포크) 활성화 상태에서 진행.

### STAGE 4 수정 항목 수 요약

| 분류 | 항목 수 |
|------|---------|
| PHP 코드 수정 | **0건** (STAGE 3 코드 PHP 8.5 완전 호환) |
| 환경 — pecl/memcache (websupport-sk) PHP 8.5 설치 성공 | (v3.03) |

---

## [FIXED] `control/action/user/add` / `delete` / `suggest` — 권한 가드 누락으로 비권한 사용자의 사용자 추가·삭제·열거 가능 (v3.06, 2026-05-17)

- **위치**:
  - `interface/control/action/user/add/index.php`
  - `interface/control/action/user/delete/index.php`
  - `interface/control/action/user/suggest/index.php`
- **내용**: 세 파일 모두 `requireStrictRoute()` 후 `requirePrivilege()` 가드가 없어, 동일 Textcube 인스턴스에서 임의 블로그의 소유자(`group.owners`, userid≠1) 계정이 시스템 사용자 관리 API를 무단으로 호출 가능했음. 상세 내용 `../php8.4-Textcube-1.10.10/SECURITY.md` 참조.
- **조치**:
  - 세 파일 모두 `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가.
  - php7.4, php8.2, php8.4, php8.5 전 버전 동일 적용.
- **현황**: 해소됨 (v3.06). `suggest/index.php`의 SQL Injection(`$_GET['input']` 미처리)은 STAGE 3 prepared statement 전환 시 해소 예정.
