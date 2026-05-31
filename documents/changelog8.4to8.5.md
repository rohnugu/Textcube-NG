# CHANGELOG — Textcube 1.10.10 PHP 8.4 → PHP 8.5 이식 (STAGE 4)

대상 PHP: 8.5  
기반 버전: php8.4-Textcube-1.10.10 (STAGE 3, v2.02)  
작성일: 2026-05-16 / 최종 수정: 2026-05-31

이전 단계 누적분(v1.75 ~ v2.02): `../php8.4-Textcube-1.10.10/changelog8.2to8.4.md` 참조

---

## v3.01 — STAGE 4 메타 파일 갱신 (2026-05-16)

- `changelog8.4to8.5.md` 신설: STAGE 4 헤더, 기반 버전 v2.02, 이전 누적분 참조 명시.
- `SECURITY.md`: 헤더 STAGE 4로 갱신.
- `README.md`: REQUIREMENTS PHP 8.4 → PHP 8.5 갱신.
- 환경 구성:
  - 컨테이너: `php:8.5-apache` 공식 이미지 (`podman run`) + 패키지 직접 설치.
  - dom/lexbor: PHP 8.5-apache 이미지에 기본 내장됨 (별도 설치 불필요).
  - pecl/memcache: [websupport-sk/pecl-memcache](https://github.com/websupport-sk/pecl-memcache) 포크가 PHP 8.5 지원 제공 (PR #118). 소스 빌드 후 설치 성공 → memcache 활성화 상태로 테스트 진행.
  - DB: `textcube85` (MySQL 5.7, tc-mysql 공유).
  - 포트: 8085.
- 초기 검증 결과: **33/33 PASS, PHP 오류 0건** (PHP 8.4 이식 코드가 PHP 8.5에서 그대로 통과).

---

## v3.02 — PHP 8.5 소스 전수 검사 결과 (2026-05-16)

`error_reporting = E_ALL | E_DEPRECATED` 환경에서 tc_full_test.sh 실행 후 PHP 오류 로그 검사.

| 검사 항목 | 결과 |
|-----------|------|
| `mysqli_*` 절차형 함수 잔여 | `mysqli_report(MYSQLI_REPORT_OFF)` 1건 (Adapter.php:17) — PHP 8.5에서 deprecated 경고 없음 |
| implicit nullable parameter | 없음 (STAGE 2에서 이미 수정) |
| `get_class()` 무인자 호출 | 없음 |
| 기타 PHP 8.5 deprecated | 없음 |

**결론**: STAGE 3 (PHP 8.4) 이식 코드가 PHP 8.5에서 추가 수정 없이 완전 호환. 코드 변경 없이 이식 완료.

### 테스트 결과 (2026-05-16, tc-php85, memcache 비활성화 초기 검사)

| 모드 | 결과 |
|------|------|
| tc_full_test.sh 33/33 | **PASS** |
| PHP 오류 로그 | **0건** |
| Apache 오류 로그 | **0건** |

---

## v3.03 — pecl/memcache (websupport-sk 포크) PHP 8.5 설치 및 검증 (2026-05-16)

- **배경**: 공식 pecl/memcache 8.2는 PHP 8.5에서 `ext/standard/php_smart_string_public.h` 헤더 제거로 빌드 불가. [websupport-sk/pecl-memcache](https://github.com/websupport-sk/pecl-memcache) 포크가 PHP 8.5 지원 제공 (PR #118 — `Zend/zend_smart_string.h`로 교체).
- **설치**: `git clone https://github.com/websupport-sk/pecl-memcache.git && phpize && ./configure --enable-memcache && make && make install`
- **결과**: tc-php85 (PHP 8.5.6)에서 빌드 및 설치 성공. `php -m | grep memcache` → 확인됨.
- **검증 시 임시 활성화**: `$service['memcached'] = true;` / `$memcached['server'] = 'tc-memcached';` / `$memcached['port'] = 11211;` — 컨테이너 내부에서만 임시 적용. 저장소 `config.php` 기본값은 비활성(주석 처리) 유지.
- **검증**: memcache 활성화 상태에서 tc_full_test.sh 33/33 PASS, PHP 오류 로그 0건.

### 테스트 결과 (2026-05-16, tc-php85, memcache 활성화)

| 모드 | 결과 |
|------|------|
| tc_full_test.sh 33/33 | **PASS** |
| PHP 오류 로그 | **0건** |
| Apache 오류 로그 | **0건** |
| memcache set/get | **정상** (Memcache 1.6.41) |

---

## v3.04 — `control/server/config`, `control/server/rewrite` 권한 가드 추가 + 인자 따옴표 버그 수정 (보안 수정, 2026-05-17)

- **변경 이유**: `interface/control/server/config/index.php` 및 `interface/control/server/rewrite/index.php` 에 `requireStrictRoute()` 후 권한 검사가 없어, `group.owners` 권한 사용자(블로그 소유자)가 시스템 전역 설정 변경 및 `.htaccess` 덮어쓰기가 가능한 취약점. 실제 악용 가능성 확인됨.
- **추가 버그 수정**: 이전 수정에서 shell sed 처리 중 `requirePrivilege(group.creators)` 로 따옴표가 누락된 채 커밋됨. PHP 에서 `group.creators` 는 정의되지 않은 상수(PHP 8에서는 E_WARNING)이므로 가드가 실질적으로 무효였음. 올바른 문자열 리터럴 `requirePrivilege('group.creators')` 로 수정.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/control/server/config/index.php` | `requirePrivilege(group.creators)` → `requirePrivilege('group.creators')` (따옴표 누락 수정) |
| `interface/control/server/rewrite/index.php` | `requirePrivilege(group.creators)` → `requirePrivilege('group.creators')` (따옴표 누락 수정) |

> **확인된 취약점**: 시나리오 테스트에서 `group.owners` 계정으로 서버 설정 변경(`$service['timeout']` 전역 변경, `error=0` 확인) 및 `.htaccess` 임의 덮어쓰기로 URL 리라이팅 전체 중단 재현됨.

---

## v3.05 — `MySQL/Adapter.php` Prepared Statement API 구현 + DBAdapter 기본 어댑터 `MySQLi` 변경 (2026-05-17)

- **변경 이유**: php8.4 v2.05 와 동일 (상세 내용 `../php8.4-Textcube-1.10.10/changelog8.2to8.4.md` 참조).

| 파일 | 변경 내용 |
|------|-----------|
| `framework/data/MySQL/Adapter.php` | `prepare()` / `bindAndExecute()` / `fetchAllStmt()` — RuntimeException 스텁 → mysqli 위임 구현 |
| `framework/alias/DBAdapter.php` | 폴백 기본값 `'MySQL'` → `'MySQLi'` |
| `setup.php` | dbms 감지 순서 변경 — MySQLi 를 MySQL 보다 먼저 추가하여 신규 설치 시 기본 선택값이 MySQLi 가 되도록 수정 |

---

## v3.06 — `control/action/user/add`, `delete`, `suggest` 권한 가드 추가 (보안 수정, 2026-05-17)

- **변경 이유**: php8.4 v2.06 과 동일 (상세 내용 `../php8.4-Textcube-1.10.10/changelog8.2to8.4.md` 참조).

| 파일 | 변경 내용 |
|------|-----------|
| `interface/control/action/user/add/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |
| `interface/control/action/user/delete/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |
| `interface/control/action/user/suggest/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |

---

## v3.07 — `phpopenid/CryptUtil.php` `mt_rand()` 폴백 → `random_bytes()` 교체 (2026-05-17)

- **변경 이유**: STAGE 2 v2.02 / STAGE 3 v2.07 과 동일. `getBytes()` 폴백 경로의 `mt_rand()` 루프 → `random_bytes($num_bytes)` (CSPRNG, PHP 7.0+) 교체.

| 파일 | 라인 | 변경 내용 |
|------|------|-----------|
| `library/contrib/phpopenid/Auth/OpenID/CryptUtil.php` | 60 | `mt_rand()` 폴백 블록 → `random_bytes($num_bytes)` |

---

## v3.30 — STAGE 4 종료 (2026-05-16)

- 회귀 테스트 결과 SECURITY.md 기록.
- README.md PHP 8.5 명시 최종 확인.
- `release-php8.5.zip` 산출.

---

## v3.31 — STAGE 4 진행 상태 재점검 (2026-05-17)

- v3.01 ~ v3.06 + v3.30 전체 항목 코드 대조 점검 완료.
- v3.03 본문 정정: "config.php 변경" → "검증 시 컨테이너 내부 임시 활성화" (저장소 기본값 비활성 유지).
- STAGE 3·4 핵심 보안 수정 및 어댑터 코드 양쪽 일관 적용 확인.
- 나머지 항목 코드 상태 일치. 누락 없음.

---

## v3.32 — phpmigtest COPY 기반 격리 테스트 (2026-05-17)

- `Containerfile.phpmigtest.php85` 신설: STAGE 4 코드를 COPY 기반으로 격리하여 tc_full_test.sh 실행 — 호스트 소스 디렉토리 보호.
- `textcube-migtest-php85` 이미지 빌드 완료 (websupport-sk/pecl-memcache 소스 빌드 포함).
- tc_full_test.sh 33/33 PASS, PHP 오류 로그 0건.
- `tc_migtest.sh` 신설: PHP 5.6 원본 ~ 8.5 STAGE 4 전체 순차 빌드·테스트 오케스트레이션.

### phpmigtest 전체 결과 (2026-05-17)

| PHP 버전 | Stage | PASS | FAIL |
|----------|-------|------|------|
| PHP 5.6 | 원본 (Textcube-1.10.10) | 33 | 0 |
| PHP 7.4 | STAGE 1 | 33 | 0 |
| PHP 8.2 | STAGE 2 | 33 | 0 |
| PHP 8.4 | STAGE 3 | 33 | 0 |
| PHP 8.5 | STAGE 4 | 33 | 0 |

---

## v3.33 — tc-php85 도메인 모드 재설치 (사용성 테스트 준비, 2026-05-17)

- **배경**: tc-php85 컨테이너의 기존 설치 상태(`testblog.example.org`, tc_full_test.sh 테스트 데이터)를 정리하고 TESTSETTING 패턴으로 재설치.
- **DB**: textcube85 전체 테이블 초기화 후 setup.php를 통해 재설치.
- **설정**: `$service['type'] = 'domain'`, `$service['domain'] = 'example.com'`, `$database['dbms'] = 'MySQLi'`.
- **초기 계정**: admin@test.local / admin1234, 블로그명 TestBlog (defaultDomain=0, example.com 에서 접근).
- 사용성 테스트 전 도메인 모드 동작 확인: `/` HTTP 200, `/login` HTTP 200, `/owner` HTTP 302 확인됨.

---

## v3.34 — `config.php` `$service['domain']` 수정 + `tc_setup_blogs.sh` 신설 (2026-05-18)

- **문제**: 초기 설치 시 `$service['domain'] = 'sub.example.com'`으로 설정하면 domain 1차(와일드카드) 모드에서 블로그 URL이 `abc.sub.example.com`으로 조합되어 `abc.example.com` 요청과 불일치 → 404.
- **원인**: `URIHandler`가 `Host: abc.example.com`을 `explode('.', host, 2)` = `['abc', 'example.com']`으로 분리 후 `domain[1]` == `$service['domain']` 일치 여부로 1차 라우팅을 판정함. `sub.example.com` ≠ `example.com`이므로 secondaryDomain 조회로 폴백 → 미등록 → 404.
- **수정**: `$service['domain'] = 'example.com'`으로 변경. `abc.example.com` → blog name `abc` 정상 조회 확인 (HTTP 200).

| 파일 | 변경 내용 |
|------|-----------|
| `config.php` | `$service['domain']` = `sub.example.com` → `example.com` |

- **`tc_setup_blogs.sh` 신설**: setup.php 초기 설치 이후 domain/path 모드 테스트 블로그(3개)·사용자를 자동 생성하는 호스트 측 실행 스크립트.
  - 로그인 → 사용자 생성(`User::add()` + DB에서 비밀번호 `MD5('admin1234')` 직접 갱신) → 블로그 생성 순서.
  - 멱등(idempotent): 사용자 중복(`result=9`) 및 블로그 이름 중복(`result=61`) 모두 OK 처리.
  - domain 1차 모드 기준: `identify=abc` → `abc.example.com` (service.domain=`example.com` 조합).
  - 환경변수: `MODE`, `HOST`, `PORT`, `DB_NAME` 등으로 각 STAGE별 파라미터 지정.

### 검증 결과 (2026-05-18, tc-php85)

| 도메인 | HTTP |
|--------|------|
| `example.com` (기본 블로그 TestBlog) | 200 |
| `abc.example.com` (admin@test.local) | 200 |
| `bcd.example.com` (admin2@test.local) | 200 |
| `cde.example.com` (admin3@test.local) | 200 |

---

## v3.35 — `config.php` `$service['port']` 명시 + pageCache 초기화 (2026-05-18)

- **문제**: domain 모드 블로그의 CSS/JS/스킨 리소스 URL에 포트(`8085`)가 누락됨 (`http://abc.example.com/skin/...` → `:8085` 없음). PHP는 `HTTP_HOST: abc.example.com:8085` 파싱으로 `SERVER_PORT=8085`를 정상 인식하나, pageCache에 포트 없는 URL이 구워진 채로 캐시가 제공됨.
- **원인**: NAT/포트포워딩 환경에서 `Config.php`의 자동 감지(`if SERVER_PORT != 80 && != 443: $service['port'] = SERVER_PORT`)는 정상이나, 자동 감지 이전에 생성된 pageCache 파일이 포트 없는 URL을 그대로 서빙.
- **수정**:
  1. `config.php`에 `$service['port'] = 8085;` 명시 추가 (자동 감지 타이밍 의존 제거).
  2. 컨테이너 내부 `/var/www/html/cache/pageCache/` 내 `*.cache` 파일 전체 삭제 (stale 캐시 초기화).

| 파일 | 변경 내용 |
|------|-----------|
| `config.php` | `$service['port'] = 8085;` 추가 (domain 명시 줄 다음) |

### 검증 결과 (2026-05-18, tc-php85)

| 도메인 | CSS URL 포트 |
|--------|-------------|
| `example.com:8085` | `http://example.com:8085/skin/blog/periwinkle/css/bootstrap.css` ✓ |
| `abc.example.com:8085` | `http://abc.example.com:8085/skin/blog/periwinkle/css/bootstrap.css` ✓ |
| `bcd.example.com:8085` | `http://bcd.example.com:8085/skin/blog/periwinkle/css/bootstrap.css` ✓ |
| `cde.example.com:8085` | `http://cde.example.com:8085/skin/blog/periwinkle/css/bootstrap.css` ✓ |

---

## v3.36 — `addBlog()` 기본 에디터 `'modern'` → `'tinyMCE'` 수정 (2026-05-19)

- **변경 이유**: STAGE 1 v1.80과 동일 (상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조).

| 파일 | 변경 내용 |
|------|-----------|
| `library/model/blog.blogSetting.php` | `addBlog()`: `defaultEditor` 기본값 `'modern'` → `'tinyMCE'` |

---

## v3.37 — `requireStrictRoute()` 비표준 포트 환경 Referer 비교 버그 수정 (2026-05-19)

- **변경 이유**: STAGE 1 v1.81과 동일 (상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조).

| 파일 | 변경 내용 |
|------|-----------|
| `library/auth.php` | `requireStrictRoute()`: `$url['host'] == $_SERVER['HTTP_HOST']` → `$refererHost` (host:port 재조합) 비교 |

---

## v3.38 — `Validator::number()` 비숫자 + bypass 처리 수정 (2026-05-19)

- **변경 이유**: STAGE 1 v1.82와 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/boot/10-CoreClasses.php` | `Validator::number()`: 비숫자 + `bypass=true` 시 범위 체크 생략 후 true 반환 |

---

## v3.39 — `add/index.php` 임시 첨부파일 parent 업데이트 패치 소급 문서화 (2026-05-19)

- STAGE 1 v1.83과 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/owner/entry/add/index.php` | `addEntry()` 성공 후 `DBModel`로 `Attachments.parent=0` → 신규 entryId 업데이트 |

---

## v3.40 — `requireStrictRoute()` 포트 비교 로직 정정 (2026-05-19)

- **v3.37 수정 오류 정정**: STAGE 1 v1.84와 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `library/auth.php` | `requireStrictRoute()`: Referer host+port 재조합 → 호스트명만 추출 후 `$_SERVER['HTTP_HOST']`와 비교 |

## v3.41 — `Tag` 클래스 메서드 `static` 선언 추가 (PHP 8.0 Fatal Error 대응, 2026-05-19)

- STAGE 1 v1.85와 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/legacy/Textcube.Data.Tag.php` | `doesExist`, `addTagsWithEntryId`, `modifyTagsWithEntryId`, `deleteTagsWithEntryId`, `getTagsWithEntryId`, `_getMaxId` — `static` 선언 추가 |

## v3.42 — legacy Data 클래스 `@static@` 메서드 `static` 선언 일괄 추가 (2026-05-19)

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

## v3.43 — `getBlogURL()` domain 모드 서브도메인 점(.) 누락 표기 오류 수정 (2026-05-19)

- STAGE 1 v1.87과 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.
- 수정 파일: `library/model/blog.service.php:64`

## v3.44 — `00-UnifiedEnvironment.php` magic_quotes 제거 → `normalizeSuperglobalInput()` 동등 변환 소급 적용 (2026-05-31)

- STAGE 1 v1.3에서 `get_magic_quotes_gpc()` 블록을 단순 제거했으나, 원본의 전체 슈퍼글로벌 순회 구조를 동등 변환해야 함. null byte 제거(`str_replace(chr(0), '', $value)`)로 대체하여 입력값 정규화 책임 유지.
- 수정 파일: `framework/boot/00-UnifiedEnvironment.php`

---

## v3.45 — `control/action/user/suggest` SQL Injection 해소 (raw 쿼리 → DBModel 빌더 전환, 보안 수정, 2026-05-31)

- **변경 이유**: `interface/control/action/user/suggest/index.php`의 자동완성 쿼리가 validator를 통과한 `$_GET['input']`(`$IV` 타입 `string`)을 `LIKE "%...%"` 절에 escape 없이 직접 concat → **SQL Injection**. v3.06에서 권한 가드(`requirePrivilege('group.creators')`)를 추가했으나, 인증된 creator 권한 사용자 또는 CSRF로 악용될 여지가 남아 있었음.
- **upstream 근거**: Needlworks/Textcube `refs #747`, commit `9c73a64` (2015-02-27) — 동일 파일의 raw 쿼리를 `DBModel` 빌더로 전환. 본 포트 1.10.10 기반에는 미반영 상태였음.
- **포트 적응** (CLAUDE.md 보안 수정 워크플로우 준수):
  - upstream의 `init()`은 본 포트에 없는 신규 별칭 → 기존 동등 API `reset()`으로 변환.
  - 본 포트 `DBModel::getQualifierModel()`은 `escape=null`일 때 escape하지 않으므로(upstream 버전과 동작 차이), 두 qualifier 모두 `escape=true`를 명시하여 `POD::escapeString()` 적용을 보장.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/control/action/user/suggest/index.php` | raw `POD::queryAll("... LIKE \"%".$_GET['input']."%\" ...")` → `DBModel` 빌더(`reset` / `setQualifierSet(escape=true)` / `setLimit` / `getAll`). 미사용 `global $database;` 제거. |

- **적용 범위**: php7.4, php8.2, php8.4, php8.5 전 버전 동일 적용.

### 방어 검증 (악성 입력 테스트, `_sectest/suggest_sqli_test.php`)

적용 **전에** 포트의 실제 DBModel 코드 경로(`getQualifierModel` + `_makeWhereClause`)에 악성 입력을 직접 주입하여 생성 SQL을 추적. `POD::escapeString`은 MySQL `real_escape_string`(utf8/utf8mb4) 동작을 충실히 모델링(utf8mb4는 single-byte-safe charset이므로 GBK류 멀티바이트 우회 불성립). 실행 환경: `php:8.4-cli` 컨테이너.

| 악성 입력 | 생성된 WHERE (요약) | 판정 |
|-----------|---------------------|------|
| `' OR '1'='1` | `name LIKE '%\' OR \'1\'=\'1%'` | SAFE |
| `\' OR 1=1 -- ` | `name LIKE '%\\\' OR 1=1 -- %'` | SAFE |
| `"; DROP TABLE tc_Users;--` | `name LIKE '%\"; DROP TABLE tc_Users;--%'` | SAFE |
| `' UNION SELECT loginid,password FROM tc_Users -- ` | `name LIKE '%\' UNION SELECT ...%'` | SAFE |
| `admin'-- ` | `name LIKE '%admin\'-- %'` | SAFE |
| `a\0' OR 1=1` (NUL 바이트) | `name LIKE '%a\0\' OR 1=1%'` | SAFE |

- **결과**: 악성 입력 7종(평범한 입력 1 + 공격 6) **ALL PASS** — 모든 작은따옴표가 `\'`로, 백슬래시가 `\\`로 escape되어 문자열 리터럴 탈출 불가. NUL 바이트는 `00-UnifiedEnvironment.php`의 `normalizeSuperglobalInput`이 이 코드 이전에 이미 제거(이중 방어).

---

## v3.46 — `control/action/user/suggest` 반사형 + 저장형 XSS 해소 (출력 인코딩, 보안 수정, 2026-05-31)

- **변경 이유**: `interface/control/action/user/suggest/index.php`는 응답(`text/javascript`)이 `control.js`의 동적 `<script src>`로 **JS 실행**되는 JSONP 구조. 두 XSS 경로 존재:
  1. **반사형**: `$_GET['id']`(`$IV` `string`)·`$_GET['cursor']`를 JS 문자열 리터럴에 escape 없이 echo → 리터럴 탈출 시 임의 JS 실행. (정상 흐름의 `id`는 고정 DOM id `"suggestContainer"`라 실질 심각도 LOW이나, `requireStrictRoute`/`requirePrivilege` 가드가 뚫릴 경우 대비 심층방어.)
  2. **저장형**: 결과 행 `loginid - name`이 `control.js` `showSuggestion`의 **`innerHTML` sink**(L58)에 escape 없이 삽입 → 사용자 `name`에 `<img onerror>` 등 포함 시 실행. (`control.js`가 `replaceAll("&quot;",'"')`로 HTML escape된 입력을 되돌리는 설계 전제였으나 서버가 escape 누락.)
- **upstream**: master의 `suggest/index.php`·`control.js` 모두 동일하게 미escape — upstream 미수정. CLAUDE.md 보안 워크플로우에 따라 포트 내 기존 관용구로 방어.
- **조치**:
  - 반사형: `$_GET['id']`·`$_GET['cursor']` → `escapeJSInCData()` (포트 기존 JS 리터럴 escape 헬퍼, `library/function/javascript.php`). `control`은 `Dispatcher.php`에서 interfaceType `owner`로 매핑되어 `include.owner.php`가 해당 헬퍼를 로드함을 확인.
  - 저장형: 결과 행 → `htmlspecialchars($v, ENT_QUOTES)`(innerHTML 안전) 후 `str_replace`로 백슬래시·CR·LF를 JS 리터럴 안전 형태로 escape. `control.js`의 `&quot;` 되돌림 설계와 정합.
  - php7.4·8.2·8.4·8.5 전 버전 동일 적용.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/control/action/user/suggest/index.php` | `showSuggestion("…")` echo의 `id`·`cursor` → `escapeJSInCData()`; 결과 행 → `htmlspecialchars(ENT_QUOTES)` + 백슬래시·개행 escape |

### 방어 검증 (실제 JS 엔진 — node)

PHP 실제 escape 함수로 생성한 출력을 node 엔진에서 계측 stub(`alert`/`document.cookie`/`showSuggestion`)으로 실행:

- **반사형** (`_sectest/run_xss_node.js`): `");alert(document.cookie);//`, `</script>…`, 개행·백슬래시 우회, 이벤트 핸들러 등 6종 공격 입력 → **`alert` 미실행·`cookie` 미접근**, 페이로드는 문자열 인자로 흡수. **ALL PASS**.
- **저장형** (`_sectest/run_xss_innerhtml_node.js`): `<img src=x onerror=alert(1)>`, `"><script>…`, JS 리터럴 탈출, 백슬래시, 개행 → fixed 모드 전부 SAFE(코드 미실행 + 클라이언트 `replaceAll` 후 raw `<>` 미잔존). 대조군 raw(미수정)는 전부 VULNERABLE로 탐지(특히 "JS 리터럴 탈출" raw는 `alert` **실제 실행** 확인) → 테스트 유효성 입증.

## v3.47 — `owner/communication/comment`·`notify` / `owner/entry` 반사형 XSS 해소 (HTML 속성 출력 인코딩, 보안 수정, 2026-05-31)

- **변경 이유**: 검색/필터 폼의 hidden `<input … value="…">` 속성에 `$_POST` 값을 escape 없이 echo. 해당 변수(`name`·`search`·`status`·`visibility`)는 `$IV` `string` 타입이라 validator가 UTF-8·길이만 검사 → 내용 필터링 없음. `"><script>…`·`" onmouseover="…` 등으로 속성·태그 탈출 시 임의 스크립트 실행. 동일 파일 `comment/index.php:557`(노출형 검색창)은 이미 `htmlspecialchars($search)` 사용 — hidden input만 누락된 불일치.
- **upstream**: master의 `comment`·`notify`·`entry` index.php 모두 동일하게 미escape — upstream 미수정. CLAUDE.md 보안 워크플로우에 따라 포트 내 기존 관용구로 방어.
- **조치**: 각 reflected 값 → `htmlspecialchars($_POST['…'], ENT_QUOTES)`(이중따옴표 속성이므로 `"`·`'` 동시 escape). 같은 echo 블록의 타입-안전 값(`ip`·`category`·`withSearch`)도 출력 인코딩 정석·일관성을 위해 동일 적용. php7.4·8.2·8.4·8.5 전 버전 동일 적용.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/owner/communication/comment/index.php` | hidden input `ip`·`name`·`category`·`search`·`withSearch`·`status` value → `htmlspecialchars(ENT_QUOTES)` |
| `interface/owner/communication/notify/index.php` | hidden input `search`·`withSearch` value → `htmlspecialchars(ENT_QUOTES)` |
| `interface/owner/entry/index.php` | hidden input `visibility` value → `htmlspecialchars(ENT_QUOTES)` |

### 방어 검증 (실제 HTML 파서 — PHP DOMDocument)

PHP `htmlspecialchars(ENT_QUOTES)`로 생성한 속성 출력을 `DOMDocument`로 파싱하여 위험 노드/주입 이벤트 핸들러 생성 여부 확인 (`_sectest/xss_attr_test.php`, `php:8.4-cli`):

- 공격 입력 `"><script>…`, `"><img src=x onerror=…>`, `" onmouseover="…`, `"></input><script>…`, `"><iframe src=javascript:…>` → fixed 모드 전부 위험 노드·이벤트 **0개**, `value` 속성에 페이로드 원문이 **데이터로만** 보존. 정상 입력은 기능 보존. **ALL PASS**.
- 대조군 raw(미수정)는 동일 파서에서 `<script>`·`<img onerror>`·`<iframe>` 노드 및 주입 이벤트 속성이 실제 생성됨을 확인 → 테스트 유효성 입증.

## v3.48 — `owner/help` 경로순회/LFI 해소 (`$_GET['lang']` 화이트리스트 정규화, 보안 수정, 2026-05-31)

- **변경 이유**: `interface/owner/help/index.php`가 `$filename = $_GET['lang'].'.'.$_GET['subject'].'.html'`를 `file_get_contents(ROOT."/interface/owner/help/".$filename)`로 읽음. `subject`는 `$IV` `filename` 타입이라 안전하나 **`lang`은 `string` 타입**이라 내용 필터링이 없어 `../`·`..\` 등으로 help 디렉토리 이탈(LFI) 가능. 해당 액션은 `requireOwnership`/`requirePrivilege` 가드가 없어 인증 사용자 범위 노출.
- **부가 발견**: `Validator::language` 정규식이 delimiter 백슬래시로 깨져(`preg_match('\^[[:alpha:]]{2}…')`) 항상 에러/false → `language` 타입은 검증 수단으로 부적합. 따라서 `lang` 방어는 화이트리스트로 적용.
- **upstream**: master의 `help/index.php`도 동일하게 `lang` 미정규화 — upstream 미수정. CLAUDE.md 보안 워크플로우에 따라 포트에서 직접 방어.
- **조치**: `$lang = preg_replace('/[^A-Za-z0-9_\-]/', '', $_GET['lang']);` (언어코드만 남기고 구분자·점·`..` 제거). `basename()`은 Linux에서 `\`를 구분자로 보지 않아 Windows 배포 시 백슬래시 우회가 가능하므로 화이트리스트 채택. php7.4·8.2·8.4·8.5 전 버전 동일 적용.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/owner/help/index.php` | `$_GET['lang']` → `preg_replace('/[^A-Za-z0-9_\-]/','',…)` 화이트리스트 정규화 후 경로 결합 |

### 방어 검증 (경로 정규화 — PHP)

`_sectest/help_lfi_test.php`(`php:8.4-cli`)에서 악성 `lang` 입력으로 최종 경로를 정규화하여 help 디렉토리 이탈 여부 판정:

- `../../../../../../etc/passwd`, `../../config`, `foo/../../../bar`, `ko/../../secret`, `ko\..\..\win`(백슬래시) → fixed 모드 전부 디렉토리 구분자 제거·정규화 경로가 help prefix 유지 → **이탈 0**. 정상값 `ko`·`en`은 보존. **ALL PASS**.
- 대조군 raw(미수정)는 동일 정규화에서 디렉토리 이탈 확인 → 테스트 유효성 입증.

## v3.49 — owner 상태변경 액션 CSRF 가드 보강 + `requireStrictRoute` path-모드 강화 (보안 수정, 2026-05-31)

- **변경 이유 (두 결함)**:
  1. 일부 owner 상태변경 액션이 `requireStrictRoute()`(CSRF Referer 검증) 미보유. 권한은 `preprocessor.php`의 `requireOwnership()`+`Aco`로 중앙 방어되나 CSRF는 개별 의존. `comment/delete`·`trash/emptyTrash`는 보유 ↔ 더 파괴적인 `trash/comment/delete`(영구삭제)·`trash/*/revert`는 누락(실수 누락). 다수가 GET `$suri['id']` 트리거라 SameSite=Lax로도 CSRF 성립.
  2. `requireStrictRoute`가 host만 비교 → `service.type=path`(단일 host 멀티블로그)에서 cross-blog CSRF 미차단.
- **upstream**: master도 동일 미수정(가드 누락 + host-only). CLAUDE.md 보안 워크플로우에 따라 포트에서 보강.
- **조치**:
  - (A) `requireStrictRoute()` 강화(`library/auth.php`): 판정을 순수 헬퍼 `__referentBlogScopeStatus()`(`pass`/`block`/`failopen` 상태 반환)로 분리. single/domain 모드는 기존 host 비교 유지(**회귀 0**), path 모드만 Referer blogname == 현재 `blog.name` 비교 추가(추출 실패 시 fail-open). **fail-open 발생은 `trigger_error(E_USER_NOTICE)`로 기록**(textcube 코어 관행과 일관, 운영 가시성). upstream에 없는 본 포트 개선.
  - (B) 가드-누락 13개 액션에 `requireStrictRoute()` 추가(보유측 동일 패턴, `require preprocessor` 직후). 오탐 `network/teamblog/changeBlog`(redirect만) 제외.
  - php7.4·8.2·8.4·8.5 전 버전 적용.

| 파일 | 변경 내용 |
|------|-----------|
| `library/auth.php` | `requireStrictRoute()` → 순수 헬퍼 `__referentBlogScopeStatus()`(pass/block/failopen) 분리 + path-모드 blogname 스코프 검증 + fail-open `trigger_error(E_USER_NOTICE)` 로깅 |
| `interface/owner/communication/trash/comment/{delete,revert}/index.php` | `requireStrictRoute()` 추가 |
| `interface/owner/communication/trash/trackback/{delete,revert}/index.php` | `requireStrictRoute()` 추가 |
| `interface/owner/skin/{coverpage/delete,sidebar/delete,adminSkin/set}/index.php` | `requireStrictRoute()` 추가 |
| `interface/owner/setting/domain/{primary,secondary}/index.php`, `setting/userSetting/set/index.php` | `requireStrictRoute()` 추가 |
| `interface/owner/data/{optimize,export}/index.php`, `entry/attachmulti/orphandelete/index.php` | `requireStrictRoute()` 추가 |

### 방어 검증 (회귀 + 강화 — 16 케이스)

`_sectest/csrf_strictroute_test.php`(`php:8.4-cli`)에서 `__isReferentSameBlogScope` 순수 판정 단위 테스트:

- **회귀**: single/domain/path 동일 블로그, 포트 포함 host, https scheme, basePath → `pass`(기존 정상 요청 보존).
- **강화**: path-모드 cross-blog(`/myblog` vs `/other`), basePath cross-blog, prefix 함정(`blog` vs `blog2`), evil.com 이름 블로그 → `block`.
- **fail-open 가시화**: path 모드 blogname 미추출(`http://ex.com/`, path 없음) → `failopen`(통과+로깅).
- **기존 방어**: 외부 도메인, 무 Referer → `block`.
- 18/18 **ALL PASS**. 추가: 13개 액션 `requireStrictRoute` 존재 grep + auth.php·13개 액션 PHP lint 통과.

---

## v3.50 — Clipboard API HTTP fallback 개선 (`resources/script/common3.js`, 2026-05-31)

- **변경 이유**: SECURITY.md [INFO] #7. `copyUrl()`은 HTTPS(secure context)에서만 `navigator.clipboard.writeText()` 사용. HTTP 환경의 fallback `_legacyCopyUrl()`이 IE `window.clipboardData`만 시도하고 실패 시 텍스트 선택만 → 현대 브라우저(Chrome/Firefox/Edge) HTTP 접속 시 자동 복사 불가(수동 Ctrl+C 필요).
- **upstream**: master의 `copyUrl()`은 IE `clipboardData` + 텍스트 선택만(Clipboard API·`_legacyCopyUrl` 모두 없음) — 본 포트가 이미 앞섬. 적용할 upstream 픽스 없음 → 포트 자체 개선.
- **조치**: `_legacyCopyUrl()`을 3단계 fallback으로 — ① IE `clipboardData` → ② `document.execCommand('copy')`(deprecated이나 secure context 불필요 → HTTP 자동복사 복원) → ③ 텍스트 선택(수동). php7.4·8.2·8.4·8.5 전 버전 적용.
- **성격**: 보안 취약점이 아닌 호환성/UX 개선(INFO 등급).
- **검증**: node `--check` 5스테이지 구문 통과.

| 파일 | 변경 내용 |
|------|-----------|
| `resources/script/common3.js` | `_legacyCopyUrl()` → `execCommand('copy')` fallback 추가(HTTP 자동복사) |

---

## v3.51 — 쿠키 보안 속성(HttpOnly/SameSite/Secure) 보강 (보안 수정, 2026-05-31)

- **변경 이유**: SECURITY.md [LOW] #5. 세션·로그인·OpenID·게스트 쿠키의 `setcookie()`/`session_set_cookie_params()`가 `HttpOnly`/`SameSite`/`Secure` 미설정 → XSS 세션 탈취 및 CSRF 노출. v3.49 CSRF 방어를 환경 비의존으로 보완.
- **upstream**: master도 레거시 형식(미설정) — 미수정. 포트 자체 개선.
- **조치**: 7개 파일 12개 호출을 PHP 7.4+ 배열 옵션으로 전환 — `httponly=>true`, `samesite=>'Lax'`, `secure=>(bool)service.useSSL`(HTTP 운영에서 쿠키 정상 전송되도록 조건부). 모든 쿠키가 서버사이드(`$_COOKIE`)에서만 읽혀 HttpOnly 회귀 없음. php7.4·8.2·8.4·8.5 전 버전 적용.
- **검증**: PHP 7.4/8.4 배열 옵션 형식 검증 + 7파일×5스테이지 lint(syntax) 통과.

| 파일 | 쿠키 |
|------|------|
| `library/preprocessor.php` | `session_set_cookie_params` (세션) |
| `framework/legacy/Textcube.Control.Session.php` · `Session.Memcached.php` | 세션 ID |
| `library/auth.php` | `TSSESSION_LOGINID` (로그인 ID 기억) |
| `framework/legacy/Textcube.Control.Openid.php` | OpenID |
| `interface/blog/comment/comment/index.php` · `comment/add/index.php` | `guestName`/`guestHomepage` |

---

## v3.52 — StatGraph jpgraph(QPL) → 의존 없는 SVG 그래프 대체 (라이선스, 2026-05-31)

- **변경 이유**: SECURITY.md [MEDIUM] #3. `plugins/StatGraph`가 jpgraph 1.x(QPL 라이선스, 상업적 사용 시 별도 라이선스 필요)를 번들. PHP 7.4+ 호환성을 위해 이미 stub(비활성)이었으나 QPL 소스 파일이 잔존.
- **upstream**: master도 jpgraph 번들 — 본 포트 개선.
- **조치**: `DisplayStatisticsGraph()`를 외부 라이브러리 의존 없는 **인라인 SVG line chart**로 재구현(`Statistics::getWeeklyStatistics()` 최근 8일 방문수 → SVG polyline·점·값·날짜). 미사용 jpgraph QPL 6파일 + `count.php`(`count/`) 제거. 자체 SVG 코드(GPL)라 라이선스 클린.
- **검증**: `_sectest/statgraph_svg_test.php` — PHP 7.4/8.4 SVG 생성 확인(polyline·circle·값·날짜, jpgraph 미잔존) PASS. 5스테이지 lint 통과.
- php7.4·8.2·8.4·8.5 전 버전 적용. 원본 `Textcube-1.10.10`은 수정 금지로 jpgraph 유지.

| 파일 | 변경 |
|------|------|
| `plugins/StatGraph/index.php` | jpgraph PNG → 인라인 SVG line chart 재구현 |
| `plugins/StatGraph/count/` (jpgraph QPL 6 + `count.php`) | 제거 |

---

## v3.53 — OpenID 2.0 (EOL) 제거 → OIDC 재구현 (이중 옵트인, 2026-05-31)

- **변경 이유**: SECURITY.md [FIXED] #4. OpenID 2.0 은 프로토콜 EOL(주요 Provider 2015 년경 종료)이고 phpopenid(JanRain)는 유지보수 중단. 인증을 OpenID Connect(OIDC)로 재구현·일원화.
- **upstream**: master도 OpenID 2.0 유지 — 본 포트 개선.
- **이중 옵트인(기본 비활성)**: ① CL_OpenID 플러그인 활성 ② 플러그인 설정에서 OIDC 활성화 + issuer/client_id/client_secret 입력. 두 게이트를 모두 충족해야 동작(플러그인 활성화만으로는 비활성).
- **OIDC 엔진**(`Textcube.Control.OIDC.php`, 신규): 의존 없음(openssl+JSON+curl, composer/외부 라이브러리 없음). discovery(`.well-known/openid-configuration`) · Authorization Code flow · PKCE(S256) · state/nonce · id_token JWT **RS256 한정** 검증(JWKS 서명·iss·aud·exp·nonce 일치). JWK(n,e)→PEM 은 최소 ASN.1 DER 자체 구현.
- **게스트 댓글**: claims → `Acl 'openid'` 식별자(`oidc:{iss}#{sub}`) 단일 출처 주입 → 기존 댓글 저장/조회/삭제·폼 흐름 무수정 호환. 표시명은 name/email, ViewCommenter·getDisplayName 은 oidc 식별자 링크 생략·`htmlspecialchars`.
- **사용자/관리자 로그인**: `UserSettings`의 `openid.*` **명시적 연결** 매핑만(자동 계정생성 없음). writers 권한 시 사용자 세션 승격(legacy setAcl 동일 판정). sub 기반이라 email 공유·provider 사칭으로 매핑 가로채기 불가.
- **OpenID 2.0 완전 제거**: phpopenid(53파일×5위치 = 265) 삭제, `Openid.php` 헬퍼 전용 축소(전 메서드 **static화** — PHP 8 non-static 정적호출 fatal 동시 해소), login/account/setting 2.0 흐름 제거, CL_OpenID 2.0 리스너·Loader `OpenIDSession` 매핑·죽은 상수(`OPENID_LIBRARY_ROOT`/`Auth_OpenID_NO_MATH_SUPPORT`) 정리.
- **검증**(`_sectest/oidc_*.php`, PHP 7.4/8.4): 이중 옵트인 게이트 6케이스 + id_token 적대적 9케이스(서명 위조/alg=none/HS256 다운그레이드/aud·iss/exp/nonce **전부 차단**) + 신원 매핑(미연결→게스트, 연결+writers→사용자, **다른 issuer 동일 sub→매핑 거부**) 전부 PASS. 8파일×5위치 lint 통과.
- php7.4·8.2·8.4·8.5 전 버전 적용. 원본 `Textcube-1.10.10`은 수정 금지로 OpenID 2.0 유지.

| 파일 | 변경 |
|------|------|
| `framework/legacy/Textcube.Control.OIDC.php` | 신규 — OIDC 클라이언트 엔진(discovery/flow/JWT 검증/신원 매핑) |
| `framework/legacy/Textcube.Control.Openid.php` | 헬퍼 전용 축소(static화), 2.0 프로토콜·phpopenid 의존 제거 |
| `interface/login/openid/index.php` · `callback/index.php` | OIDC 전용 진입점 + 콜백(신규) |
| `interface/owner/setting/account/openid/index.php` | OIDC 식별자 연결 전용 |
| `interface/owner/setting/openid/{change,delegate}/index.php` | 설정 인라인화 / 위임 비활성 |
| `plugins/CL_OpenID/{index.php,index.xml}` | OIDC 설정 탭 + ViewCommenter OIDC 적응, 2.0 리스너 제거 |
| `framework/id/textcube/config.default.php`, `framework/legacy/Needlworks.PHP.Loader.php` | 죽은 상수·`OpenIDSession` 매핑 정리 |
| `library/contrib/phpopenid/` (53파일) | **제거** |

---

<sub>Modifications documented herein by @deokio (2026), performed with AI assistance (Anthropic Claude) under human review.
No additional copyright is asserted. Licensed under GPL (same as the rest of the project).</sub>
