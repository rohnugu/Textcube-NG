Textcube-NG: Brand Yourself - Personalized web publishing platform
==================================================================

**Version**: `1.10.10+php85.r2`

> ## ⚠️ Security Notice — Read Before Deployment
>
> This fork brings Textcube 1.10.10 to PHP 8.5 compatibility and addresses
> several security issues, but **one unresolved HIGH-severity item remains**
> (unsalted MD5 passwords). See [SECURITY.md](./documents/SECURITY.md) for the complete list. Notably:
>
> - **Passwords are stored as unsalted MD5** (legacy from upstream; intentionally retained for
>   drop-in compatibility with existing Textcube data — naive migration breaks logins for existing users)
> - Internal queries use string-escaped raw SQL (not prepared statements).
>   SQL injection defense has been **verified by adversarial testing** (no
>   actual vulnerability found); full prepared-statement migration remains a
>   best-practice item, not a vulnerability — see SECURITY.md
>
> **Not recommended for public-facing production deployment** without
> additional hardening. Suitable for: local/internal use, archival access
> to legacy Textcube blogs, development environments, and contributors who
> can review and patch remaining issues.
>
> ## ℹ️ Project Status and Provenance
>
> This is an **unofficial community fork** of [Textcube](https://github.com/Needlworks/Textcube),
> originally developed by Needlworks / Tatter Network Foundation (TNF) under GPL.
> This fork is **not affiliated with or endorsed by Needlworks/TNF**.
>
> The PHP 7.4 → 8.5 port consists **predominantly of mechanical migrations**
> following PHP upgrade documentation (PHP4 constructor removal, dynamic
> property declarations, mysqli OO migration, deprecated function replacement,
> etc.), and was **performed with the assistance of an AI coding assistant
> (Anthropic Claude) under human review**.
>
> Because the bulk of changes are deterministic transformations dictated by
> PHP migration guides, the maintainer **asserts no additional copyright over
> these modifications**. The original Needlworks/TNF copyright on the
> underlying codebase is preserved in all source file headers as required by GPL.
>
> See [NOTICE](./NOTICE), [CHANGELOG.md](./documents/CHANGELOG.md), and the staged
> changelog files for the full modification history.

## DESCRIPTION

Textcube is an opensource tool to archive and share the experiences, ideas, opinions and thoughts.

Supports import/export individual data via XML compatible with other solutions in 'Tattertools Project'

* Strong support of non-latin compatibility including Korean/Japanese/Chinese
* Supports various installation environments (webservers,databases and languages)
* Provides and extensible plugin and skin architecture
* Expandable from individual blog to blog service platform.
* Supports easy backup and restore via TTXML format, which is supported by various platforms of 'Project Tattertools.'

## HISTORY

Textcube is based on online publishing platform 'Tattertools,' started by JH in 2004, developed by TNC in 2005 and GPLized in 2006. Tatter Network Foundation (TNF) developed Tattertools with TNC from Apr. 2006. Needlworks/TNF was dedicated to Tattertools' development from Nov. 2006, and started developing 'Project S2' as its successor. 'Textcube' was named by YJ Park, and made its debut in Aug. 2007.

## Textcube-NG

Textcube-NG is a community-maintained continuation of Textcube 1.10.10, porting the codebase
from PHP 5.6 (the last version supported by upstream) to PHP 8.5.

### PHP Version Compatibility

The primary target is **PHP 8.5**, but the same codebase also runs on PHP 8.4 —
both have been verified with the full test suite. Separate release packages are provided for
each supported version:

| Release package | PHP target | Test result |
|-----------------|------------|-------------|
| `php7.4-Textcube-1.10.10` | PHP 7.4 | 44/44 PASS |
| `php8.2-Textcube-1.10.10` | PHP 8.2 | 33/33 PASS |
| `php8.4-Textcube-1.10.10` | PHP 8.4 | 33/33 PASS |
| `Textcube-NG-1.10.10+php85.r2` *(this release)* | PHP 8.5 | 33/33 PASS |

The PHP 8.4 and PHP 8.5 packages share the same codebase — the PHP 8.5 target required
no additional PHP code changes beyond the PHP 8.4 stage.
If you are on PHP 8.4, the PHP 8.5 package runs without modification.

### Key Changes from Upstream Textcube 1.10.10

Compared to the upstream release (which last ran on PHP 5.6), Textcube-NG includes:

- **PHP 8.5 compliance**: All deprecated/removed PHP syntax fixed across the entire codebase —
  PHP4-style constructors, dynamic property declarations, `${}` string interpolation, `strftime()`,
  `gmmktime()`, brace array access, mysqli procedural API, and more.
- **phpmailer 5.x → 6.9.x**: Replaced the bundled phpmailer (incompatible with PHP 8.0+) with
  phpmailer 6.9.x. A `legacy_shim.php` preserves the existing call interface — no plugin changes needed.
- **Security fixes**: Privilege guard added to server config and user management endpoints;
  all `rand()` replaced with `random_int()` (CSPRNG); timing-safe token comparison via `hash_equals()`;
  prepared statements for key external-input entry points.
- **memcache PHP 8.5 support**: The official pecl/memcache does not build on PHP 8.5; the
  [websupport-sk fork](https://github.com/websupport-sk/pecl-memcache) is required and confirmed working.

### Drop-in Compatibility

Existing Textcube installations can migrate to Textcube-NG by replacing the PHP files.
Your existing database, `config.php`, skins, plugins, and uploaded attachments carry over without
modification. The database schema is unchanged from upstream.

See [CHANGELOG.md](./documents/CHANGELOG.md) and the staged changelog files for the
full modification history.

## Security Status (Summary)

| Severity | Open | Notes |
|----------|------|-------|
| HIGH     | 1    | MD5 password hashing (intentionally retained) |
| MEDIUM   | 0    | jpgraph QPL & OpenID 2.0 EOL — both resolved |
| LOW      | 1    | Outdated static assets (audited; upgrade deferred) |

25 issues resolved across all severity levels. See [SECURITY.md](./documents/SECURITY.md) for full details.

Notable open items:
- MD5 password hashing (no salt) — intentionally retained for drop-in hosting compatibility; migration requires careful strategy (e.g., on-login rehashing) to avoid breaking existing user logins
- Outdated static assets (jQuery 1.11.2, Lodash 2.4.1, TinyMCE 4.1.10, etc.) — audited (inventory + CVEs documented); upgrade deferred due to behavior-change risk (notably the customized TinyMCE plugins TTMLsupport/codemirror). See SECURITY.md #6.

Resolved in this release (r2):
- **OpenID 2.0 (EOL) → OpenID Connect (OIDC)** reimplementation with double opt-in (disabled by default); legacy phpopenid (265 files) removed
- **jpgraph (QPL)** → dependency-free inline SVG chart
- **Raw SQL SQLi defense** verified by adversarial testing — reclassified from HIGH (prepared-statement full migration remains a best-practice item, not a vulnerability)

## REQUIREMENTS (CURRENT VERSION — PHP 8.5 Port)

Textcube supports various environments. You need at least one webserver supporting PHP and one database engine.

* Web servers (Need at least one environment)
  * Apache 2.4 or above
    * fancyURL support with mod_rewrite module
  * Nginx 1.1 or above
  * IIS 7.0 or above
    * with URL Rewrite Module 2.0
* Language
  * **PHP 8.5**
    * Required extensions: iconv, GD, mbstring, json, mysqli
    * (All typically bundled with standard PHP 8.5 distributions)
* Database Management System (Need at least one environment)
  * **MySQL 5.7.6+ or 8.0+** with UTF-8 character set and collation setting
  * MariaDB 10.2 or above
  * SQLite 3 (limited feature set; not recommended for production)
  * *(Cubrid, PostgreSQL: not tested with PHP 8.5 port)*

For massive service / Heavy load environments

* **OPcache** — built-in since PHP 5.5, replaces APC/XCache (both removed/abandoned)
* **pecl/memcache** ([websupport-sk fork](https://github.com/websupport-sk/pecl-memcache), PHP 8.5 compatible) with Memcached server — for session storage and query cache
  * The official pecl/memcache 8.2 does not build on PHP 8.5; use the websupport-sk fork (build from source or install via OS package manager on Debian/Ubuntu).
  * Enable in `config.php`: `$service['memcached'] = true;`
  * Configure server: `$memcached['server'] = 'hostname';` / `$memcached['port'] = 11211;`

> **NAT / port-forwarding note**: When `$service['memcached'] = true` on a host behind NAT or
> port-forwarding (external port differs from internal port), you **must** set `$serviceURL` in
> `config.php` to the *external* URL (including the external port). Cached skin fragments and feeds
> bake the service URL at write time and are shared across requests via memcached; without this,
> the internal hostname/port can leak into responses served to clients on the external URL.

are strongly recommended.

### Tested Environments

The following combinations have been verified via `tc_full_test.sh`:

| PHP    | DB           | Web Server      | Tests      | Status   |
|--------|--------------|-----------------|------------|----------|
| 7.4.x  | MySQL 5.7    | Apache 2.4      | 44/44 PASS | Verified |
| 8.2.x  | MySQL 5.7    | Apache 2.4      | 33/33 PASS | Verified |
| 8.4.x  | MySQL 5.7    | Apache 2.4      | 33/33 PASS | Verified |
| 8.5.x  | MySQL 5.7    | Apache 2.4      | 33/33 PASS | Verified |

**Not yet tested with this port**: PostgreSQL, Cubrid, SQLite 3.x, MariaDB,
IIS, Nginx (configuration documented but untested by current maintainer).

### Test Coverage

**Functional** (`tc_full_test.sh`): setup, authentication, basic CRUD for
entries/comments/categories, attachment upload, TTXML import/export, RSS/Atom
feeds, skin rendering, basic plugin loading.

**Security — adversarial** (`_sectest/`): SQL injection (incl. **trackback
receive**, entry search, user suggest), XSS (reflected / stored / HTML-attribute),
path traversal / LFI, CSRF (`requireStrictRoute` path-mode), the OIDC engine
(opt-in gates, JWT/JWKS verification, identity mapping), and the **XMLRPC parser**
(XXE / external-DTD / billion-laughs). These are adversarial unit/integration
harnesses that trace generated SQL/output/parser behavior for malicious input.

**Not covered**: OIDC live-provider end-to-end flow (the engine is unit-tested
above; integration with a real provider is manual), trackback **send** (outbound
request), **XMLRPC handler authorization paths** (the parser itself is XXE/DoS-tested
above), individual plugin functionality beyond load, large-scale performance,
concurrent multi-user scenarios.

## REQUIREMENTS (OLD VERSIONS)

* Web servers (Need at least one environment)
  * Apache 1.3 or above
    * fancyURL support with mod_rewrite module
* Language
  * (Textcube 1.8–1.10) PHP 5.2–5.6
    * with iconv / gd module
    * APC or XCache recommended for performance
  * (Till Textcube 1.7) PHP 4.3–5.1
    * with iconv / gd module
* Database Management System (Need at least one environment)
  * (Textcube 1.8–1.10) MySQL 5.0+ / MariaDB 5.1+ with UTF-8 character set
  * (Till Textcube 1.7) MySQL 4.1+ / MariaDB 5+ (lower version with UTF-8 emulation routine in Textcube)

## INSTALLATION

Before you start, you need to

* know the port / username / password of your database
* have the permission to modify webserver configuration.

Uncompress the downloaded file, locate them to the web-accessible location. Assume that the textcube location is /var/www/textcube.

This is apache setting.

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

This is nginx setting.

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

If the accessible URL is http://www.example.org, run the installation program by accessing http://www.example.org/setup.php. Follow the setup procedure.

* [Installation (Korean)](http://help.tattertools.com/ko/index.php?title=Getting_Started)

## RUNNING

## DOCUMENTATION

### USERS
* [Shortcuts](https://github.com/Needlworks/Textcube/wiki/shortCutList)


### SPECIFICATIONS AND STRUCTURES

* [Requirements](https://github.com/Needlworks/Textcube/wiki/requirements)

* [Upgrade instruction from 1.7 to 1.8 or higher version](https://github.com/Needlworks/Textcube/wiki/attentionOnInstallation)
* [Configurable options](https://github.com/Needlworks/Textcube/wiki/configOptions)

* [Skin](https://github.com/Needlworks/Textcube/wiki/SkinDocs)
* [Skin replacer list](https://github.com/Needlworks/Textcube/wiki/replacer)
* [Predefined styles](https://github.com/Needlworks/Textcube/wiki/skinpredefined)
* [Skin information file specification](https://github.com/Needlworks/Textcube/wiki/skin/index_xml)
* [Tattertools/Textcube online manual wiki](http://help.tattertools.com)

* [Plugins](https://github.com/Needlworks/Textcube/wiki/PluginDocs)
* [Plugin Creation](https://github.com/Needlworks/Textcube/wiki/PluginIntroduction)
* [Plugin Specification](https://github.com/Needlworks/Textcube/wiki/pluginSpec)
* [Plugin Event Listeners](https://github.com/Needlworks/Textcube/wiki/pluginEvents)

* [TTXML format specification](https://github.com/Needlworks/Textcube/wiki/TTXML)
* [WPI package creation](https://github.com/Needlworks/Textcube/wiki/WPI)

### DEVELOPMENT
* [Ticketing process](https://github.com/Needlworks/Textcube/wiki/ticketProcess)
* [Coding guideline](https://github.com/Needlworks/Textcube/wiki/codingGuideline)
* [Commiter/Reporter List](https://github.com/Needlworks/Textcube/wiki/contributorList)

* [Developing references](https://github.com/Needlworks/Textcube/wiki/devReference)
* [Textcube 1.8 changes for Plugin Developers](http://docs.google.com/View?id=dgc24tzr_136ckbg4ngn)
* [Textcube 1.8 changes for Skin Designers](http://docs.google.com/View?id=dgc24tzr_138hhfbmwdg)
* [Textcube 1.8 changes for Server administrators and service hosts / maintainers](http://docs.google.com/View?id=dgc24tzr_137gr9xpdfb)
* [Textcube 1.8 changes for coding geeks](http://docs.google.com/View?id=dgc24tzr_140c9wz6nc5)

## EXTERNAL LINKS

* [Textcube notice blog](http://notice.textcube.org/ko)
* [Needlworks](http://www.needlworks.org)
* [Needlworks Blog](http://blog.needlworks.org)
* [Tatter Network Foundation forum](http://forum.tattersite.com/ko)

---

<sub>Modifications by @deokio (2026), performed with AI assistance (Anthropic Claude) under human review.
No additional copyright is asserted. Licensed under GPL (same as the rest of the project).</sub>
