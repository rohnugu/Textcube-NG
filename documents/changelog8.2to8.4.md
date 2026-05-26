# CHANGELOG — Textcube 1.10.10 PHP 8.2 → PHP 8.4 이식 (STAGE 3)

대상 PHP: 8.4  
기반 버전: php8.2-Textcube-1.10.10 (STAGE 2, v2.00)  
작성일: 2026-05-16 / 최종 수정: 2026-05-16

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

## v2.30 — STAGE 3 종료

- 회귀 테스트 결과 SECURITY.md 기록.
- SECURITY.md 모든 항목 현황 최종 재평가.
- README.md PHP 8.4 명시 최종 확인.
- `release-php8.4.zip` 산출.

---

<sub>Modifications documented herein by @deokio (2026), performed with AI assistance (Anthropic Claude) under human review.
No additional copyright is asserted. Licensed under GPL (same as the rest of the project).</sub>
