<!-- Korean translation of README.md -->
Textcube-NG: Brand Yourself - 개인화된 웹 퍼블리싱 플랫폼
==========================================================

**버전**: `1.10.10+php85.r1`

> ## ⚠️ 보안 공지 — 배포 전 반드시 확인하세요
>
> 이 fork는 Textcube 1.10.10을 PHP 8.5 호환 버전으로 이식하고
> 여러 보안 문제를 해결했지만, **미해결 HIGH 심각도 항목이 남아 있습니다**.
> 전체 목록은 [SECURITY.md](./documents/SECURITY.md)를 참조하세요. 주요 항목:
>
> - **비밀번호가 Salt 없는 MD5로 저장됨** (업스트림 레거시; 기존 호스팅 환경과의 단순 이식 호환성을 위해 의도적으로 유지 — 단순 마이그레이션 적용 시 기존 사용자 로그인 불가 우려)
> - **수백 개의 내부 쿼리가 여전히 문자열 이스케이프 방식의 raw SQL을 사용** (핵심 외부 진입점은 prepared statement로 변환 완료; SECURITY.md에서 추적 중)
>
> **추가 보안 강화 없이는 공개 운영 환경 배포를 권장하지 않습니다.**
> 적합한 사용 환경: 로컬/내부 사용, 기존 Textcube 블로그 아카이브 접근,
> 개발 환경, 잔여 문제를 검토·패치할 수 있는 기여자.
>
> ## ℹ️ 프로젝트 현황 및 출처
>
> 이 프로젝트는 GPL 라이선스 하에 Needlworks / Tatter Network Foundation (TNF)이
> 개발한 [Textcube](https://github.com/Needlworks/Textcube)의
> **비공식 커뮤니티 fork**입니다.
> 이 fork는 **Needlworks/TNF와 제휴 관계에 있지 않으며, 공식 승인을 받은 것도 아닙니다**.
>
> PHP 7.4 → 8.5 이식 작업은 **주로 PHP 업그레이드 문서를 따른 기계적 변환**
> (PHP4 생성자 제거, 동적 프로퍼티 선언, mysqli OO 이전, deprecated 함수 대체 등)으로 이루어졌으며,
> **AI 코딩 어시스턴트(Anthropic Claude)의 도움을 받아 인간의 검토 하에 수행**되었습니다.
>
> 변경 사항의 대부분이 PHP 마이그레이션 가이드에 따른 결정론적 변환이므로,
> 관리자는 **이러한 수정에 대해 추가적인 저작권을 주장하지 않습니다**.
> 원본 Needlworks/TNF 저작권은 GPL 요건에 따라 모든 소스 파일 헤더에 보존되어 있습니다.
>
> 전체 변경 이력은 [NOTICE](./NOTICE), [CHANGELOG.md](./documents/CHANGELOG.md) 및
> 단계별 changelog 파일을 참조하세요.

## 설명

Textcube는 경험, 아이디어, 의견, 생각을 기록하고 공유하기 위한 오픈소스 도구입니다.

'Tattertools Project'의 다른 솔루션과 호환되는 XML 형식(TTXML)으로 개인 데이터를 가져오거나 내보낼 수 있습니다.

* 한국어/일본어/중국어를 포함한 비 라틴 문자 강력 지원
* 다양한 설치 환경(웹서버, 데이터베이스, 언어) 지원
* 확장 가능한 플러그인 및 스킨 아키텍처 제공
* 개인 블로그부터 블로그 서비스 플랫폼까지 확장 가능
* 'Project Tattertools'의 다양한 플랫폼에서 지원하는 TTXML 형식으로 손쉬운 백업 및 복원 지원

## 역사

Textcube는 2004년 JH가 시작한 온라인 퍼블리싱 플랫폼 'Tattertools'를 기반으로 합니다. 2005년 TNC가 개발을 맡았고, 2006년에 GPL 라이선스로 공개되었습니다. Tatter Network Foundation (TNF)은 2006년 4월부터 TNC와 함께 Tattertools를 개발했습니다. Needlworks/TNF는 2006년 11월부터 Tattertools 개발에 전념하며 후속 프로젝트로 'Project S2'를 시작했습니다. 'Textcube'라는 이름은 YJ Park이 지었으며, 2007년 8월에 처음 출시되었습니다.

## Textcube-NG

Textcube-NG는 업스트림이 마지막으로 지원한 PHP 5.6에서 PHP 8.5로
코드베이스를 이식한, Textcube 1.10.10의 커뮤니티 유지 관리 후속 프로젝트입니다.

### PHP 버전 호환성

주 대상 버전은 **PHP 8.5**이지만, 동일 코드베이스가 PHP 8.4에서도 정상 동작합니다.
두 버전 모두 전체 테스트 스위트로 검증되었습니다. 각 지원 버전별 별도 릴리즈 패키지가 제공됩니다:

| 릴리즈 패키지 | PHP 대상 | 테스트 결과 |
|---------------|----------|-------------|
| `php7.4-Textcube-1.10.10` | PHP 7.4 | 44/44 PASS |
| `php8.2-Textcube-1.10.10` | PHP 8.2 | 33/33 PASS |
| `php8.4-Textcube-1.10.10` | PHP 8.4 | 33/33 PASS |
| `Textcube-NG-1.10.10+php85.r1` *(이번 릴리즈)* | PHP 8.5 | 33/33 PASS |

PHP 8.4와 PHP 8.5 패키지는 동일한 코드베이스를 사용합니다 —
PHP 8.5 대상에서는 PHP 8.4 단계 이후 추가 PHP 코드 변경이 필요하지 않았습니다.
PHP 8.4 환경에서는 PHP 8.5 패키지를 수정 없이 그대로 사용할 수 있습니다.

### 업스트림 Textcube 1.10.10과의 주요 변경 사항

업스트림 릴리즈(PHP 5.6까지만 동작)와 비교하여 Textcube-NG에 포함된 변경 사항:

- **PHP 8.5 완전 호환**: 전체 코드베이스에 걸쳐 deprecated/제거된 PHP 문법 전면 수정 —
  PHP4 방식 생성자, 동적 프로퍼티 선언, `${}` 문자열 보간, `strftime()`,
  `gmmktime()`, 중괄호 배열 접근, mysqli 절차형 API 등.
- **phpmailer 5.x → 6.9.x 교체**: PHP 8.0+에서 동작하지 않는 번들 phpmailer를
  6.9.x로 교체. `legacy_shim.php`를 통해 기존 호출 인터페이스가 유지되므로 플러그인 변경 불필요.
- **보안 수정**: 서버 설정 및 사용자 관리 엔드포인트에 권한 가드 추가;
  전체 `rand()` → `random_int()` (CSPRNG) 교체; `hash_equals()`를 통한 타이밍 안전 토큰 비교;
  핵심 외부 입력 진입점에 prepared statement 적용.
- **memcache PHP 8.5 지원**: 공식 pecl/memcache는 PHP 8.5에서 빌드 불가;
  [websupport-sk fork](https://github.com/websupport-sk/pecl-memcache) 사용 필요 (동작 확인 완료).

### Drop-in 호환성

기존 Textcube 설치 환경에서 PHP 파일만 교체하는 방식으로 Textcube-NG로 이전할 수 있습니다.
기존 데이터베이스, `config.php`, 스킨, 플러그인, 업로드된 첨부파일은 수정 없이 그대로 사용 가능합니다.
데이터베이스 스키마는 업스트림과 동일합니다.

전체 변경 이력은 [CHANGELOG.md](./documents/CHANGELOG.md) 및 단계별 changelog 파일을 참조하세요.

## 보안 현황 (요약)

| 심각도   | 미해결 | 비고 |
|----------|--------|------|
| HIGH     | 2      | MD5 비밀번호 해싱; 내부 쿼리 raw SQL |
| MEDIUM   | 2      | jpgraph QPL 라이선스; OpenID 2.0 EOL |
| LOW      | 2      | 쿠키 속성 누락; 오래된 정적 자산 |

전체 심각도 합산 22건 해결 완료. 전체 내용은 [SECURITY.md](./documents/SECURITY.md) 참조.

주요 미해결 항목:
- MD5 비밀번호 해싱 (Salt 없음) — 기존 호스팅 이식 호환성을 위해 의도적으로 유지; 기존 사용자 로그인 불가 방지를 위한 신중한 마이그레이션 전략(예: 로그인 시 점진적 재해싱) 필요
- 내부 raw SQL 쿼리 — 핵심 진입점 변환 완료; 일괄 이전 예정
- 레거시 의존성 (phpopenid, phpxpath, jpgraph) — CVE 검토 미완료

## 요구 사항 (현재 버전 — PHP 8.5 이식본)

Textcube는 다양한 환경을 지원합니다. PHP를 지원하는 웹서버와 데이터베이스 엔진이 각각 하나 이상 필요합니다.

* 웹서버 (하나 이상의 환경 필요)
  * Apache 2.4 이상
    * mod_rewrite 모듈을 통한 fancyURL 지원
  * Nginx 1.1 이상
  * IIS 7.0 이상
    * URL Rewrite Module 2.0 포함
* 언어
  * **PHP 8.5**
    * 필수 확장: iconv, GD, mbstring, json, mysqli
    * (일반적으로 표준 PHP 8.5 배포판에 모두 포함됨)
* 데이터베이스 관리 시스템 (하나 이상의 환경 필요)
  * **MySQL 5.7.6+ 또는 8.0+** (UTF-8 문자셋 및 정렬 설정 필요)
  * MariaDB 10.2 이상
  * SQLite 3 (기능 제한; 운영 환경 비권장)
  * *(Cubrid, PostgreSQL: PHP 8.5 이식본으로 미검증)*

대규모 서비스 / 고부하 환경을 위한 권장 사항:

* **OPcache** — PHP 5.5부터 내장. APC/XCache를 대체합니다 (두 확장 모두 제거/개발 중단).
* **pecl/memcache** ([websupport-sk fork](https://github.com/websupport-sk/pecl-memcache), PHP 8.5 호환) + Memcached 서버 — 세션 저장 및 쿼리 캐시 용도
  * 공식 pecl/memcache 8.2는 PHP 8.5에서 빌드 불가; websupport-sk fork 사용 (소스 빌드 또는 Debian/Ubuntu 패키지 매니저로 설치).
  * `config.php`에서 활성화: `$service['memcached'] = true;`
  * 서버 설정: `$memcached['server'] = 'hostname';` / `$memcached['port'] = 11211;`

> **NAT / 포트포워딩 환경 주의**: NAT 또는 포트포워딩 환경(외부 포트와 내부 포트가 다른 경우)에서
> `$service['memcached'] = true`를 사용할 때는, `config.php`의 `$serviceURL`을
> *외부* URL(외부 포트 포함)로 반드시 설정해야 합니다. 캐시된 스킨 조각과 피드는
> 쓰기 시점에 서비스 URL을 고정하여 memcached를 통해 요청 간 공유됩니다;
> 이 설정 없이는 내부 호스트명/포트가 외부 URL을 통해 서비스되는 응답에 노출될 수 있습니다.

### 검증된 환경

`tc_full_test.sh`를 통해 다음 조합이 검증되었습니다:

| PHP    | DB           | 웹서버          | 테스트     | 상태     |
|--------|--------------|-----------------|------------|----------|
| 7.4.x  | MySQL 5.7    | Apache 2.4      | 44/44 PASS | 검증됨   |
| 8.2.x  | MySQL 5.7    | Apache 2.4      | 33/33 PASS | 검증됨   |
| 8.4.x  | MySQL 5.7    | Apache 2.4      | 33/33 PASS | 검증됨   |
| 8.5.x  | MySQL 5.7    | Apache 2.4      | 33/33 PASS | 검증됨   |

**이 이식본으로 미검증**: PostgreSQL, Cubrid, SQLite 3.x, MariaDB,
IIS, Nginx (설정 문서는 있으나 현재 관리자가 직접 검증하지 않음).

### 테스트 범위

`tc_full_test.sh`는 설치, 인증, 글·댓글·카테고리 기본 CRUD, 첨부파일 업로드,
TTXML 가져오기/내보내기, RSS/Atom 피드, 스킨 렌더링, 기본 플러그인 로딩을 다룹니다.

**자동화 테스트 미포함**: OpenID 흐름, 트랙백 송수신, XMLRPC API,
로드 이상의 개별 플러그인 기능, 대규모 성능, 동시 다중 사용자 시나리오.

## 요구 사항 (구버전)

* 웹서버 (하나 이상의 환경 필요)
  * Apache 1.3 이상
    * mod_rewrite 모듈을 통한 fancyURL 지원
* 언어
  * (Textcube 1.8–1.10) PHP 5.2–5.6
    * iconv / gd 모듈 필요
    * 성능을 위해 APC 또는 XCache 권장
  * (Textcube 1.7 이하) PHP 4.3–5.1
    * iconv / gd 모듈 필요
* 데이터베이스 관리 시스템 (하나 이상의 환경 필요)
  * (Textcube 1.8–1.10) MySQL 5.0+ / MariaDB 5.1+ (UTF-8 문자셋)
  * (Textcube 1.7 이하) MySQL 4.1+ / MariaDB 5+ (낮은 버전은 Textcube 내 UTF-8 에뮬레이션 루틴 사용)

## 설치

시작 전 다음 사항을 확인하세요:

* 데이터베이스의 포트 / 사용자명 / 비밀번호
* 웹서버 설정 수정 권한

다운로드한 파일을 압축 해제하고 웹 접근 가능한 위치에 배치합니다. Textcube 위치를 /var/www/textcube로 가정합니다.

Apache 설정 예시:

    <VirtualHost *:80>
        ServerName www.example.org
        ServerAlias www.example.org
        ServerAdmin admin@example.org
        DocumentRoot /var/www/textcube/
        <Directory /var/www/textcube>
            AllowOverride FileInfo
            Order allow,deny
            allow from all
        </Directory>
    </VirtualHost>

Nginx 설정 예시:

    server {
       listen  80;
       server_name example.org *.example.org;
       root    /var/www/textcube;

       location /  {
           root    /var/www/textcube;
           set $rewrite_base '';
           if (!-f $request_filename) {
               rewrite ^(thumbnail)/([0-9]+/.+)$ cache/$1/$2;
           }
           if ($request_filename ~* ^(cache)+/+(.+[^/])\.(cache|xml|txt|log)$) {
               return 403;
           }
           if (-d $request_filename) {
               rewrite ^(.+[^/])$ $1/;
           }
           rewrite  ^(.*)$ $rewrite_base/rewrite.php last;
       }

       location ~ \.php$ {
           fastcgi_pass   127.0.0.1:9000;
           fastcgi_index  index.php;
           fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
           fastcgi_param  QUERY_STRING     $query_string;
           fastcgi_param  REQUEST_METHOD   $request_method;
           fastcgi_param  CONTENT_TYPE     $content_type;
           fastcgi_param  CONTENT_LENGTH   $content_length;
           include fastcgi_params;
       }
       location ~ /\.ht {
           deny all;
       }
    }

접근 URL이 http://www.example.org 라면, http://www.example.org/setup.php에 접속하여 설치 프로그램을 실행합니다. 설치 절차를 따르세요.

* [설치 가이드 (한국어)](http://help.tattertools.com/ko/index.php?title=Getting_Started)

## 실행

## 문서

### 사용자
* [단축키 목록](https://github.com/Needlworks/Textcube/wiki/shortCutList)

### 사양 및 구조

* [요구 사항](https://github.com/Needlworks/Textcube/wiki/requirements)
* [1.7에서 1.8 이상 버전으로의 업그레이드 안내](https://github.com/Needlworks/Textcube/wiki/attentionOnInstallation)
* [설정 옵션](https://github.com/Needlworks/Textcube/wiki/configOptions)
* [스킨](https://github.com/Needlworks/Textcube/wiki/SkinDocs)
* [스킨 치환자 목록](https://github.com/Needlworks/Textcube/wiki/replacer)
* [사전 정의 스타일](https://github.com/Needlworks/Textcube/wiki/skinpredefined)
* [스킨 정보 파일 사양](https://github.com/Needlworks/Textcube/wiki/skin/index_xml)
* [Tattertools/Textcube 온라인 매뉴얼 위키](http://help.tattertools.com)
* [플러그인](https://github.com/Needlworks/Textcube/wiki/PluginDocs)
* [플러그인 제작](https://github.com/Needlworks/Textcube/wiki/PluginIntroduction)
* [플러그인 사양](https://github.com/Needlworks/Textcube/wiki/pluginSpec)
* [플러그인 이벤트 리스너](https://github.com/Needlworks/Textcube/wiki/pluginEvents)
* [TTXML 형식 사양](https://github.com/Needlworks/Textcube/wiki/TTXML)
* [WPI 패키지 생성](https://github.com/Needlworks/Textcube/wiki/WPI)

### 개발
* [티켓팅 프로세스](https://github.com/Needlworks/Textcube/wiki/ticketProcess)
* [코딩 가이드라인](https://github.com/Needlworks/Textcube/wiki/codingGuideline)
* [기여자/보고자 목록](https://github.com/Needlworks/Textcube/wiki/contributorList)
* [개발 참고 자료](https://github.com/Needlworks/Textcube/wiki/devReference)
* [플러그인 개발자를 위한 Textcube 1.8 변경 사항](http://docs.google.com/View?id=dgc24tzr_136ckbg4ngn)
* [스킨 디자이너를 위한 Textcube 1.8 변경 사항](http://docs.google.com/View?id=dgc24tzr_138hhfbmwdg)
* [서버 관리자 및 서비스 호스트/관리자를 위한 Textcube 1.8 변경 사항](http://docs.google.com/View?id=dgc24tzr_137gr9xpdfb)
* [개발자를 위한 Textcube 1.8 변경 사항](http://docs.google.com/View?id=dgc24tzr_140c9wz6nc5)

## 외부 링크

* [Textcube 공지 블로그](http://notice.textcube.org/ko)
* [Needlworks](http://www.needlworks.org)
* [Needlworks 블로그](http://blog.needlworks.org)
* [Tatter Network Foundation 포럼](http://forum.tattersite.com/ko)

---

<sub>@deokio의 수정 작업 (2026), AI 어시스턴트(Anthropic Claude)의 도움을 받아 인간의 검토 하에 수행됨.
추가적인 저작권을 주장하지 않습니다. GPL 라이선스 적용 (프로젝트 전체와 동일).</sub>
