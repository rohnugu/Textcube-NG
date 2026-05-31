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
대상 버전: Textcube-NG 1.10.10+php85.r2 (기반: php8.4-Textcube-1.10.10 v2.02)  
범위: PHP 8.5 호환성 이식 + STAGE 4 보안 강화 작업 후 발견/확인된 미해결 보안 취약점  
이전 단계 항목: 각 stage별 SECURITY.md 참조 (STAGE 1/2/3 해소 항목 포함)

---

## [HIGH] 비밀번호 MD5 해싱 (Salt 없음)

- **위치**: `library/auth.php`, `framework/boot/30-Auth.php:367`, `framework/legacy/Textcube.Core.php:246`, `setup.php:1146`
- **내용**: 사용자 비밀번호가 `md5($password)` 단독으로 저장됨. Salt 없음. MD5는 레인보우 테이블 및 GPU 브루트포스에 취약.
- **권고**: `password_hash($password, PASSWORD_BCRYPT)` 저장, `password_verify()` 검증. 기존 사용자 로그인 시 점진적 재해싱(rehash) 적용.
- **현황**: 기존 호스팅 환경과의 단순 이식 호환성을 위해 의도적으로 수정하지 않음. 단순 데이터 마이그레이션 적용 시 기존 사용자의 비밀번호 검증 방식이 달라져 로그인 불가 현상이 발생하며 사용자 혼란을 초래할 우려가 있음. 기존 Textcube 데이터를 그대로 이전하는 환경에서는 로그인 시 점진적 재해싱(on-login rehash) 등 신중한 마이그레이션 전략이 필요하며, 해당 설계 없이 단독 적용은 권장하지 않음.

---

## [FIXED] Raw SQL 쿼리 + 수동 escape — SQLi 방어 확보 (핵심 진입점 prepared 전환 + 잔여 escape 실측 입증), prepared 전면화는 best-practice 잔여

- **위치**: `POD::query()` 인터페이스 전체. 대표 예: `library/auth.php:147` (`WHERE u.loginid = '$loginid'`), `library/model/blog.entry.php` 등 수백 곳
- **내용**: SQL 쿼리를 문자열 임베딩 방식으로 조립하고 `mysqli::real_escape_string()`으로 수동 이스케이프. escape 누락 시 SQL Injection 가능.
- **권고**: `prepare()`/`execute()`/`bind_param()` Prepared Statement 전면 도입.
- **현황**: STAGE 2(v1.85~v1.90)에서 핵심 외부 입력 진입점을 prepared statement로 변환 완료. 잔여 내부 쿼리는 STAGE 3(v2.10) 이연. 미변환 외부 입력 함수에 `@security raw-sql-escape` PHPDoc 마커 부착. **잔여 마커 4함수는 escape 기반 SQLi 방어를 악성 입력 하니스로 실측 입증(2026-05-31, ALL PASS) — 아래 「악성 입력 실측 SQLi 방어 검증」 참조. 현 상태로 SQL Injection 방어는 확보되어 있으며, 잔여 항목은 실제 취약점이 아니라 파라미터 바인딩(best-practice) 미적용 수준.**

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

### 악성 입력 실측 SQLi 방어 검증 (2026-05-31)

`@security raw-sql-escape` 마커 4함수가 escape로 SQL Injection을 실제 차단하는지, **포트의 실제 코드 경로**(DBModel / escapeSearchString / Trackback)를 악성 입력으로 실행하여 **생성 SQL을 추적·입증**함.

| 함수 | 검증 방식(하니스) | 악성 입력 | 결과 |
|------|------------------|----------|------|
| `getEntryListWithPagingBySearch` / `getEntriesWithPagingBySearch` | 실제 `escapeSearchString` 경로로 LIKE 절 생성 추적 (`_sectest/entry_search_sqli_test.php`) | 따옴표 탈출·UNION·주석 절단·와일드카드(`%`,`_`)·NUL 6종 | **ALL PASS** — 따옴표 `\'`, 와일드카드 `\%`/`\_` escape, 리터럴/와일드카드 탈출 불가 |
| `receiveTrackback` → `Trackback::add()` | 실제 `DBModel` + `Textcube.Data.RemoteResponse` 경로로 INSERT SQL 생성 추적 (`_sectest/trackback_sqli_test.php`) | `url`/`site`/`title`/`excerpt`에 따옴표 탈출·UNION·세미콜론 스택·NUL 6종 | **ALL PASS** — 외부입력 전부 `\'` escape, 리터럴 탈출 불가 (url 포함) |
| `api_update_attaches_with_replace` | 정적 추적 | `$entryId`=`$post->id`(정수, 단일 호출 L956), `$newfile['label']`=`POD::escapeString` | 방어(정수 + escape) |

