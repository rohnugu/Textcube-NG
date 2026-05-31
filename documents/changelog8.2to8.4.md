# CHANGELOG — Textcube 1.10.10 PHP 8.2 → PHP 8.4 이식 (STAGE 3)

대상 PHP: 8.4  
기반 버전: php8.2-Textcube-1.10.10 (STAGE 2, v2.00)  
작성일: 2026-05-16 / 최종 수정: 2026-05-31

이전 단계 누적분(v1.75 ~ v2.00): `../php8.2-Textcube-1.10.10/changelog7.4to8.2.md` 참조

---

## v2.01 — STAGE 3 메타 파일 갱신 (2026-05-16)

- `changelog8.2to8.4.md` 신설: STAGE 3 헤더, 기반 버전 v2.00, 이전 누적분 참조 명시.
- `SECURITY.md`: 헤더 STAGE 3로 갱신. 모든 항목 현황을 STAGE 3 시점으로 재평가.
- `README.md`: REQUIREMENTS PHP 8.2 → PHP 8.4 갱신.
- 환경 구성:
  - 컨테이너: `php:8.4-apache` 공식 이미지 (`podman run`) + 패키지 직접 설치 (Containerfile 빌드 없음).
  - DB: `textcube84` (MySQL 5.7, tc-mysql 공유).
  - 포트: 8084.
  - 초기 상태: php8.2-Textcube-1.10.10 (v2.00) 소스를 그대로 복사 → PHP 8.4 환경에서 tc_full_test.sh 실행.
- 초기 검증 결과: **33/33 PASS, PHP 오류 0건** (PHP 8.2 이식 코드가 PHP 8.4에서 그대로 통과).

---

## v2.02 — mysqli 절차형 API → 객체지향 전환 (PHP 8.5+ 대비) (2026-05-16)

- **변경 이유**: mysqli 절차형 함수(`mysqli_error()`, `mysqli_errno()`, `mysqli_num_rows()` 등)는 PHP 8.5 이후 deprecated 예정. 객체지향 API로 전환하여 미래 버전 호환성 확보. (php.net/migration84)
- **PHP 권고**: mysqli 연결/결과 객체의 프로퍼티/메서드를 직접 사용할 것.

### 변환 목록

| 절차형 (구) | 객체지향 (신) | 적용 파일 |
|-------------|---------------|----------|
| `mysqli_character_set_name(POD::$db)` | `POD::$db->character_set_name()` | Debug.php 2개 |
| `mysqli_error(POD::$db)` | `POD::$db->error` | Debug.php 2개, Adapter.php |
| `mysqli_errno(POD::$db)` | `POD::$db->errno` | Debug.php 2개 |
| `mysqli_num_rows($result)` | `$result->num_rows` | Debug.php 2개, Adapter.php |
| `mysqli_affected_rows(POD::$db)` | `POD::$db->affected_rows` | Debug.php 2개 |
| `mysqli_free_result($handle)` | `$handle->free()` | Adapter.php |
| `mysqli_fetch_array($handle)` | `$handle->fetch_array()` | Adapter.php |
| `mysqli_fetch_row($handle)` | `$handle->fetch_row()` | Adapter.php |
| `mysqli_fetch_assoc($handle)` | `$handle->fetch_assoc()` | Adapter.php |

| 파일 | 변경 건수 |
|------|-----------|
| `framework/data/MySQLi/Debug.php` | 7건 |
| `framework/data/MySQL/Debug.php` | 7건 |
| `framework/data/MySQLi/Adapter.php` | 5건 |

**비고**: `mysqli_report(MYSQLI_REPORT_OFF)` (`Adapter.php:17`)는 전역 설정 함수이며 PHP 8.4에서 deprecated 대상이 아니므로 유지.

### 테스트 결과 (2026-05-16, tc-php84)

| 모드 | 결과 |
|------|------|
| tc_full_test.sh 33/33 | **PASS** |
| PHP 오류 로그 | **0건** |
| Apache 오류 로그 | **0건** |

