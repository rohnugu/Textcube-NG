# CHANGELOG — Textcube 1.10.10 PHP 8.4 → PHP 8.5 이식 (STAGE 4)

대상 PHP: 8.5  
기반 버전: php8.4-Textcube-1.10.10 (STAGE 3, v2.02)  
작성일: 2026-05-16 / 최종 수정: 2026-05-16

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

## v3.05 — `MySQL/Adapter.php` Prepared Statement API 구현 + DBAdapter 기본 어댑터 `MySQLi` 변경 (2026-05-17)

- **변경 이유**: php8.4 v2.05 와 동일 (상세 내용 `../php8.4-Textcube-1.10.10/changelog8.2to8.4.md` 참조).

| 파일 | 변경 내용 |
|------|-----------|
| `framework/data/MySQL/Adapter.php` | `prepare()` / `bindAndExecute()` / `fetchAllStmt()` — RuntimeException 스텁 → mysqli 위임 구현 |
| `framework/alias/DBAdapter.php` | 폴백 기본값 `'MySQL'` → `'MySQLi'` |
| `setup.php` | dbms 감지 순서 변경 — MySQLi 를 MySQL 보다 먼저 추가하여 신규 설치 시 기본 선택값이 MySQLi 가 되도록 수정 |

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

## v3.06 — `control/action/user/add`, `delete`, `suggest` 권한 가드 추가 (보안 수정, 2026-05-17)

- **변경 이유**: php8.4 v2.06 과 동일 (상세 내용 `../php8.4-Textcube-1.10.10/changelog8.2to8.4.md` 참조).

| 파일 | 변경 내용 |
|------|-----------|
| `interface/control/action/user/add/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |
| `interface/control/action/user/delete/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |
| `interface/control/action/user/suggest/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |

---

## v3.30 — STAGE 4 종료 (2026-05-16)

- 회귀 테스트 결과 SECURITY.md 기록.
- README.md PHP 8.5 명시 최종 확인.
- `release-php8.5.zip` 산출.

---

## v3.07 — `phpopenid/CryptUtil.php` `mt_rand()` 폴백 → `random_bytes()` 교체 (2026-05-17)

- **변경 이유**: STAGE 2 v2.02 / STAGE 3 v2.07 과 동일. `getBytes()` 폴백 경로의 `mt_rand()` 루프 → `random_bytes($num_bytes)` (CSPRNG, PHP 7.0+) 교체.

| 파일 | 라인 | 변경 내용 |
|------|------|-----------|
| `library/contrib/phpopenid/Auth/OpenID/CryptUtil.php` | 60 | `mt_rand()` 폴백 블록 → `random_bytes($num_bytes)` |

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

---

<sub>Modifications documented herein by @deokio (2026), performed with AI assistance (Anthropic Claude) under human review.
No additional copyright is asserted. Licensed under GPL (same as the rest of the project).</sub>