- **부가 확인**: `POD::escapeString($s, $link = null)`의 2번째 인자는 **미사용 레거시 link 파라미터** → escape는 인자와 무관하게 항상 수행(`real_escape_string`/`escape_string`). v3.45의 "DBModel `getQualifierModel`이 `escape=null`일 때 미escape" 함정과는 **별개 경로**이며, 본 진입점들에는 해당하지 않음.
- **결론**: 잔여 외부입력 진입점은 escape로 **SQL Injection이 실제 차단됨**(생성 SQL 실측). prepared statement 전면 전환은 `Paging::fetch`(완성 SQL 수령)·`DBModel`(`setAttribute(escape=true)`) 구조 제약으로 STAGE 3 이연을 유지하나, **현 상태로 SQLi 방어는 확보**됨.
- **severity 재평가 (2026-05-31)**: SQLi 방어가 전수 스캔(신규 취약 0건) + 잔여 4함수 악성입력 실측(ALL PASS, 생성 SQL 추적)으로 확보되어 **실제 SQL Injection 취약점은 부재** → **[HIGH] 해제, [FIXED]로 분류**. prepared statement 전면화는 `Paging::fetch`/`POD::queryAll`(완성 SQL 실행)·전 DBMS Adapter 를 `(sql, params)` bind 방식으로 재설계하고 수십 개 검색·목록 함수 호출처를 수정해야 하는 **프레임워크 재설계 규모의 best-practice 개선(기술부채)**으로, 취약점이 아니므로 회귀 위험을 피해 이연을 유지한다.

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

## [FIXED] StatGraph — jpgraph 1.x QPL 라이선스 → 의존 없는 SVG 그래프 대체 (v3.52, 2026-05-31)

- **위치**: `plugins/StatGraph/count/src/jpgraph.php`
- **내용**: 번들된 jpgraph 라이브러리가 QPL(Q Public License) 라이선스 적용. 상업적 사용 시 별도 라이선스 필요.
- **권고**: Chart.js/ApexCharts 등 MIT 라이선스 대체 라이브러리로 재구현.
- **현황**: **해소 (v3.52, 2026-05-31)**. jpgraph(QPL)를 **외부 라이브러리 의존 없는 인라인 SVG line chart**로 재구현(`DisplayStatisticsGraph()` → `Statistics::getWeeklyStatistics()` 최근 8일 방문수 SVG polyline). 미사용 jpgraph QPL 6파일 + `count.php`(`count/` 디렉토리) 제거. 자체 SVG 코드(GPL)라 라이선스 클린. upstream master도 jpgraph 번들 — 본 포트 개선. 검증: `_sectest/statgraph_svg_test.php` SVG 생성 PHP 7.4/8.4 PASS + 5스테이지 lint. (원본 `Textcube-1.10.10`은 수정 금지로 jpgraph 유지.)

---

## [FIXED] OpenID 2.0 지원 중단 (EOL) → OIDC 재구현 + phpopenid 제거 (STAGE4 v3.53, 2026-05-31)

- **위치**: `framework/legacy/Textcube.Control.OIDC.php`(신규), `framework/legacy/Textcube.Control.Openid.php`(헬퍼 축소), `plugins/CL_OpenID/`, `interface/login/openid/`(+`callback/` 신규), `interface/owner/setting/{account,openid}/`, `library/contrib/phpopenid/`(**제거**)
- **내용**: OpenID 2.0 프로토콜은 대부분의 Provider가 2015년경 지원 종료. phpopenid(JanRain)는 유지보수 중단.
- **조치**: OpenID Connect(OIDC, OAuth2 기반)로 재구현하여 일원화.
  - 경량 자체 구현(`OIDCClient`) — 의존은 openssl + JSON + curl(PHP 내장)뿐, composer/외부 라이브러리 없음.
  - **이중 옵트인(기본 비활성)**: ① CL_OpenID 플러그인 활성 ② 플러그인 설정에서 OIDC 활성화 + issuer/client_id/client_secret 입력. 두 게이트를 모두 충족해야 동작(플러그인 활성화만으로는 비활성).
  - Authorization Code flow + PKCE(S256) + state/nonce + id_token(JWT **RS256 한정**) 검증: JWKS 서명·iss·aud·exp·nonce 일치.
  - 게스트 댓글: claims → `Acl 'openid'` 식별자(`oidc:{iss}#{sub}`) 단일 출처 주입 → 기존 댓글 저장/조회/삭제·폼 흐름 무수정 호환.
  - 사용자/관리자 로그인: `UserSettings`의 `openid.*` **명시적 연결** 매핑만(자동 계정생성 없음). sub 기반 식별자라 email 공유·provider 사칭으로 매핑 가로채기 불가.
  - OpenID 2.0 완전 제거: phpopenid(53파일×5위치 = 265파일) 삭제, `Openid.php`를 헬퍼 전용으로 축소(전 메서드 **static화** — PHP 8 의 non-static 정적 호출 fatal 동시 해소), login/account/setting 의 2.0 흐름 제거, 죽은 상수(`OPENID_LIBRARY_ROOT`, `Auth_OpenID_NO_MATH_SUPPORT`) 정리.