---

## v2.03 — pecl/memcache (websupport-sk 포크) PHP 8.4 설치 및 검증 (2026-05-16)

- **배경**: 공식 pecl/memcache 8.2는 PHP 8.5에서 `php_smart_string_public.h` 헤더 제거로 빌드 불가였으나, [websupport-sk/pecl-memcache](https://github.com/websupport-sk/pecl-memcache) 포크가 PHP 8.4+ 지원을 제공함 (PR #118 — PHP 8.5-alpha3 대응 포함).
- **설치 방법**: `git clone https://github.com/websupport-sk/pecl-memcache.git` → `phpize && ./configure --enable-memcache && make && make install`
- **결과**: tc-php84 컨테이너에 memcache.so 설치 성공.
- **검증 시 임시 활성화**: `$service['memcached'] = true;` / `$memcached['server'] = 'tc-memcached';` / `$memcached['port'] = 11211;` — 컨테이너 내부에서만 임시 적용. 저장소 `config.php` 기본값은 비활성(`$service['memcached'] = 0;`) 유지.
- **검증**: memcache 활성화 상태에서 tc_full_test.sh 33/33 PASS, PHP 오류 로그 0건, Apache 오류 로그 0건.

### 테스트 결과 (2026-05-16, tc-php84, memcache 활성화)

| 모드 | 결과 |
|------|------|
| tc_full_test.sh 33/33 | **PASS** |
| PHP 오류 로그 | **0건** |
| Apache 오류 로그 | **0건** |
| memcache set/get | **정상** |

---

## v2.05 — `MySQL/Adapter.php` Prepared Statement API 구현 + DBAdapter 기본 어댑터 `MySQLi` 변경 (2026-05-17)

- **변경 이유 (1) — Prepared Statement stub 오류**: `MySQL/Adapter.php` 는 PHP 7.0의 `ext/mysql` 제거 이후 내부적으로 `new mysqli()` 를 사용하는 MySQLi 래퍼이다. v1.85에서 Prepared Statement API 를 인터페이스에 추가할 때 MySQL 어댑터 구현을 `RuntimeException` 스텁으로 잘못 작성 → 기본 어댑터(MySQL) 사용 시 로그인 포함 모든 prepared statement 호출 500 오류.
- **변경 이유 (2) — DBAdapter 기본값**: `framework/alias/DBAdapter.php` 의 폴백 어댑터가 `'MySQL'`로 고정되어 있어, `config.php` 에 `$database['dbms']` 를 명시하지 않으면 구 어댑터가 선택됨. 신규 설치 기본값으로 `'MySQLi'` 가 적절.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/data/MySQL/Adapter.php` | `prepare()` / `bindAndExecute()` / `fetchAllStmt()` — RuntimeException 스텁 → mysqli 위임 구현 |
| `framework/alias/DBAdapter.php` | 폴백 기본값 `'MySQL'` → `'MySQLi'` |
| `setup.php` | dbms 감지 순서 변경 — MySQLi 를 MySQL 보다 먼저 추가하여 신규 설치 시 기본 선택값이 MySQLi 가 되도록 수정 |

---

## v2.04 — `control/server/config`, `control/server/rewrite` 권한 가드 추가 + 인자 따옴표 버그 수정 (보안 수정, 2026-05-17)

- **변경 이유**: `interface/control/server/config/index.php` 및 `interface/control/server/rewrite/index.php` 에 `requireStrictRoute()` 후 권한 검사가 없어, `group.owners` 권한 사용자(블로그 소유자)가 시스템 전역 설정 변경 및 `.htaccess` 덮어쓰기가 가능한 취약점. 실제 악용 가능성 확인됨.
- **추가 버그 수정**: 이전 수정에서 shell sed 처리 중 `requirePrivilege(group.creators)` 로 따옴표가 누락된 채 커밋됨. PHP 에서 `group.creators` 는 정의되지 않은 상수(PHP 8에서는 E_WARNING, 이전에는 문자열 자동 변환)이므로 가드가 실질적으로 무효였음. 올바른 문자열 리터럴 `requirePrivilege('group.creators')` 로 수정.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/control/server/config/index.php` | `requirePrivilege(group.creators)` → `requirePrivilege('group.creators')` (따옴표 누락 수정) |
| `interface/control/server/rewrite/index.php` | `requirePrivilege(group.creators)` → `requirePrivilege('group.creators')` (따옴표 누락 수정) |

> **확인된 취약점**: 시나리오 테스트에서 `group.owners` 계정으로 서버 설정 변경(`$service['timeout']` 전역 변경, `error=0` 확인) 및 `.htaccess` 임의 덮어쓰기로 URL 리라이팅 전체 중단 재현됨.

---

## v2.06 — `control/action/user/add`, `delete`, `suggest` 권한 가드 추가 (보안 수정, 2026-05-17)

- **변경 이유**: `interface/control/action/user/add/index.php`, `delete/index.php`, `suggest/index.php` 에 `requireStrictRoute()` 후 `requirePrivilege('group.creators')` 가드가 없어, `group.owners` 권한 사용자(블로그 소유자, userid≠1)가 직접 API 를 호출하여 사용자 추가·삭제 및 전체 사용자 loginid/이름 열람이 가능한 취약점. 실제 악용 확인됨 — 테스트에서 `group.owners` 계정으로 신규 사용자 생성(hacktest), 기존 사용자 삭제(writer), 전체 사용자 목록 열거를 재현함.
- **PHP 보안 권고**: 관리 기능 엔드포인트는 반드시 최소 권한 원칙(PoLP)에 따라 `requirePrivilege()` 가드를 배치할 것.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/control/action/user/add/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |
| `interface/control/action/user/delete/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |
| `interface/control/action/user/suggest/index.php` | `requireStrictRoute();` 다음 줄에 `requirePrivilege('group.creators');` 추가 |

> **확인된 취약점**: `group.owners` 계정으로 `user/add` — 시스템 사용자 추가, `user/delete` — 기존 사용자 삭제, `user/suggest` — loginid·이름 전수 열거가 가능함을 실제 테스트에서 확인함.

---

## v2.30 — STAGE 3 종료

- 회귀 테스트 결과 SECURITY.md 기록.
- SECURITY.md 모든 항목 현황 최종 재평가.
- README.md PHP 8.4 명시 최종 확인.
- `release-php8.4.zip` 산출.

---

## v2.07 — `phpopenid/CryptUtil.php` `mt_rand()` 폴백 → `random_bytes()` 교체 (2026-05-17)

- **변경 이유**: STAGE 2 v2.02 와 동일. `getBytes()` 폴백 경로의 `mt_rand()` 루프 → `random_bytes($num_bytes)` (CSPRNG, PHP 7.0+) 교체.

| 파일 | 라인 | 변경 내용 |
|------|------|-----------|
| `library/contrib/phpopenid/Auth/OpenID/CryptUtil.php` | 60 | `mt_rand()` 폴백 블록 → `random_bytes($num_bytes)` |

---

## v2.31 — STAGE 3 진행 상태 재점검 (2026-05-17)

- v2.01 ~ v2.06 + v2.30 전체 항목 코드 대조 점검 완료.
- v2.03 본문 정정: "config.php 변경" → "검증 시 컨테이너 내부 임시 활성화" (저장소 기본값 비활성 유지).
- STAGE 3·4 핵심 보안 수정 및 어댑터 코드 양쪽 일관 적용 확인.
- 나머지 항목 코드 상태 일치. 누락 없음.

---

## v2.32 — phpmigtest COPY 기반 격리 테스트 (2026-05-17)

- `Containerfile.phpmigtest.php84` 신설: STAGE 3 코드를 COPY 기반으로 격리하여 tc_full_test.sh 실행 — 호스트 소스 디렉토리 보호.
- `textcube-migtest-php84` 이미지 빌드 완료.
- tc_full_test.sh 33/33 PASS, PHP 오류 로그 0건.

---

## v2.33 — `addBlog()` 기본 에디터 `'modern'` → `'tinyMCE'` 수정 (2026-05-19)

- **변경 이유**: STAGE 1 v1.80과 동일 (상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조).

| 파일 | 변경 내용 |
|------|-----------|
| `library/model/blog.blogSetting.php` | `addBlog()`: `defaultEditor` 기본값 `'modern'` → `'tinyMCE'` |

---

## v2.34 — `requireStrictRoute()` 비표준 포트 환경 Referer 비교 버그 수정 (2026-05-19)

- **변경 이유**: STAGE 1 v1.81과 동일 (상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조).

| 파일 | 변경 내용 |
|------|-----------|
| `library/auth.php` | `requireStrictRoute()`: `$url['host'] == $_SERVER['HTTP_HOST']` → `$refererHost` (host:port 재조합) 비교 |

---

## v2.35 — `Validator::number()` 비숫자 + bypass 처리 수정 (2026-05-19)

- **변경 이유**: STAGE 1 v1.82와 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/boot/10-CoreClasses.php` | `Validator::number()`: 비숫자 + `bypass=true` 시 범위 체크 생략 후 true 반환 |

---

## v2.36 — `add/index.php` 임시 첨부파일 parent 업데이트 패치 소급 문서화 (2026-05-19)

- STAGE 1 v1.83과 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `interface/owner/entry/add/index.php` | `addEntry()` 성공 후 `DBModel`로 `Attachments.parent=0` → 신규 entryId 업데이트 |

---

## v2.37 — `requireStrictRoute()` 포트 비교 로직 정정 (2026-05-19)

- **v2.34 수정 오류 정정**: STAGE 1 v1.84와 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `library/auth.php` | `requireStrictRoute()`: Referer host+port 재조합 → 호스트명만 추출 후 `$_SERVER['HTTP_HOST']`와 비교 |

## v2.38 — `Tag` 클래스 메서드 `static` 선언 추가 (PHP 8.0 Fatal Error 대응, 2026-05-19)

- STAGE 1 v1.85와 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.

| 파일 | 변경 내용 |
|------|-----------|
| `framework/legacy/Textcube.Data.Tag.php` | `doesExist`, `addTagsWithEntryId`, `modifyTagsWithEntryId`, `deleteTagsWithEntryId`, `getTagsWithEntryId`, `_getMaxId` — `static` 선언 추가 |

## v2.39 — legacy Data 클래스 `@static@` 메서드 `static` 선언 일괄 추가 (2026-05-19)

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

## v2.40 — `getBlogURL()` domain 모드 서브도메인 점(.) 누락 표기 오류 수정 (2026-05-19)

- STAGE 1 v1.87과 동일. 상세 내용 `../php7.4-Textcube-1.10.10/CHANGELOG.md` 참조.
- 수정 파일: `library/model/blog.service.php:64`

## v2.41 — `00-UnifiedEnvironment.php` magic_quotes 제거 → `normalizeSuperglobalInput()` 동등 변환 소급 적용 (2026-05-31)

- STAGE 1 v1.3에서 `get_magic_quotes_gpc()` 블록을 단순 제거했으나, 원본의 전체 슈퍼글로벌 순회 구조를 동등 변환해야 함. null byte 제거(`str_replace(chr(0), '', $value)`)로 대체하여 입력값 정규화 책임 유지.
- 수정 파일: `framework/boot/00-UnifiedEnvironment.php`

## v2.42 — `control/action/user/suggest` SQL Injection 해소 (raw 쿼리 → DBModel 빌더 전환, 보안 수정, 2026-05-31)

- STAGE 4 v3.45와 동일 (5스테이지 공통 적용). 상세·악성 입력 검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.45 및 SECURITY.md 참조.
- 수정 파일: `interface/control/action/user/suggest/index.php`

## v2.43 — `control/action/user/suggest` 반사형 + 저장형 XSS 해소 (출력 인코딩, 보안 수정, 2026-05-31)

- STAGE 4 v3.46과 동일 (5스테이지 공통 적용). 상세·검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.46 및 SECURITY.md 참조.
- 수정 파일: `interface/control/action/user/suggest/index.php`

## v2.44 — `owner/communication/comment`·`notify` / `owner/entry` 반사형 XSS 해소 (HTML 속성 출력 인코딩, 보안 수정, 2026-05-31)

- STAGE 4 v3.47과 동일 (5스테이지 공통 적용). 상세·검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.47 및 SECURITY.md 참조.
- 수정 파일: `interface/owner/communication/comment/index.php`, `interface/owner/communication/notify/index.php`, `interface/owner/entry/index.php`

## v2.45 — `owner/help` 경로순회/LFI 해소 (`$_GET['lang']` 화이트리스트 정규화, 보안 수정, 2026-05-31)

- STAGE 4 v3.48과 동일 (5스테이지 공통 적용). 상세·검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.48 및 SECURITY.md 참조.
- 수정 파일: `interface/owner/help/index.php`

## v2.46 — owner 상태변경 액션 CSRF 가드 보강 + `requireStrictRoute` path-모드 강화 (보안 수정, 2026-05-31)

- STAGE 4 v3.49와 동일 (5스테이지 공통 적용). 상세·회귀 검증(18 케이스) 내용은 STAGE 4 `changelog8.4to8.5.md` v3.49 및 SECURITY.md 참조.
- 수정 파일: `library/auth.php` + owner 상태변경 액션 13개

## v2.47 — Clipboard API HTTP fallback 개선 (`resources/script/common3.js`, 2026-05-31)

- STAGE 4 v3.50과 동일 (5스테이지 공통 적용). 상세 내용은 STAGE 4 `changelog8.4to8.5.md` v3.50 및 SECURITY.md #7 참조.
- 수정 파일: `resources/script/common3.js`

## v2.48 — 쿠키 보안 속성(HttpOnly/SameSite/Secure) 보강 (보안 수정, 2026-05-31)

- STAGE 4 v3.51과 동일 (5스테이지 공통 적용). 상세·검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.51 및 SECURITY.md #5 참조.
- 수정 파일: `library/preprocessor.php`, `library/auth.php`, `framework/legacy/Textcube.Control.Session.php`·`Session.Memcached.php`·`Openid.php`, `interface/blog/comment/{comment,add}/index.php`

## v2.49 — StatGraph jpgraph(QPL) → SVG 그래프 대체 (라이선스, 2026-05-31)

- STAGE 4 v3.52와 동일 (5스테이지 공통 적용). 상세·검증 내용은 STAGE 4 `changelog8.4to8.5.md` v3.52 및 SECURITY.md #3 참조.
- 수정/제거: `plugins/StatGraph/index.php`(SVG 재구현), `plugins/StatGraph/count/`(jpgraph QPL 제거)

## v2.50 — OpenID 2.0 (EOL) 제거 → OIDC 재구현 (이중 옵트인, 2026-05-31)

- STAGE 4 v3.53과 동일 (5스테이지 공통 적용). 상세·검증·테스트 내용은 STAGE 4 `changelog8.4to8.5.md` v3.53 및 SECURITY.md #4 참조.
- OIDC 재구현(이중 옵트인, 의존 없는 자체 구현 `Textcube.Control.OIDC.php`) + OpenID 2.0/phpopenid(53파일) 완전 제거. `Openid.php` 헬퍼 축소(static화), `login/openid`(+`callback`)·`account/openid`·`setting/openid`·CL_OpenID 일원화.

---

---

<sub>Modifications documented herein by @deokio (2026), performed with AI assistance (Anthropic Claude) under human review.
No additional copyright is asserted. Licensed under GPL (same as the rest of the project).</sub>
