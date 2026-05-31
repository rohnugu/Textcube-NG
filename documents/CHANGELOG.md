# CHANGELOG — Textcube 1.10.10 → PHP 8.2 호환 (STAGE 2)

대상 PHP: 8.2  
기반 버전: php7.4-Textcube-1.10.10 (STAGE 1, v1.74)  
작성일: 2026-05-15

이전 단계 누적분(v1.1 ~ v1.74): `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조

---

## v1.75 — STAGE 2 메타 파일 갱신 (2026-05-15)

- `CHANGELOG.md`: STAGE 2 헤더로 교체. 기반 버전 v1.74, 이전 누적분 php7.4 CHANGELOG 참조 명시.
- `SECURITY.md`: 헤더 STAGE 2로 갱신. 모든 항목 현황을 STAGE 2 시점으로 재평가.
  - `[MEDIUM] phpmailer 5.x each()` → 현황: v1.81에서 phpmailer 6.9.x 교체 예정.
  - `[MEDIUM] 세션 토큰 단순 문자열 비교` → 현황: v1.84에서 `hash_equals()` 도입 예정.
  - `[HIGH] Raw SQL` → 현황: v1.85~v1.89에서 핵심 진입점 Prepared Statement 변환 예정.
  - phpopenid PHP4 스타일 생성자 PHP 8.0 deprecated 항목 신설.
  - phpopenid 동적 프로퍼티 PHP 8.2 deprecated 항목 신설.
  - `${}` 문자열 보간 PHP 8.2 deprecated 항목 신설.
- `README.md`: REQUIREMENTS 섹션 PHP 7.4 → PHP 8.2 갱신.
- 디렉토리 복사: `php7.4-Textcube-1.10.10/`(v1.74) → `php8.2-Textcube-1.10.10/` (attach/, cache/ 제외).

---

## v1.76 — phpopenid PHP4 스타일 생성자 → `__construct()` 변환 (PHP 8.0 deprecated, PHP 9.0 제거)

- **제거 이유**: PHP 8.0에서 PHP4 스타일 생성자(클래스명과 동일한 메서드명) deprecated. PHP 9.0에서 제거 예정. (php.net/migration80.deprecated)
- **PHP 권고**: 생성자는 반드시 `__construct()`로 명명할 것.
- **적용 대상**: `library/contrib/phpopenid/` 하위 24개 파일, 총 56건

| 파일 | 라인 | 변경 전 | 변경 후 |
|------|------|---------|---------|
| `Auth/OpenID/Association.php` | 131 | `function Auth_OpenID_Association(` | `function __construct(` |
| `Auth/OpenID/Association.php` | 526 | `function Auth_OpenID_AssociationPool(` | `function __construct(` |
| `Auth/OpenID/AX.php` | 72 | `function Auth_OpenID_AX_Error(` | `function __construct(` |
| `Auth/OpenID/AX.php` | 154 | `function Auth_OpenID_AX_AttrInfo(` | `function __construct(` |
| `Auth/OpenID/AX.php` | 272 | `function Auth_OpenID_AX_KeyValueMessage(` | `function __construct(` |
| `Auth/OpenID/AX.php` | 543 | `function Auth_OpenID_AX_FetchRequest(` | `function __construct(` |
| `Auth/OpenID/AX.php` | 796 | `function Auth_OpenID_AX_FetchResponse(` | `function __construct(` |
| `Auth/OpenID/AX.php` | 993 | `function Auth_OpenID_AX_StoreRequest(` | `function __construct(` |
| `Auth/OpenID/Consumer.php` | (확인 후) | PHP4 생성자 9건 | `function __construct(` |
| `Auth/OpenID/DiffieHellman.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/OpenID/Discover.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/OpenID/DumbStore.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/OpenID/FileStore.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/OpenID/MDB2Store.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/OpenID/MemcachedStore.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/OpenID/Message.php` | (확인 후) | PHP4 생성자 3건 | `function __construct(` |
| `Auth/OpenID/PAPE.php` | (확인 후) | PHP4 생성자 2건 | `function __construct(` |
| `Auth/OpenID/Parse.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/OpenID/PredisStore.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/OpenID/SQLStore.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/OpenID/Server.php` | (확인 후) | PHP4 생성자 16건 | `function __construct(` |
| `Auth/OpenID/ServerRequest.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/OpenID/SReg.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/Yadis/HTTPFetcher.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/Yadis/Manager.php` | (확인 후) | PHP4 생성자 2건 | `function __construct(` |
| `Auth/Yadis/ParseHTML.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/Yadis/ParanoidHTTPFetcher.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/Yadis/Yadis.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `Auth/Yadis/XRDS.php` | (확인 후) | PHP4 생성자 2건 | `function __construct(` |
| `Auth/Yadis/XRIRes.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |
| `contrib/signed_assertions/AP.php` | (확인 후) | PHP4 생성자 1건 | `function __construct(` |

**v1.76 추가 발견 (2026-05-16) — StatGraph 플러그인 jpgraph 1.x PHP4 생성자**: STAGE 2 전수 점검 중 `plugins/StatGraph/count/src/` 하위 4개 파일에서 PHP4 스타일 생성자 28건 추가 발견. 동일 방식으로 변환 완료.

| 파일 | 변환된 생성자 |
|------|--------------|
| `plugins/StatGraph/count/src/jpgraph.php` | `JpGraphErrObject`, `JpgTimer`, `DateLocale`, `FuncGenerator`, `Footer`, `Graph`, `TTF`, `Text`, `GraphTabTitle`, `SuperScriptText`, `Grid`, `Axis`, `Ticks`, `LinearTicks`, `LinearScale`, `RGB`, `Picture`, `RotPicture`, `ImgStreamCache`, `Legend`, `Plot`, `PlotLine` (22건) |
| `plugins/StatGraph/count/src/jpgraph_gradient.php` | `Gradient` (1건) |
| `plugins/StatGraph/count/src/jpgraph_line.php` | `LinePlot`, `AccLinePlot` (2건) |
| `plugins/StatGraph/count/src/jpgraph_scatter.php` | `FieldArrow`, `FieldPlot`, `ScatterPlot` (3건) |

**v1.76 추가 발견 (2026-05-16) — framework/legacy 및 phpxpath 라이브러리**: STAGE 2 전수 점검 중 추가 발견.

| 파일 | 변환 내용 |
|------|-----------|
| `framework/legacy/Needlworks.Cache.PageCache.Legacy.php` | `pageCache`, `queryCache`, `globalCacheStorage` 생성자 (3건) |
| `library/contrib/phpxpath/XPath.class.php` | `XPathBase`, `XPathEngine`, `XPath` 생성자 (3건) + `parent::XPathBase()` → `parent::__construct()`, `parent::XPathEngine(...)` → `parent::__construct(...)` (3건) |

---

## v1.77 — phpopenid 부모 생성자 호출 변환 (v1.76 후속)

- **변경 이유**: PHP4 스타일 생성자 제거 후 `parent::ClassName()` 형태의 부모 생성자 호출도 동일하게 무효화. `parent::__construct()`로 명시 변환 필요.
- **적용 파일**: `library/contrib/phpopenid/Auth/OpenID/Server.php`, `Auth/OpenID/AX.php`

| 파일 | 변경 내용 |
|------|-----------|
| `Auth/OpenID/Server.php` | `parent::Auth_OpenID_ServerError(...)` → `parent::__construct(...)` (4건) |
| `Auth/OpenID/AX.php` | `$this->Auth_OpenID_AX_KeyValueMessage(...)` → `parent::__construct(...)` (상속 트리 확인 후 결정, 1건) |

---

## v1.78 — 문자열 보간 `${}` 형태 deprecated 대응 (PHP 8.2)

- **제거 이유**: PHP 8.2에서 `"${varname}"` 및 `"${expr}"` 형태의 문자열 내 변수 보간이 deprecated. PHP 9.0에서 제거 예정. (php.net/migration82.deprecated)
- **PHP 권고**: `"{$varname}"` 또는 `"$varname"` 형태 사용.

| 파일 | 라인 | 변경 전 | 변경 후 |
|------|------|---------|---------|
| `interface/owner/network/xfn/index.php` | 10 | `"Location: ${_SERVER['REQUEST_URI']}"` | `"Location: {$_SERVER['REQUEST_URI']}"` |

*전수 grep 결과 잔존 `${` 패턴 추가 발견 시 동일 적용.*

---

## v1.79 — phpopenid 동적 프로퍼티 deprecated 대응 (PHP 8.2)

- **제거 이유**: PHP 8.2에서 미선언 프로퍼티에 동적 할당(dynamic property creation) deprecated. PHP 9.0에서 제거 예정. (php.net/migration82.deprecated)
- **PHP 권고**: 클래스에 `#[AllowDynamicProperties]` 어트리뷰트 추가 (마이그레이션 임시 대응) 또는 프로퍼티 명시 선언.
- **적용**: 하기 클래스 정의 직전 단독 라인으로 `#[AllowDynamicProperties]` 추가.

| 파일 | 클래스 |
|------|--------|
| `Auth/OpenID/Server.php` | `Auth_OpenID_ServerError`, `Auth_OpenID_MalformedReturnURL`, `Auth_OpenID_UntrustedReturnURL`, `Auth_OpenID_Server`, `Auth_OpenID_AssociateRequest`, `Auth_OpenID_CheckIDRequest`, `Auth_OpenID_SigningEncoder`, `Auth_OpenID_Decoder` |
| `Auth/OpenID/Discover.php` | `Auth_OpenID_ServiceEndpoint` |
| `Auth/OpenID/Consumer.php` | `Auth_OpenID_Consumer`, `Auth_OpenID_GenericConsumer`, `Auth_OpenID_AuthRequest` |
| `Auth/OpenID/AX.php` | `Auth_OpenID_AX_FetchRequest`, `Auth_OpenID_AX_KeyValueMessage`, `Auth_OpenID_AX_AttrInfo` |
| `Auth/Yadis/Manager.php` | `Auth_Yadis_Manager`, `Auth_Yadis_Discovery` |

---

## v1.80 — nullable 인자 deprecated 대응 (PHP 8.1)

- **제거 이유**: PHP 8.1에서 nullable이 아닌 파라미터에 `null` 전달 시 deprecated 경고. (php.net/migration81.deprecated)
- **PHP 권고**: null을 받을 수 있는 파라미터는 `?type` 또는 `type|null`로 명시 선언. 또는 호출 지점에서 null 전달 방지.

| 파일 | 라인 | 변경 전 | 변경 후 |
|------|------|---------|---------|
| `library/contrib/phpopenid/Auth/OpenID/Message.php` | 678 | `htmlspecialchars($val)` | `htmlspecialchars($val ?? '')` |
| `library/contrib/phpopenid/Auth/OpenID/Message.php` | 687 | `htmlspecialchars($val)` | `htmlspecialchars($val ?? '')` |
| `library/contrib/phpopenid/Auth/OpenID/Message.php` | 691 | `htmlspecialchars($val)` | `htmlspecialchars($val ?? '')` |

*컨테이너 error_log의 "Passing null to parameter" 추가 검출 시 동일 처리.*

---

## v1.81 — phpmailer 5.x → 6.9.x 교체 (PHP 8.0 `each()` 제거 대응)

- **제거 이유**: PHP 8.0에서 `each()` 제거. phpmailer 5.x의 `class.phpmailer.php:342,369,453` 및 `class.smtp.php`에서 `each()` 사용 → PHP 8.x에서 Fatal Error 발생. (php.net/migration80.removed-functions)
- **PHP 권고**: phpmailer 6.x는 PHP 8.x 공식 지원, namespace 도입, `each()` 완전 제거.
- **호출 진입점**: `library/function/mail.php` (단 1곳 검증 완료).

| 파일 | 변경 내용 |
|------|-----------|
| `library/contrib/phpmailer/src/PHPMailer.php` | phpmailer 6.9.x 신규 파일 배치 |
| `library/contrib/phpmailer/src/SMTP.php` | phpmailer 6.9.x 신규 파일 배치 |
| `library/contrib/phpmailer/src/POP3.php` | phpmailer 6.9.x 신규 파일 배치 |
| `library/contrib/phpmailer/src/Exception.php` | phpmailer 6.9.x 신규 파일 배치 |
| `library/contrib/phpmailer/legacy_shim.php` | 신규 생성. 6.x 파일 require + `class_alias(PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer')` 등 전역 별칭 |
| `library/function/mail.php` | include 경로를 `legacy_shim.php`로 변경 |

*기존 phpmailer 5.x 파일(`class.phpmailer.php` 등)은 `library/contrib/phpmailer/legacy_v5/`로 이동 (삭제 아님, 사용자 확인 후).*  
*SECURITY.md `[MEDIUM] phpmailer 5.x each()` → `[FIXED]` 갱신.*

---

## v1.82 — GD 리소스 → GdImage 객체 호환 점검 (PHP 8.0)

- **변경 이유**: PHP 8.0에서 GD 함수 반환값이 `resource`에서 `GdImage` 객체로 변경. `is_resource($img)`가 `false` 반환하는 코드가 있을 경우 GD 기능 전체 중단. (php.net/migration80.other-changes)
- **점검 결과**: `framework/utils/Image.php`, `library/function/watermark.php`, `interface/blog/imageResizer.php` — `is_resource()` GD 검사 없음 확인. 추가 수정 불필요.
- `imagecreate()` 음수 인자 등 `ValueError` 발생 가능 지점 입력 검증 추가 (발견 시 적용).

---

## v1.83 — pecl/memcache PHP 8.x 호환 점검

- **점검 대상**: `framework/cache/Memcache.php`, `library/preprocessor.php`의 `Memcache::connect($host, $port)` (STAGE 1 v1.70에서 포트 파라미터 명시화 완료).
- **점검 결과**: tc-php82 컨테이너에서 pecl/memcache 빌드/동작 확인 후 기록.
- pecl/memcache 8.x 빌드 실패 시 대응 방안(pecl/memcached 어댑터 신설 또는 비활성화)은 사용자 확인 후 결정.

---

## v1.84 — `hash_equals()` 도입 — Timing attack 대응

- **변경 이유**: 인증 토큰/비밀번호 비교를 `===`로 수행할 경우 timing attack(실행 시간 차이로 비교값 유추) 취약. PHP 5.6+에서 `hash_equals()`가 상수 시간 비교를 보장. (php.net/function.hash-equals)
- **PHP 권고**: 비밀번호·토큰·해시 비교는 항상 `hash_equals()` 사용.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/boot/30-Auth.php` | 인증 토큰 비교 `($authtoken === $password)` → `hash_equals($authtoken, $password)` |

*전수 grep 결과 비밀번호/토큰/해시 비교 추가 발견 시 동일 처리.*  
*SECURITY.md `[MEDIUM] 세션 토큰 단순 문자열 비교` → `[FIXED]` 갱신.*

---

## v1.85 — POD 어댑터에 Prepared Statement API 추가

- **변경 이유**: SQL Injection 대응. 기존 `POD::query()`는 문자열 임베딩 방식이며 escape 누락 시 취약. Prepared Statement API를 어댑터에 추가하여 `?` placeholder 방식 사용 가능화.
- **PHP 권고**: `mysqli::prepare()` / `mysqli_stmt::bind_param()` / `mysqli_stmt::execute()` 사용. (php.net/mysqli.prepare)

| 파일 | 변경 내용 |
|------|-----------|
| `framework/data/MySQLi/Adapter.php` | `prepare($query)`, `bindAndExecute(mysqli_stmt, $types, ...$params)`, `fetchAllStmt(mysqli_stmt)` 정적 메서드 추가 |
| `framework/data/IAdapter.php` | 동일 시그니처 인터페이스 선언 추가 |
| 비-MySQLi 어댑터 (SQLite3 등) | `throw new RuntimeException('Prepared statements not supported')` 기본 구현 추가 |

---

## v1.86 — 핵심 인증 진입점 Prepared Statement 변환 + PRNG 보안 강화

- **변경 이유**: 외부 입력(loginid, password)이 직접 SQL에 삽입되는 인증 쿼리는 SQL Injection 위험 최고 등급. `generatePassword()`의 `rand()` 사용은 임시 비밀번호 예측 가능 취약점.

| 파일 | 함수 | 변경 내용 |
|------|------|-----------|
| `framework/boot/30-Auth.php` | `Auth::authenticate()` | `WHERE u.loginid = '$loginid'` → `?` placeholder + `bind_param('s', $loginid)` |
| `library/auth.php` | `isLoginId()` | `WHERE u.loginid = '$loginid'` → prepared (`is` 타입 2 params). `POD::escapeString()` 제거 |
| `library/auth.php` | `generatePassword()` | `rand(0x10000000, 0x70000000)` → `random_int(0x10000000, 0x70000000)` (CSPRNG, PHP 7.0+). (php.net/function.random-int) |
| `framework/legacy/Textcube.Core.php` | `__generatePassword()` | `rand(...)` → `random_int(...)` (CSPRNG) |
| `framework/legacy/Textcube.Control.Session.php` | `newAnonymousSession()` L133 | session ID `rand(...)` 4번 → `random_int(...)` 4번 (CSPRNG). DB 세션 ID도 STAGE 1의 memcached 세션과 동일하게 처리 |
| `framework/legacy/Textcube.Control.Session.php` | `authorize()` L225 | session ID `rand(...)` 4번 → `random_int(...)` 4번 (CSPRNG) |
| `framework/legacy/Textcube.Data.Attachment.php` | 파일 이름 생성 (L152, L163) | `rand(1000000000, 9999999999)` → `random_int(...)` |
| `library/model/blog.attachment.php` | 파일 이름 생성 (L147, L223) | `rand(...)` → `random_int(...)` |
| `library/model/blog.api.php` | 파일 이름 생성 (L356, L359) | `rand(...)` → `random_int(...)` |
| `plugins/ST_TeamBlogSettings/index.php` | 파일 이름 생성 (L329) | `rand(...)` → `random_int(...)` |

---

## v1.87 — 게시물 / 댓글 저장 Prepared Statement 변환, 트랙백 수신 마커 부착

- **변경 이유**: 사용자 입력이 들어가는 콘텐츠 저장 쿼리 — SQL Injection 직접 노출 지점.

| 파일 | 함수 | 변경 내용 |
|------|------|-----------|
| `library/model/blog.entry.php` | `addEntry()`, `updateEntry()` | INSERT/UPDATE raw SQL → prepared |
| `library/model/blog.comment.php` | `addComment()`, `updateComment()` | INSERT/UPDATE raw SQL → prepared |
| `library/model/blog.response.remote.php` | 트랙백 수신 처리 | DBModel 구조상 prepared 불가. `@security raw-sql-escape` 마커 부착. 외부 입력은 `DBModel::setAttribute($val, true)` → `POD::escapeString()` 처리됨 |

*참고: 트랙백 관련 모델은 `blog.trackback.php`가 아닌 `blog.response.remote.php`에 존재함 (원본 확인 완료).*

---

## v1.88 — BlogAPI / 검색 쿼리 Prepared Statement 변환

- **변경 이유**: XMLRPC 외부 입력 진입점 및 검색어 LIKE 패턴.

| 파일 | 함수 | 변경 내용 |
|------|------|-----------|
| `library/model/blog.api.php` | XMLRPC 핸들러 쿼리 | raw SQL → prepared |
| `library/model/blog.entry.php` | `searchEntries()` | `LIKE '%$keyword%'` → `LIKE CONCAT('%', ?, '%')` + prepared |

---

## v1.89 — setup.php Prepared Statement 변환 검토 — legacy escaping 유지 결정

- **변경 이유**: 설치 시점 관리자 계정 생성 쿼리에 외부 입력(`$loginid`, `$name`, `$blog`) 사용. Prepared Statement 전환 검토.
- **결정**: `setup.php` L1136-1188의 INSERT는 `implode()`로 조립된 배치 SQL 구조. prepared statement 전환 시 배치 실행 방식 전면 재작성 필요. 해당 파라미터는 모두 `POD::escapeString()` 처리됨을 확인. 리스크 수용 후 legacy escaping 유지 결정.
- SECURITY.md `[HIGH] Raw SQL` 항목에 "setup.php 배치 구조상 legacy escaping 유지" 기록.

---

## v1.90 — SECURITY.md `[HIGH] Raw SQL` 항목 현황 갱신 (2026-05-15)

- STAGE 2(v1.85~v1.89) Prepared Statement 변환 완료 함수 목록을 SECURITY.md에 명세.
- 미변환 외부 입력 진입점에 `@security raw-sql-escape` PHPDoc 마커 부착 완료.
- "핵심 진입점 prepared 변환 완료, 잔여 내부 쿼리는 STAGE 3(v2.10) 이연" SECURITY.md에 명시.

**변환 완료 함수 (10건):**
- `framework/data/MySQLi/Adapter.php`: `prepare()`, `bindAndExecute()`, `fetchAllStmt()` 신설
- `framework/data/IAdapter.php`: 위 3개 메서드 인터페이스 선언 추가
- `framework/boot/30-Auth.php`: `Auth::authenticate()` loginid WHERE 절
- `library/auth.php`: `isLoginId()` loginid + blogid WHERE 절
- `library/model/blog.comment.php`: `addComment()`, `updateComment()`
- `library/model/blog.entry.php`: `addEntry()`, `updateEntry()`
- `library/model/blog.api.php`: `api_addAttachment()`, `api_update_attaches()`

**`@security raw-sql-escape` 마커 부착 (미변환, STAGE 3 예정):**
- `library/model/blog.entry.php`: `getEntryListWithPagingBySearch()`, `getEntriesWithPagingBySearch()`
- `library/model/blog.api.php`: `api_update_attaches_with_replace()`
- `library/model/blog.response.remote.php`: trackback 수신 처리 (DBModel 구조상 prepared 불가)

---

## v1.91 — `utf8_encode()` / `utf8_decode()` deprecated 점검 (PHP 8.2)

- **제거 이유**: PHP 8.2에서 `utf8_encode()`, `utf8_decode()` deprecated. PHP 9.0에서 제거 예정. (php.net/migration82.deprecated)
- **PHP 권고**: `mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1')` / `mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8')` 사용.
- **점검 결과**: 본체 코드에서 호출 없음 확인. 컨테이너 부팅 후 deprecation 로그 추가 검출 시 동일 처리.

---

## v1.92 — 문자열-정수 비교 엄격화 대응 (PHP 8.0)

- **변경 이유**: PHP 8.0에서 문자열-정수 비교(`"abc" == 0`) 동작 변경. PHP 7.x에서는 문자열을 정수로 강제 변환하여 `0 == 0 → true`였으나, PHP 8.0부터 정수를 문자열로 변환하여 `"abc" == "0" → false`. (php.net/migration80.incompatible)
- **적용**: 회귀 테스트 오류 로그에서 실제 동작 변경 케이스 핀포인트 확인 후 수정. 광범위 변경은 STAGE 3 이연.

---

## v1.93 — 정적 분석 1차 (PHPStan level 1)

- PHPStan level 1 분석 실행. 결과는 `STATIC_ANALYSIS.md` 또는 SECURITY.md `[INFO]` 섹션으로 기록.
- 수정은 STAGE 3 이연. fatal 위험 항목만 예외 적용.

---

## v1.94 ~ v1.99 — 회귀 테스트 및 잔여 결함 수정

- tc-php82 컨테이너에서 회귀 테스트 실행.
- PASS 기준: single 66/66, path 91/91, domain 91/91, memcached 44/44 (합계 292/292).
- PHP 오류 로그 0건, Apache 오류 로그 0건 달성까지 잔여 결함 수정 반복.

---

## v1.96 — `MySQL/Adapter.php` Prepared Statement API 구현 — mysqli 래퍼 완성 (2026-05-17)

- **변경 이유**: `MySQL/Adapter.php` 는 PHP 7.0의 `ext/mysql` 제거 이후 내부적으로 `new mysqli()` 를 사용하는 MySQLi 래퍼로 재작성되었다. v1.85 (STAGE 2)에서 Prepared Statement API (`prepare`, `bindAndExecute`, `fetchAllStmt`) 를 IAdapter 인터페이스에 추가했을 때, MySQL 어댑터 구현은 `RuntimeException("Prepared statements not supported")` 를 던지는 스텁으로 잘못 작성되었다. 이로 인해 MySQL 어댑터(기본 어댑터) 사용 시 모든 로그인이 500 Internal Server Error 로 실패.
- **PHP 권고**: MySQL 어댑터는 이미 MySQLi 연결 객체(`self::$db`)를 보유하므로 prepared statement 기능을 그대로 위임할 수 있다.
- **조치**: 세 메서드를 `self::$db` 에 위임하는 실제 구현으로 교체.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/data/MySQL/Adapter.php` | `prepare()` → `self::$db->prepare($query)` / `bindAndExecute()` → `bind_param + execute` / `fetchAllStmt()` → `get_result + fetch_assoc` |
| `framework/alias/DBAdapter.php` | 폴백 기본값 `'MySQL'` → `'MySQLi'` |
| `setup.php` | dbms 감지 순서 변경 — MySQLi 를 MySQL 보다 먼저 추가하여 신규 설치 시 기본 선택값이 MySQLi 가 되도록 수정 |

---

## v1.95 — `control/server/config`, `control/server/rewrite` 권한 가드 추가 (보안 수정, 2026-05-17)

- **변경 이유**: `interface/control/server/config/index.php` 및 `interface/control/server/rewrite/index.php` 에 `requireStrictRoute()` 후 권한 검사가 없어, `group.owners` 권한 사용자(블로그 소유자)가 시스템 전역 설정 변경 및 `.htaccess` 덮어쓰기가 가능한 취약점. 실제 악용 가능성 확인됨.
- **PHP 보안 권고**: 관리 기능 엔드포인트는 반드시 최소 권한 원칙(PoLP)에 따라 `requirePrivilege()` 가드를 배치할 것.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/control/server/config/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |
| `interface/control/server/rewrite/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |

> **확인된 취약점**: 시나리오 테스트에서 `group.owners` 계정으로 서버 설정 변경(`$service['timeout']` 전역 변경, `error=0` 확인) 및 `.htaccess` 임의 덮어쓰기로 URL 리라이팅 전체 중단 재현됨. SECURITY.md `[FIXED]` 항목으로 갱신.

---

## v1.97 — `control/action/user/add`, `delete`, `suggest` 권한 가드 추가 (보안 수정, 2026-05-17)

- **변경 이유**: `interface/control/action/user/add/index.php`, `delete/index.php`, `suggest/index.php` 에 `requireStrictRoute()` 후 `requirePrivilege('group.creators')` 가드가 없어, `group.owners` 권한 사용자(블로그 소유자, userid≠1)가 직접 API 를 호출하여 사용자 추가·삭제 및 전체 사용자 loginid/이름 열람이 가능한 취약점. 실제 악용 확인됨.
- **PHP 보안 권고**: 관리 기능 엔드포인트는 반드시 최소 권한 원칙(PoLP)에 따라 `requirePrivilege()` 가드를 배치할 것.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/control/action/user/add/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |
| `interface/control/action/user/delete/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |
| `interface/control/action/user/suggest/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |

> **확인된 취약점**: `group.owners` 계정으로 `user/add` — 시스템 사용자 추가, `user/delete` — 기존 사용자 삭제, `user/suggest` — loginid·이름 전수 열거가 가능함을 확인함.

---

## v2.00 — STAGE 2 종료

- 회귀 테스트 결과 SECURITY.md에 기록.
- SECURITY.md 모든 항목 현황 최종 재평가.
- README.md PHP 8.2 명시 최종 확인.
- `release-php8.2.zip` 산출.

---

## v2.01 — STAGE 2 진행 상태 재점검 (2026-05-17)

- v1.75 ~ v2.00 전체 항목 코드 대조 점검 완료.
- **v1.86 재점검**: 점검 초기에 `framework/boot/30-Auth.php` 행을 PRNG 환각으로 오판하였으나, 해당 행은 Prepared Statement 변환(`WHERE loginid = ? placeholder`)에 관한 기록임을 확인. `30-Auth.php:401` `POD::prepare()` 실 적용 확인. 표 수정 없음.
- 나머지 v1.75~v1.97 항목 코드 일치 확인. 누락 없음.

---

## v2.02 — PRNG 잔존 분류 + `phpopenid/CryptUtil.php` 보안 강화 (2026-05-17)

- **변경 이유**: STAGE 2 전체 `rand()` / `mt_rand()` 잔존 호출을 보안/비보안 컨텍스트로 분류. 보안 컨텍스트 1건 발견하여 `random_bytes()`로 교체. (php.net/function.random-bytes)

**보안 컨텍스트 — 교체 적용**

| 파일 | 라인 | 변경 전 | 변경 후 |
|------|------|---------|---------|
| `library/contrib/phpopenid/Auth/OpenID/CryptUtil.php` | 60 (폴백 블록) | `for ($i=0; $i<$num_bytes; $i+=4) { $bytes .= pack('L', mt_rand()); } $bytes = substr($bytes, 0, $num_bytes);` | `$bytes = random_bytes($num_bytes);` |

- **변경 이유**: `getBytes()` 함수는 OpenID 프로토콜의 암호화 난수 생성에 사용된다. 기존 폴백 경로(`Auth_OpenID_RAND_SOURCE === null` 또는 파일 열기 실패 시)에서 `mt_rand()`(예측 가능 PRNG)를 사용하고 있었다. PHP 7.0+에서는 `random_bytes()`가 항상 CSPRNG를 보장하므로 교체. (php.net/migration80 — mt_rand 보안 부적합 주의)

**비보안 컨텍스트 — 유지 결정 (SECURITY.md [INFO] 분류 기록)**

| 파일 | 함수/용도 | 판정 |
|------|-----------|------|
| `library/contrib/phpopenid/Auth/OpenID/DiffieHellman.php:73` | `$this->lib->rand($this->mod)` — BigMath 클래스 메서드 호출 (PHP 내장 `rand()` 아님, 내부에서 `CryptUtil::getBytes()` 사용) | 안전 |
| `library/contrib/phpopenid/Auth/OpenID/BigMath.php:142` | `function rand($stop)` — 메서드 정의. 내부에서 `CryptUtil::getBytes()` 호출 | 안전 |
| `library/contrib/phpmailer/src/PHPMailer.php:2843` | `mt_rand()` — MIME boundary 생성 최후 폴백 ("We failed to produce a proper random string") | 비보안, 유지 |
| `plugins/StatGraph/count/src/jpgraph.php` | `rand()` — 그래프 렌더링 내부 위치·색상 계산 | 비보안, 유지 |
| `plugins/FM_Textile/classTextile.php`, `ttml.php` | `rand()` — HTML 요소 고유 ID 생성 | 비보안, 유지 |
| `plugins/FM_TTML/ttml.php`, `plugins/FM_Markdown/ttml.php` | `rand()` — 동상 | 비보안, 유지 |
| `plugins/GoogleMap/index.php` | `rand()` — 지도 컨테이너 div ID 생성 | 비보안, 유지 |

---

## v2.03 — phpmigtest COPY 기반 격리 테스트 (2026-05-17)

- `Containerfile.phpmigtest.php82` 신설: STAGE 2 코드를 COPY 기반으로 격리하여 tc_full_test.sh 실행 — 호스트 소스 디렉토리 보호.
- `textcube-migtest-php82` 이미지 빌드 완료.
- tc_full_test.sh 33/33 PASS, PHP 오류 로그 0건.

---

## v2.04 — `MySQLi/Adapter.php`, `MySQLi/Debug.php`, `MySQL/Debug.php` mysqli 절차형 → 객체지향 전환 (STAGE 3 v2.02 역이식, 2026-05-17)

- **변경 이유**: STAGE 3 v2.02에서 PHP 8.5 deprecated 대비로 적용한 mysqli OOP 전환을 STAGE 2에도 동일하게 적용. PHP 8.2 시점에서는 deprecated 아니나 일관성 및 미래 호환성 확보를 위해 역이식.
- **PHP 권고**: mysqli 연결/결과 객체의 프로퍼티·메서드를 직접 사용할 것.

| 절차형 (구) | 객체지향 (신) | 적용 파일 |
|-------------|---------------|----------|
| `mysqli_character_set_name(POD::$db)` | `POD::$db->character_set_name()` | Debug.php 2개 |
| `mysqli_error(POD::$db)` | `POD::$db->error` | Debug.php 2개 |
| `mysqli_errno(POD::$db)` | `POD::$db->errno` | Debug.php 2개 |
| `mysqli_num_rows($result)` | `$result->num_rows` | Debug.php 2개, Adapter.php |
| `mysqli_affected_rows(POD::$db)` | `POD::$db->affected_rows` | Debug.php 2개 |
| `mysqli_free_result($handle)` | `$handle->free()` | Adapter.php |
| `mysqli_fetch_array($handle)` | `$handle->fetch_array()` | Adapter.php |
| `mysqli_fetch_row($handle)` | `$handle->fetch_row()` | Adapter.php |
| `mysqli_fetch_assoc($handle)` | `$handle->fetch_assoc()` | Adapter.php |
| `mysqli_error($err)` | `$err->error` | Adapter.php |

| 파일 | 변경 건수 |
|------|-----------|
| `framework/data/MySQLi/Debug.php` | 7건 |
| `framework/data/MySQL/Debug.php` | 7건 |
| `framework/data/MySQLi/Adapter.php` | 6건 |

**검증**: PHP 문법 검사(`php -l`) 3개 파일 모두 통과. 로직은 STAGE 3 v2.02 적용본과 동일.

---

## v2.05 — phpmigtest 재빌드 검증 (mysqli OOP 백포트 v2.04 반영, 2026-05-18)

- v2.04 mysqli 절차형 → 객체지향 전환 적용 후 `textcube-migtest-php82` 이미지 재빌드.
- tc_full_test.sh 33/33 PASS, PHP 오류 로그 0건.

### 테스트 결과 (2026-05-18, phpmigtest-php82, v2.04 적용 후)

| 모드 | 결과 |
|------|------|
| tc_full_test.sh 33/33 | **PASS** |
| PHP 오류 로그 | **0건** |

---

## v2.06 — `addBlog()` 기본 에디터 `'modern'` → `'tinyMCE'` 수정 (2026-05-19)

- **변경 이유**: STAGE 1 v1.80과 동일 (상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조).

| 파일 | 변경 내용 |
|------|-----------|
| `library/model/blog.blogSetting.php` | `addBlog()`: `defaultEditor` 기본값 `'modern'` → `'tinyMCE'` |

---

## v2.07 — `requireStrictRoute()` 비표준 포트 환경 Referer 비교 버그 수정 (2026-05-19)

- **변경 이유**: STAGE 1 v1.81과 동일 (상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조).

| 파일 | 변경 내용 |
|------|-----------|
| `library/auth.php` | `requireStrictRoute()`: `$url['host'] == $_SERVER['HTTP_HOST']` → `$refererHost` (host:port 재조합) 비교 |

---

## v2.08 — `Validator::number()` 비숫자 + bypass 처리 수정 (2026-05-19)

- **변경 이유**: STAGE 1 v1.82와 동일. PHP 8.0 브레이킹 체인지로 인해 `"null"` 문자열과 float 비교가 문자열 비교로 변경 → latitude/longitude IV 유효성 검사 실패 → "저장하지 못했습니다". 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/boot/10-CoreClasses.php` | `Validator::number()`: 비숫자 + `bypass=true` 시 범위 체크 생략 후 true 반환 |

---

## v2.09 — `add/index.php` 임시 첨부파일 parent 업데이트 패치 소급 문서화 (2026-05-19)

- STAGE 1 v1.83과 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/owner/entry/add/index.php` | `addEntry()` 성공 후 `DBModel`로 `Attachments.parent=0` → 신규 entryId 업데이트 |

---

## v2.10 — `requireStrictRoute()` 포트 비교 로직 정정 (2026-05-19)

- **v2.07 수정 오류 정정**: STAGE 1 v1.84와 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `library/auth.php` | `requireStrictRoute()`: Referer host+port 재조합 → 호스트명만 추출 후 `$_SERVER['HTTP_HOST']`와 비교 |

## v2.11 — `Tag` 클래스 메서드 `static` 선언 추가 (PHP 8.0 Fatal Error 대응, 2026-05-19)

- STAGE 1 v1.85와 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/legacy/Textcube.Data.Tag.php` | `doesExist`, `addTagsWithEntryId`, `modifyTagsWithEntryId`, `deleteTagsWithEntryId`, `getTagsWithEntryId`, `_getMaxId` — `static` 선언 추가 |

## v2.12 — legacy Data 클래스 `@static@` 메서드 `static` 선언 일괄 추가 (2026-05-19)

- STAGE 1 v1.86과 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 추가된 `static` 메서드 |
|------|----------------------|
| `Textcube.Data.php` | `removeAll` |
| `Textcube.Data.DataMaintenance.php` | `removeAll` |
| `Eolin.API.Syndication.php` | `join`, `leave` |
| `Textcube.Data.Feed.php` | `getId`, `getName` |
| `Textcube.Data.LinkCategories.php` | `getId`, `getName` |
| `Textcube.Data.SubscriptionStatistics.php` | `compile` |
| `Textcube.Data.RefererStatistics.php` | `compile` |
| `Textcube.Data.DailyStatistics.php` | `compile`, `validateDate` |
| `Textcube.Data.BlogStatistics.php` | `compile` |
| `Textcube.Data.Notice.php` | `doesExist` |
| `Textcube.Data.Keyword.php` | `doesExist` |
| `Textcube.Data.CommentNotifiedSiteInfo.php` | `getEntry` |
| `Textcube.Data.CommentNotified.php` | `getEntry` |
| `Textcube.Data.Comment.php` | `getEntry` |
| `Textcube.Data.Post.php` | `correctTagsAll` |
| `Textcube.Data.BlogSetting.php` | `setTimezone`, `validateName` |
| `Textcube.Data.Attachment.php` | `doesExist`, `getParent`, `adjustPermission`, `confirmFolder` |

## v2.13 — `getBlogURL()` domain 모드 서브도메인 점(.) 누락 표기 오류 수정 (2026-05-19)

- STAGE 1 v1.87과 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.
- 수정 파일: `library/model/blog.service.php:64`

## v2.14 — `00-UnifiedEnvironment.php` magic_quotes 제거 → `normalizeSuperglobalInput()` 동등 변환 소급 적용 (2026-05-31)

- STAGE 1 v1.3에서 `get_magic_quotes_gpc()` 블록을 단순 제거했으나, 원본의 전체 슈퍼글로벌 순회 구조를 동등 변환해야 함. null byte 제거(`str_replace(chr(0), '', $value)`)로 대체하여 입력값 정규화 책임 유지.
- 수정 파일: `framework/boot/00-UnifiedEnvironment.php`

## v2.15 — `control/action/user/suggest` SQL Injection 해소 (raw 쿼리 → DBModel 빌더 전환, 보안 수정, 2026-05-31)

- STAGE 4 v3.45와 동일 (5스테이지 공통 적용). 상세·악성 입력 검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.45 및 SECURITY.md 참조.
- 수정 파일: `interface/control/action/user/suggest/index.php`

## v2.16 — `control/action/user/suggest` 반사형 + 저장형 XSS 해소 (출력 인코딩, 보안 수정, 2026-05-31)

- STAGE 4 v3.46과 동일 (5스테이지 공통 적용). 상세·검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.46 및 SECURITY.md 참조.
- 수정 파일: `interface/control/action/user/suggest/index.php`

## v2.17 — `owner/communication/comment`·`notify` / `owner/entry` 반사형 XSS 해소 (HTML 속성 출력 인코딩, 보안 수정, 2026-05-31)

- STAGE 4 v3.47과 동일 (5스테이지 공통 적용). 상세·검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.47 및 SECURITY.md 참조.
- 수정 파일: `interface/owner/communication/comment/index.php`, `interface/owner/communication/notify/index.php`, `interface/owner/entry/index.php`

## v2.18 — `owner/help` 경로순회/LFI 해소 (`$_GET['lang']` 화이트리스트 정규화, 보안 수정, 2026-05-31)

- STAGE 4 v3.48과 동일 (5스테이지 공통 적용). 상세·검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.48 및 SECURITY.md 참조.
- 수정 파일: `interface/owner/help/index.php`

## v2.19 — owner 상태변경 액션 CSRF 가드 보강 + `requireStrictRoute` path-모드 강화 (보안 수정, 2026-05-31)

- STAGE 4 v3.49와 동일 (5스테이지 공통 적용). 상세·회귀 검증(18 케이스) 내용은 STAGE 4 `changelog8.4to8.5.md` v3.49 및 SECURITY.md 참조.
- 수정 파일: `library/auth.php` + owner 상태변경 액션 13개

## v2.20 — Clipboard API HTTP fallback 개선 (`resources/script/common3.js`, 2026-05-31)

- STAGE 4 v3.50과 동일 (5스테이지 공통 적용). 상세 내용은 STAGE 4 `changelog8.4to8.5.md` v3.50 및 SECURITY.md #7 참조.
- 수정 파일: `resources/script/common3.js`

## v2.21 — 쿠키 보안 속성(HttpOnly/SameSite/Secure) 보강 (보안 수정, 2026-05-31)

- STAGE 4 v3.51과 동일 (5스테이지 공통 적용). 상세·검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.51 및 SECURITY.md #5 참조.
- 수정 파일: `library/preprocessor.php`, `library/auth.php`, `framework/legacy/Textcube.Control.Session.php`·`Session.Memcached.php`·`Openid.php`, `interface/blog/comment/{comment,add}/index.php`

## v2.22 — StatGraph jpgraph(QPL) → SVG 그래프 대체 (라이선스, 2026-05-31)

- STAGE 4 v3.52와 동일 (5스테이지 공통 적용). 상세·검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.52 및 SECURITY.md #3 참조.
- 수정/제거: `plugins/StatGraph/index.php`(SVG 재구현), `plugins/StatGraph/count/`(jpgraph QPL 제거)

## v2.23 — OpenID 2.0 (EOL) 제거 → OIDC 재구현 (이중 옵트인, 2026-05-31)

- STAGE 4 v3.53과 동일 (5스테이지 공통 적용). 상세·검증·테스트 내용은 STAGE 4 `changelog8.4to8.5.md` v3.53 및 SECURITY.md #4 참조.
- OIDC 재구현(이중 옵트인, 의존 없는 자체 구현 `Textcube.Control.OIDC.php`) + OpenID 2.0/phpopenid(53파일) 완전 제거. `Openid.php` 헬퍼 축소(static화), `login/openid`(+`callback`)·`account/openid`·`setting/openid`·CL_OpenID 일원화.

---

---

<sub>Modifications documented herein by @deokio (2026), performed with AI assistance (Anthropic Claude) under human review.
No additional copyright is asserted. Licensed under GPL (same as the rest of the project).</sub>