- **악성 입력 테스트**(PoC, `_sectest/oidc_*.php`, PHP 7.4/8.4 컨테이너):
  - 이중 옵트인 게이트 6케이스: 플러그인 비활성/설정 빈/부분설정(issuer·secret 누락)은 모두 `blocked`, 완비 시에만 `ENABLED`.
  - id_token 적대적 9케이스: 정상 토큰 통과 + **서명 위조(공격자 키)/서명 비트 변조/alg=none/alg=HS256 다운그레이드/aud 불일치/iss 불일치/exp 만료/nonce 재사용** 전부 `차단`.
  - 신원 매핑: 미연결→게스트(자동승격 없음), 연결+writers→사용자 세션 승격, 연결+non-writers→권한 식별만, **다른 issuer 동일 sub→매핑 거부**(provider 사칭 차단).
- **현황**: 해소됨 (STAGE4 v3.53 / STAGE3 v2.50 / STAGE2 v2.23 / STAGE1 1.97; 코드는 전 STAGE 적용, 원본 `Textcube-1.10.10` 제외).

---

## [LOW] 쿠키 속성 누락

- **위치**: `framework/legacy/Textcube.Control.Session.php`·`Session.Memcached.php`, `library/preprocessor.php`, `library/auth.php`, `framework/legacy/Textcube.Control.Openid.php`, `interface/blog/comment/{comment,add}/index.php`
- **내용**: `setcookie()` / `session_set_cookie_params()` 호출에 `HttpOnly`/`SameSite`/`Secure` 속성 미설정. XSS를 통한 세션 쿠키 탈취 및 CSRF(SameSite 미설정) 노출.
- **권고**: `setcookie($name, $value, ['httponly'=>true,'samesite'=>'Lax','secure'=>true,...])`
- **현황**: **해소 (v3.51, 2026-05-31)**. 7개 파일 12개 호출을 PHP 7.4+ 배열 옵션으로 전환 — `httponly=>true`, `samesite=>'Lax'`, `secure=>(bool)service.useSSL`. **Secure는 `useSSL=false`(HTTP) 운영에서 쿠키가 정상 전송되도록 조건부**(무조건 true 시 로그인 불가 회귀 방지). 모든 쿠키가 서버사이드(`$_COOKIE`)에서만 읽혀 HttpOnly 적용에 회귀 없음. v3.49 CSRF 방어를 환경 비의존으로 보완. upstream master도 레거시 형식(미설정) — 본 포트 개선. 검증: PHP 7.4/8.4 배열 옵션 형식 + 7파일×5스테이지 lint 통과.

---

## [LOW] 정적 자산 버전 고정 (jQuery 1.11.2 등) — audit 완료, 업그레이드 보류

- **위치**: `framework/id/textcube/config.default.php:19-22`(jQuery/UI/bpopup/Lodash), `plugins/ED_tinyMCE/tinymce/`(TinyMCE), `skin/blog/periwinkle/js/`(jQuery UI 1.10.3)
- **내용**: 10년+ 전 프런트엔드 자산 고정. 알려진 XSS·prototype pollution 포함.
- **인벤토리 + 주요 CVE** (audit 2026-05-31):

  | 자산 | 버전 | 연도 | 주요 CVE |
  |------|------|------|----------|
  | jQuery | 1.11.2 | 2014 | CVE-2020-11022/11023(XSS, `html()`), CVE-2019-11358(proto pollution), CVE-2015-9251 |
  | jQuery UI | 1.11.2 + 1.10.3(skin) | 2014/2013 | CVE-2021-41182/41183/41184, CVE-2022-31160, CVE-2016-7103 (XSS) |
  | Lodash | 2.4.1 | 2013 | **CVE-2019-10744(proto pollution, Critical 9.8)**, CVE-2020-8203, CVE-2018-3721/16487 |
  | bpopup | 0.10.0 | 2013 | — |
  | TinyMCE | 4.1.10 | 2014 | 4.x 초기 다수 XSS (4.9.11이 4.x 최종 보안패치) |

- **TinyMCE 커스텀 번들 비교** (원본 `Textcube-1.10.10` 대비 확인):
  - TinyMCE 4.1.10(LGPL 2.1)은 **커스텀 번들** — 공식 외 플러그인 **`TTMLsupport`**(textcube TTML 마크업 ↔ HTML, 첨부/이미지/미디어 변환) + **`codemirror`**(소스 편집), 그리고 `override.css`·`images`·`index.php`(에디터 래퍼·설정).
  - textcube-ng 포팅 수정(원본 대비 차이 2건): ① `index.php` — codemirror `jsFiles` 출력을 `implode('\',\'',…)` → **`json_encode()`**(JS 배열 주입 안전화); ② `TTMLsupport/plugin.js`(+`plugin.min.js`) — 오디오 첨부(mp3/ogg/wav/flac/m4a/aac/wma/mid/midi)를 url 객체 대신 **TTML 태그 경로**로 처리(→ `<audio>` 렌더링).
  - **업그레이드 함의**: `TTMLsupport`가 TinyMCE 4.x plugin API 에 의존 → TinyMCE 5/6/7 메이저 업그레이드 시 **커스텀 플러그인 재작성 필수**(고난도). **4.9.11**(4.x 최종)은 plugin API 가 동일하여 커스텀(TTMLsupport/codemirror)을 보존하며 보안패치만 적용 가능.
- **업그레이드 계획**(위험·난이도 순, 미착수): ① Lodash 2.4.1 실사용처 확인 후 제거 또는 4.x(proto pollution Critical 우선) ② jQuery 1.11.2 → 3.7.1(textcube 자체 JS 의 deprecated 제거 API `.live`/`.andSelf`/`$.browser`/`.size()`/`$.parseJSON` **0건** 확인 → 코드 호환 양호, 번들 플러그인 bpopup/placeholder/tagsinput/touch-punch 동반 필요) ③ jQuery UI → 1.13.3(1.10.3/1.11.2 버전 통일) ④ TinyMCE → 4.9.11(커스텀 보존 최소 패치).
- **현황**: **audit 완료(2026-05-31)** — 인벤토리·CVE·커스텀 비교·업그레이드 계획 수립. 업그레이드는 동작 변경 위험(특히 TinyMCE 커스텀 플러그인·jQuery 메이저)으로 단계적·검증 필요 → 별도 작업으로 보류.

---

## [INFO] XMLRPC blogAPI — 보안 검토 (정적 audit + 파서·권한 적대적 실측, 2026-05-31~06-01)

- **위치**: `framework/legacy/Needlworks.PHP.XMLRPC.php`(XMLRPC 디스패처), `framework/boot/10-CoreClasses.php` `XMLStruct`(PHP expat 기반 XML 파서), `library/model/blog.api.php`(metaWeblog/blogger/mt handler), `library/model/blog.response.remote.php`(trackback/pingback)
- **검토 결과**:
  - **XXE / entity 폭탄**: 입력은 `XMLRPC::receive()` → `XMLStruct::open()` → PHP expat(`xml_parser_create`)으로 파싱되며, expat 은 외부 DTD/엔티티를 로드하지 않는다. **적대적 실측**(`_sectest/xmlrpc_xxe_test.php`, PHP 7.4/8.4): `file://` SYSTEM 외부 엔티티·외부 DTD·billion laughs(중첩 internal entity) 입력에 대해 **비밀 파일 미유출 + entity 미확장(cdata 1B) + 즉시 반환 — ALL PASS** → XXE·entity-expansion DoS **미해당(실측 입증)**.
  - **인증**: 모든 API 메서드가 `api_login()` → `Auth::login($id, $password)` 통과 후 동작(실패 시 `XMLRPCFault` 반환). canonical id fallback 포함. 등록된 19개 핸들러 전부 `api_login` 가드 보유(무인증 메서드 없음).
  - **권한(authz)**: 인증 후 Auth(`framework/boot/30-Auth.php:240-263`)가 `Privileges`(userid→blogid,acl)로 **blogid별** `Acl::setAcl` → 권한 미보유 blogid 는 acl 미설정(cross-blog·권한상승 불가). **적대적 실측**(`_sectest/xmlrpc_authz_test.php`, PHP 7.4/8.4): 무 Privileges→권한 0, acl=0→writers 기본만, acl 비트(OWNER/EDITOR/ADMIN) 정확 매핑, blogid 분리, uid=1만 creators, 미정의 비트 권한상승 없음 — **ALL PASS**.
  - **SQLi**: handler 입력은 STAGE 2(v1.88)에서 핵심 INSERT/UPDATE(`api_addAttachment`/`api_update_attaches`)를 prepared statement 로 전환, 나머지는 `POD::escapeString` 으로 escape(#2 방어 확보). **트랙백 수신 경로는 `_sectest/trackback_sqli_test.php` 로 악성입력 실측(ALL PASS)**.
- **잔여 갭(취약점 아님, 개선 여지)**:
  - 로그인 무차별 대입(brute-force) rate limit 부재 — #1 MD5 와 동일 맥락.
  - 비밀번호 MD5 평문 전송 → HTTPS 권장(#1).
  - 메서드 본문(addEntry/deletePost 등)이 부여된 acl 을 실제로 준수하는지의 **종단 강제**는 실DB 통합 테스트 영역(권한 부여 로직·파서·인증·트랙백 SQLi 는 단위/실측 완료).
- **현황**: 정적 audit + 파서·권한 부여 적대적 실측 완료(2026-05-31~06-01). XXE·entity DoS·인증·SQLi·권한 부여(acl/blogid) 측면에서 **critical 미방어 취약점 부재**. 메서드 본문의 acl 종단 강제는 실DB 통합 영역.

---

## [INFO] Clipboard API — HTTPS 필수

- **위치**: `resources/script/common3.js` (`copyUrl()` 함수)
- **내용**: `navigator.clipboard.writeText()` (Clipboard API)는 보안 컨텍스트(HTTPS 또는 localhost)에서만 사용 가능. HTTP 환경에서는 `_legacyCopyUrl()` fallback으로 동작: ① IE `window.clipboardData` → ② `document.execCommand('copy')`(secure context 불필요 → 현대 브라우저 HTTP 자동복사) → ③ 텍스트 선택(수동 복사).
- **권고**: 운영 환경에서 HTTPS 적용 권장(Clipboard API 정식 경로).
- **현황**: 해소(개선) (2026-05-31). HTTPS에서는 Clipboard API 사용(본 포트가 upstream보다 앞서 도입 — master는 IE `clipboardData`+텍스트선택만). HTTP 자동복사를 위해 `_legacyCopyUrl`에 `document.execCommand('copy')` fallback 추가. upstream 미수정 → 포트 자체 개선. 검증: node `--check` 5스테이지 구문 통과. 보안 취약점이 아닌 호환성/UX 개선.

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
- **현황**: 해소됨 (v3.06). `suggest/index.php`의 SQL Injection(`$_GET['input']` 미처리)은 **v3.45에서 DBModel 빌더 전환으로 해소** (아래 [FIXED] 항목 참조).

---

## [FIXED] `control/action/user/suggest` — SQL Injection (`$_GET['input']` 미escape) → DBModel 빌더 전환 (v3.45, 2026-05-31)

- **위치**: `interface/control/action/user/suggest/index.php`
- **내용**: validator를 통과한 `$_GET['input']`(`$IV` 타입 `string` — UTF-8 유효성·길이만 검사, 내용 필터링 없음)이 자동완성 쿼리의 `LIKE "%...%"` 절에 `POD::escapeString()` 없이 직접 concat되어 **SQL Injection** 가능. v3.06의 권한 가드(`requirePrivilege('group.creators')`)로 인증 경계는 좁혔으나, 인증된 creator 권한 사용자 또는 CSRF로 악용될 여지가 남아 있었음.
- **upstream 근거**: Needlworks/Textcube `refs #747`, commit `9c73a64` (2015-02-27) — 동일 파일의 raw 쿼리를 `DBModel` 빌더로 전환. 본 포트 1.10.10 기반에는 미반영 상태였음.
- **조치**: raw `POD::queryAll(...)` → `DBModel` 빌더로 전환.
  - upstream `init()`은 본 포트에 없는 신규 별칭이므로 기존 동등 API `reset()`으로 적응.
  - 본 포트 `DBModel::getQualifierModel()`은 `escape=null`이면 escape하지 않으므로(upstream과 동작 차이), 두 qualifier 모두 `escape=true` 명시하여 `POD::escapeString()` 적용 보장. 미사용 `global $database;` 제거.
  - php7.4, php8.2, php8.4, php8.5 전 버전 동일 적용.
- **방어 검증** (`_sectest/suggest_sqli_test.php`, `php:8.4-cli`): 적용 **전에** 포트의 실제 DBModel 코드 경로(`getQualifierModel` + `_makeWhereClause`)에 악성 입력을 직접 주입하여 생성 SQL을 추적. `POD::escapeString`은 MySQL `real_escape_string`(utf8/utf8mb4, single-byte-safe charset → GBK류 멀티바이트 우회 불성립) 동작을 충실히 모델링.

  | 악성 입력 | 생성된 WHERE (요약) | 판정 |
  |-----------|---------------------|------|
  | `' OR '1'='1` | `name LIKE '%\' OR \'1\'=\'1%'` | SAFE |
  | `\' OR 1=1 -- ` | `name LIKE '%\\\' OR 1=1 -- %'` | SAFE |
  | `"; DROP TABLE tc_Users;--` | `name LIKE '%\"; DROP TABLE tc_Users;--%'` | SAFE |
  | `' UNION SELECT loginid,password FROM tc_Users -- ` | `name LIKE '%\' UNION SELECT ...%'` | SAFE |
  | `admin'-- ` | `name LIKE '%admin\'-- %'` | SAFE |
  | `a\0' OR 1=1` (NUL 바이트) | `name LIKE '%a\0\' OR 1=1%'` | SAFE |

  악성 입력 7종(평범한 입력 1 + 공격 6) **ALL PASS** — 모든 작은따옴표가 `\'`로, 백슬래시가 `\\`로 escape되어 문자열 리터럴 탈출 불가. NUL 바이트는 `00-UnifiedEnvironment.php`의 `normalizeSuperglobalInput`이 이 코드 이전에 선행 제거(이중 방어).
- **현황**: 해소됨 (v3.45).

---

## [FIXED] `control/action/user/suggest` — 반사형 + 저장형 XSS (JS/innerHTML 출력 인코딩) (v3.46, 2026-05-31)

- **위치**: `interface/control/action/user/suggest/index.php` (+ 소비처 `resources/script/control.js`)
- **내용**: 응답(`text/javascript`)이 `control.js`의 동적 `<script src>`로 **JS 실행**되는 JSONP 구조. 두 경로:
  1. **반사형**: `$_GET['id']`(`$IV` `string`)·`$_GET['cursor']`를 JS 문자열 리터럴에 escape 없이 echo → 리터럴 탈출 시 임의 JS 실행. 정상 흐름의 `id`는 고정 DOM id `"suggestContainer"`(비-사용자 입력)이고 `requireStrictRoute`(Referer 검증)+`requirePrivilege('group.creators')` 가드가 있어 **실질 심각도 LOW**이나, 가드 우회 대비 심층방어.
  2. **저장형**: 결과 행 `loginid - name`이 `showSuggestion`의 **`innerHTML` sink**(control.js L58)에 escape 없이 삽입 → 사용자 `name`에 `<img onerror>` 등 포함 시 실행. `control.js`가 `replaceAll("&quot;",'"')`로 HTML escape된 입력을 되돌리는 설계 전제였으나 서버가 escape를 누락하여 발생.
- **upstream**: master의 `suggest/index.php`·`control.js` 모두 동일하게 미escape — upstream 미수정. 포트 내 기존 관용구로 방어.
- **조치**:
  - 반사형: `$_GET['id']`·`$_GET['cursor']` → `escapeJSInCData()` (`library/function/javascript.php`). `control`은 `Dispatcher.php`에서 interfaceType `owner`로 매핑되어 해당 헬퍼 로드 확인.
  - 저장형: 결과 행 → `htmlspecialchars($v, ENT_QUOTES)` 후 백슬래시·CR·LF를 JS 리터럴 안전 형태로 `str_replace` escape (`control.js`의 `&quot;` 되돌림 설계와 정합).
  - php7.4, php8.2, php8.4, php8.5 전 버전 동일 적용.
- **방어 검증** (실제 JS 엔진 node, `php:8.4-cli`로 출력 생성):
  - 반사형(`_sectest/run_xss_node.js`): `");alert(document.cookie);//`·`</script>…`·개행/백슬래시 우회·이벤트 핸들러 등 6종 → `alert` 미실행·`cookie` 미접근, 페이로드 문자열 인자로 흡수. **ALL PASS**.
  - 저장형(`_sectest/run_xss_innerhtml_node.js`): `<img src=x onerror=alert(1)>`·`"><script>…`·JS 리터럴 탈출·백슬래시·개행 → fixed 모드 전부 SAFE(코드 미실행 + 클라이언트 `replaceAll` 후 raw `<>` 미잔존). 대조군 raw(미수정)는 전부 VULNERABLE로 탐지(JS 리터럴 탈출 raw는 `alert` 실제 실행) → 테스트 유효성 입증.
- **현황**: 해소됨 (v3.46).

## [FIXED] `owner/communication/comment`·`notify` / `owner/entry` — 반사형 XSS (HTML 속성 출력 인코딩 누락) (v3.47, 2026-05-31)

- **위치**:
  - `interface/owner/communication/comment/index.php` (`name`, `search`, `status`)
  - `interface/owner/communication/notify/index.php` (`search`)
  - `interface/owner/entry/index.php` (`visibility`)
- **내용**: 검색/필터 폼의 hidden `<input … value="…">` 속성에 `$_POST` 값을 escape 없이 echo. 해당 변수들은 `$IV`에서 `string` 타입이라 validator가 UTF-8·길이만 검사하고 **내용 필터링은 하지 않음** → `"><script>…`·`" onmouseover="…` 등으로 속성·태그 탈출 시 임의 스크립트 실행. 동일 파일 `comment/index.php:557`의 노출형 검색창은 이미 `htmlspecialchars($search)`를 쓰고 있어, hidden input만 인코딩이 누락된 불일치였음.
- **검증된 안전 변수**(같은 echo 블록): `ip`(`ip` 타입), `category`(`int`), `withSearch`(enum `array('on')`), `tagId`(`int`), plugin `visibility`(`$IV` 미선언 → validator drop) — 타입으로 방어되나, 출력 인코딩 정석·일관성을 위해 같은 블록의 reflected 값은 모두 `htmlspecialchars`로 통일.
- **upstream**: master의 `comment`·`notify`·`entry` index.php 모두 동일하게 미escape — upstream 미수정. 포트 내 기존 관용구(`htmlspecialchars(ENT_QUOTES)`, `comment:557`과 동일)로 방어.
- **조치**:
  - 각 sink의 reflected 값 → `htmlspecialchars($_POST['…'], ENT_QUOTES)` (이중따옴표 속성이므로 `"`·`'` 동시 escape).
  - php7.4·8.2·8.4·8.5 + 릴리즈 전 버전 동일 적용(15개 파일, 각 1:1 치환). 임시 Python 스크립트로 바이너리 치환하여 CRLF 보존.
- **악성 입력 검증** (실제 HTML 파서 — PHP `DOMDocument`, `php:8.4-cli`, `_sectest/xss_attr_test.php`):

| PoC 입력 | RAW(미수정) | FIXED |
|---------|------------|-------|
| `"><script>alert(document.cookie)</script>` | `<script>` 노드 생성(실행) | value에 데이터로 흡수, 위험노드 0 |
| `"><img src=x onerror=alert(1)>` | `<img onerror>` 노드 생성 | 0 노드·0 이벤트 |
| `" onmouseover="alert(1)` | 주입 이벤트 속성 생성 | 0 이벤트 |
| `"></input><script>alert(1)</script>` | `<script>` 노드 생성 | 0 노드 |
| `"><iframe src=javascript:alert(1)>` | `<iframe>` 노드 생성 | 0 노드 |
| `normal-search-term`(정상) | 정상 | 정상 보존(기능 유지) |

  - 판정: FIXED 모드에서 위험 노드·주입 이벤트 핸들러 **0개**, `value` 속성은 페이로드 원문을 **데이터로만** 보존. 대조군 RAW는 동일 파서에서 실제 위험 노드/이벤트가 생성됨을 확인 → 테스트 유효성 입증. **ALL PASS**.
- **현황**: 해소됨 (v3.47).

## [FIXED] `owner/help` — 경로순회/LFI (`$_GET['lang']` 미정규화) (v3.48, 2026-05-31)

- **위치**: `interface/owner/help/index.php`
- **내용**: 도움말 파일명을 `$filename = $_GET['lang'].'.'.$_GET['subject'].'.html'`로 구성해 `file_get_contents(ROOT."/interface/owner/help/".$filename)`로 읽음. `subject`는 `$IV` `filename` 타입(`^\w+(\.\w+)*$`)이라 안전하나, **`lang`은 `string` 타입**이라 내용 필터링이 없어 `../`·`..\`·`....//` 등으로 help 디렉토리를 이탈 가능. `.html` suffix와 중간 `.subject.`로 임의 파일 읽기는 제한되나, 디렉토리 트래버설로 의도치 않은 `*.<word>.html` 파일 접근(정보 노출)이 성립. 이 액션은 `preprocessor`만 거치고 `requireOwnership`/`requirePrivilege` 가드가 없어 인증 사용자 범위에서 노출.
- **부가 발견**: `Validator::language`는 정규식 delimiter가 백슬래시(`preg_match('\^[[:alpha:]]{2}…')`)로 깨져 있어 PHP에서 항상 에러/false → `language` 타입은 검증 수단으로 사용 부적합(fail-safe로 항상 reject되나 기능 파손). 따라서 `lang` 방어는 `language` 타입 대신 화이트리스트로 적용.
- **upstream**: master의 `help/index.php`도 동일하게 `lang`을 `string`으로 받고 sanitize 없음 — upstream 미수정. 포트에서 직접 방어.
- **조치**: 경로 결합 전 `$lang = preg_replace('/[^A-Za-z0-9_\-]/', '', $_GET['lang']);` — 언어코드(영숫자·밑줄·하이픈)만 남기고 구분자(`/`·`\`)·점·`..` 전부 제거. `basename()`은 Linux에서 `\`를 구분자로 보지 않아 Windows 배포 시 백슬래시 우회가 가능하므로 화이트리스트를 택함. php7.4·8.2·8.4·8.5 + 릴리즈 전 버전 동일 적용.
- **악성 입력 검증** (`_sectest/help_lfi_test.php`, `php:8.4-cli`, 경로 정규화로 help 디렉토리 이탈 판정):

| PoC `lang` 입력 | RAW(미수정) | FIXED |
|----------------|------------|-------|
| `../../../../../../etc/passwd` | help 디렉토리 이탈(OUT) | 디렉토리 내(in) |
| `../../config` | 이탈 | 내부 |
| `foo/../../../bar` | 이탈 | 내부 |
| `ko/../../secret` | 이탈 | 내부 |
| `ko\..\..\win`(백슬래시) | 이탈 | 내부 |
| `ko`·`en`(정상) | 정상 | 정상 보존(기능 유지) |

  - 판정: FIXED는 모든 페이로드에서 최종 파일명에 디렉토리 구분자 없음 + 정규화 경로가 help 디렉토리 prefix 유지 → 이탈 0. 대조군 RAW는 동일 정규화에서 디렉토리 이탈 확인 → 테스트 유효성 입증. **ALL PASS**.
- **현황**: 해소됨 (v3.48).

## [FIXED] owner 상태변경 액션 CSRF 가드 누락 + `requireStrictRoute` path-모드 강화 (v3.49, 2026-05-31)

- **위치**: `library/auth.php`(가드 함수) + 가드 누락 13개 액션(`interface/owner/...`)
- **내용 (두 결함)**:
  1. **CSRF 가드 누락**: 일부 상태변경 액션이 `requireStrictRoute()`(Referer 동일출처 검증) 미보유. 권한 자체는 `preprocessor.php`의 `requireOwnership()` + `Aco::getRequiredPrivFromUrl()`(URL별 권한)로 **중앙 방어**되나, CSRF는 개별 액션 의존. 같은 기능군의 `comment/delete`(휴지통 이동)·`trash/emptyTrash`는 가드 보유 ↔ 더 파괴적인 `trash/comment/delete`(영구삭제)·`trash/*/revert`는 누락 → **실수 누락**. 다수가 `$suri['id']` **GET 트리거**라 SameSite=Lax(브라우저 기본, 코드 미설정)로도 top-level navigation을 통해 CSRF 성립.
  2. **path-모드 cross-blog 미차단**: `requireStrictRoute`가 **host만 비교**(`$refererHost === $serverHost`). textcube `service.type=path`(`example.com/basePath/{blogname}`)는 모든 블로그가 같은 host를 공유 → 같은 host의 다른 블로그가 악성 페이지 호스팅 시 Referer host 동일 → 통과(cross-blog CSRF). 기존 96개 가드 보유 액션 전부 동일한 한계.
- **권한 관점(정정)**: 최초 "권한 가드 누락" 의심은 부정확 — owner/reader는 preprocessor에서 소유권·ACL이 중앙 강제됨. 쟁점은 CSRF 한정.
- **트리거 / 심각도 분류**:

| 액션 | sink | 트리거 | 비고 |
|------|------|--------|------|
| `trash/comment/delete`·`revert`, `trash/trackback/delete`·`revert` | 댓글/트랙백 영구삭제·복원 | GET `$suri['id']` | Lax 완화 약함 |
| `skin/coverpage/delete`·`skin/sidebar/delete` | setBlogSetting(모듈 삭제) | GET | owner 레이아웃 |
| `setting/domain/primary`·`secondary` | setPrimary/SecondaryDomain | GET | 도메인 설정 |
| `data/optimize`·`export`, `entry/attachmulti/orphandelete` | OPTIMIZE/백업/첨부삭제 | GET | 유지보수·DoS성 |
| `setting/userSetting/set`, `skin/adminSkin/set` | setBlogSettingGlobal | POST | Lax 완화 강함 |
| (오탐 제외) `network/teamblog/changeBlog` | redirect만 | — | DB write 없음 |

- **upstream**: master도 `trash/comment/delete`·`userSetting/set` 가드 미보유 + `requireStrictRoute` host-only 동일 — 미수정. 포트에서 보강.
- **조치**:
  - **(A) `requireStrictRoute()` 강화** (본 포트 개선, upstream 미존재): 판정을 순수 헬퍼 `__isReferentSameBlogScope($referer,$hostHeader,$serviceType,$basePath,$blogName)`로 분리. host≠ → 차단(기존). host 동일 시 — `service.type !== 'path'`(single/domain)는 **기존 동작 그대로 통과(회귀 0)**, `path`는 Referer의 `basePath` 제거 후 첫 세그먼트(blogname)를 현재 `blog.name`과 `===` 비교(**추출 실패 시 fail-open**으로 회귀 방지하되, 그 발생을 `trigger_error(E_USER_NOTICE)`로 기록 — textcube 코어 관행(`Validator`)과 일관, 운영 가시성 확보. 성공+불일치만 차단). `getBlogURL` path 형식(`…/basePath/blogname`)과 정합, fancyURL 무관. 판정은 순수 상태(`pass`/`block`/`failopen`) 반환이라 단위 테스트 가능.
  - **(B) 13개 액션에 `requireStrictRoute()` 추가** — 보유측(`comment/delete:14`)과 동일 패턴(`require preprocessor` 직후). 오탐 `changeBlog` 제외.
  - php7.4·8.2·8.4·8.5 + 릴리즈 전 버전 적용(auth.php 5 + 13×5=65 파일). 임시 Python 바이너리 치환으로 CRLF 보존.
- **악성 입력 / 회귀 검증** (`_sectest/csrf_strictroute_test.php`, `php:8.4-cli`, 16 케이스):

| type | basePath | blog.name | HTTP_HOST | Referer | 기대 | 결과 |
|------|---------|-----------|-----------|---------|------|------|
| single | '' | - | ex.com | http://ex.com/owner | 통과(회귀) | PASS |
| single | '' | - | ex.com | http://evil.com | 차단 | PASS |
| single | '' | - | ex.com | (무Referer) | 차단 | PASS |
| domain | '' | - | a.ex.com | http://b.ex.com/owner | 차단 | PASS |
| path | '' | myblog | ex.com | http://ex.com/myblog/owner | 통과(회귀) | PASS |
| path | '' | myblog | ex.com | http://ex.com/**other**/owner | **차단(강화)** | PASS |
| path | '/tc' | myblog | ex.com | http://ex.com/tc/**other**/owner | **차단(강화)** | PASS |
| path | '' | blog | ex.com | http://ex.com/**blog2**/owner | **차단**(prefix함정) | PASS |
| path | '' | myblog | ex.com | http://ex.com/ (blogname 없음) | 통과(fail-open) | PASS |
| path | '' | myblog | ex.com | http://evil.com/myblog | 차단(host) | PASS |

  - 전 18 케이스 **ALL PASS**(회귀 케이스 `pass` + 강화 케이스 `block` + blogname 미추출 `failopen` 동시 입증). 추가: 13개 액션 `requireStrictRoute` 존재 grep + auth.php·13개 액션 PHP lint 통과.
- **잔여 한계(정직한 명시)**: 강화로 path-모드 cross-blog가 차단되나, 이는 host+blogname Referer 기반 방어다. blogname 추출 실패 시 fail-open(회귀 우선)하므로 비표준 URL 구조에선 cross-blog 차단이 적용되지 않을 수 있다(해당 fail-open 발생은 `trigger_error(E_USER_NOTICE)`로 로깅되어 사후 탐지·튜닝 가능). 완전한 출처 독립 방어는 블로그별 CSRF 토큰이 필요하나, fork의 플러그인 호환성·기존 사용자 영향을 고려해 채택하지 않았다.
- **현황**: 해소됨 (v3.49).
