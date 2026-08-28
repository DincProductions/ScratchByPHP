<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="docs/assets/brand/scratchbyphp-logo-full-dark.png">
    <img src="docs/assets/brand/scratchbyphp-logo-full-light.png" alt="ScratchByPHP" width="760">
  </picture>
</p>

<p align="center">
  <strong>A powerful bridge between PHP and Scratch.</strong><br>
  Projects, users, studios, authenticated sessions, Cloud Variables, CloudRequests, CloudDatabase, analysis, watchers, SB3 tools, CLI and an embeddable Wizard Pro in one PHP package.
</p>

<p align="center">
  <a href="https://www.blocklandin.com/scratchbyphp/"><img alt="Website" src="https://img.shields.io/badge/Website-ScratchByPHP-ff9f1c?style=flat"></a>
  <a href="https://www.blocklandin.com/scratchbyphp/docs"><img alt="Documentation" src="https://img.shields.io/badge/Docs-Türkçe%20%2B%20English-2f80ed?style=flat"></a>
  <a href="https://github.com/scratchbyphp/scratchbyphp/actions"><img alt="CI" src="https://img.shields.io/badge/CI-GitHub%20Actions-2088FF?style=flat&logo=githubactions&logoColor=white"></a>
  <img alt="Stable Version" src="https://img.shields.io/badge/stable-v0.8.5-2ea44f?style=flat">
  <a href="https://www.php.net/"><img alt="PHP 8.1+" src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat&logo=php&logoColor=white"></a>
  <a href="LICENSE"><img alt="MIT License" src="https://img.shields.io/badge/license-MIT-2ea44f?style=flat"></a>
</p>

<p align="center">
  <a href="README.md">Türkçe</a> · <strong>English</strong> ·
  <a href="https://www.blocklandin.com/scratchbyphp/">Website</a> ·
  <a href="https://www.blocklandin.com/scratchbyphp/docs">Documentation</a> ·
  <a href="https://github.com/scratchbyphp/scratchbyphp">GitHub</a>
</p>

---

# ScratchByPHP v0.8.5

**Current stable release:** `v0.8.5`  
**Minimum PHP:** `8.1`  
**License:** MIT

ScratchByPHP is a reusable SDK/toolkit layer between PHP websites, backends, dashboards, CLI/worker applications and the Scratch ecosystem. Instead of rebuilding endpoint handling, authenticated cookies/tokens, WebSocket logic, retries, caching and response parsing for every project, applications can work through higher-level PHP objects.

```bash
composer require scratchbyphp/scratchbyphp
```

```php
require __DIR__ . '/vendor/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();
$project = $scratch->project(104);

echo $project->title();
echo $project->views();
```

> ScratchByPHP is not an official Scratch Foundation SDK. Unofficial/internal Scratch endpoints may change over time.

## Highlights in v0.8.5

- **CloudDB Pro → MySQL:** export Scratch CloudDatabase key/value data using JSON configuration or an existing `mysqli` connection, with prepared statements and transactions.
- **Turkish Studio Trending:** discovers studios with Turkish-name signals (`türk / Türk / TÜRK`), aggregates and deduplicates their projects, then ranks the pool using views, social signals and freshness.
- **ProjectDiff fix:** `toArray()` is canonical; `summary()` is available as a compatibility alias.
- **Wizard Pro:** draggable, resizable, maximizable site-embedded ScratchByPHP control center with public APIs, server-side login, authenticated actions, Cloud, Watcher, Analyzer and developer tools.
- **Watcher 2.0:** fresh polling, event queue, persistent state, jitter/backoff and project change tracking.
- **Reliability/DX:** Cache 2.0, Batch 2.0, Metrics, Retry Policy, Circuit Breaker and Doctor 2.0.
- **Analyzer/SB3:** Project Analyzer 2.0, ProjectDiff, SB3 Archive and Validator.
- **Tooling:** CLI, FakeScratch, PHPUnit/PHPStan scaffolding, generated API references and browser test panels.

## Why ScratchByPHP?

Raw PHP integration often requires repetitive work around HTTP status handling, session cookies, CSRF/X-Token state, response parsing, WebSockets, reconnect behavior, caching, rate limits and maintenance when Scratch behavior changes. ScratchByPHP centralizes those concerns so application code can focus on product logic.

ScratchByPHP does not claim a fixed percentage improvement in development time. The practical advantage is reuse: the same integration and security logic does not need to be rewritten in each PHP project.

---

# Installation

```bash
composer require scratchbyphp/scratchbyphp
```

Requirements:

- PHP `8.1+`
- cURL
- OpenSSL
- JSON
- ZIP for SB3/archive features
- mysqli for live CloudDB Pro → MySQL transfer
- outbound WebSocket/TLS access for Scratch Cloud features

---

# Core API

## Public resources

```php
$project = $scratch->project(104);
$user = $scratch->user('griffpatch');
$studio = $scratch->studio(123456);
```

Project helpers include `get`, `refresh`, `title`, `author`, `views`, `loves`, `favorites`, `comments`, `remixes`, collections/paginators, Analyzer/SB3 and player helpers.

User helpers include profile data, projects, followers/following, favorites, studios, activity, collections/paginators and DTO conversion.

Studio helpers include project/curator/manager/comment reads, `allProjects()` pagination aggregation and authenticated management operations.

## Authentication

```php
$session = $scratch->login(
    getenv('SCRATCH_USERNAME'),
    getenv('SCRATCH_PASSWORD')
);

$project = $session->project(104);
$studio = $session->studio(123456);
$user = $session->user('ExampleUser');
$cloud = $session->cloud(104);
```

Session-ID login:

```php
$session = $scratch->loginWithSessionId(
    getenv('SCRATCH_SESSION_ID')
);
```

Never commit passwords, session IDs, X-Tokens, CSRF values or database credentials.

---

# Search and Turkish Studio Trending

```php
$projects = $scratch->searchProjects('platformer');
$studios = $scratch->searchStudios('türk');
```

Turkish Trending in v0.8.5 uses studio discovery rather than a project-description hashtag filter:

```php
$projects = $scratch->turkishTrending(
    limit: 20,
    scan: 120
);
```

Discovery flow:

1. Search Scratch studios with Turkish-name queries such as `türk`, `Türk`, `TÜRK`.
2. Deduplicate studio IDs and retain Turkish-name candidates.
3. Fetch studio projects with pagination.
4. Deduplicate projects found in multiple studios.
5. Rank the combined project pool.

Default ranking weights:

- views: 35%
- loves: 15%
- favorites: 10%
- shared-date freshness: 40%

Love/favorite counts are supporting signals, not minimum requirements. Output includes `turkish_trend.rank`, `score`, scoring signals and source-studio metadata.

---

# Collections, Pagination, Batch and Cache

```php
$projects = $user->projectsCollection()
    ->filter(fn ($p) => $p->views() > 1000)
    ->sortByDesc(fn ($p) => $p->views())
    ->take(10);
```

```php
$page = $user->projectsPaginator()->limit(20)->page(2)->get();
```

```php
$result = $scratch->batch()
    ->project(104)
    ->project(105)
    ->user('griffpatch')
    ->concurrency(4)
    ->timeout(15)
    ->retries(2)
    ->run();
```

```php
$scratch->cache('file')->cacheRules([
    'project:' => 30,
    'user:' => 120,
    'studio:' => 60,
]);
```

---

# Scratch Cloud

```php
$cloud = $session->cloud(104);
$cloud->connect();

$value = $cloud->getRemote('score');
$result = $cloud->setVerified('score', 500);

$cloud->setMany([
    'score' => 500,
    'level' => 4,
], true);

$values = $cloud->variables();
$history = $cloud->history('score');

$cloud->disconnect();
```

Long-running listeners and RPC loops are best suited to CLI/worker processes.

## CloudRequests

```php
$rpc = $cloud->requests('request', 'response');
$rpc->route('sum', fn (array $params) => array_sum($params));
$rpc->run();
```

## CloudDatabase

```php
$db = $cloud->database('db');
$db->set('level', 12);
$db->increment('coins', 10);
$value = $db->get('level');
```

CloudDatabase is a compact Scratch Cloud state layer, not a general replacement for MySQL/SQLite.

## CloudDB Pro → MySQL

```php
$result = $cloud
    ->database('db')
    ->getToDB(__DIR__ . '/../secure/mysql.json');
```

The bridge uses mysqli, prepared statements, transactions, strict table/column identifier validation, optional upsert and optional table creation.

A dry plan can be created without opening MySQL:

```php
$plan = ScratchByPHP\Cloud\CloudDatabase::planToDB(
    ['level' => 12, 'coins' => 500],
    ['table' => 'scratch_cloud']
);
```

Keep MySQL configuration outside public web roots and out of source control.

---

# Watcher 2.0

```php
$watch = $scratch->watch()->interval(10)->project(104);
$baseline = $watch->baseline();

$watch->onView(fn ($new, $old) => null);
$watch->onComment(fn ($comment) => null);
$watch->onChange(fn ($field, $new, $old) => null);
```

Watcher polling uses fresh project state so the normal model cache does not hide live changes. Comment detection uses the latest comment ID rather than a nonexistent project `stats.comments` value.

---

# Analyzer, ProjectDiff and SB3

```php
$analysis = $scratch->project(104)->analyze();
print_r($analysis->summary());
print_r($analysis->warnings());
print_r($analysis->opcodeCounts());
```

```php
$diff = $scratch->compareProjects(104, 105);
print_r($diff->toArray());
print_r($diff->summary());
```

```php
$validator = new ScratchByPHP\Sb3\Sb3Validator();
$result = $validator->validate(__DIR__.'/project.sb3');
```

---

# Wizard Pro

```php
$scratch = new Scratch();

$wizard = $scratch->wizard([
    'allow_auth' => true,
    'allow_writes' => true,
    'clouddb_profiles' => [
        'main' => __DIR__.'/../secure/mysql.json',
    ],
    'cloud_request_handlers' => [
        'sum' => fn (array $params) => array_sum($params),
    ],
]);

// Before any HTML output:
$wizard->handle();
```

Then render it anywhere in the page:

```php
<?= $wizard->render([
    'title' => 'ScratchByPHP Control Center',
    'width' => 980,
    'height' => 680,
]) ?>
```

Wizard Pro is draggable, resizable and maximizable. It exposes project/user/studio/search tools, server-side login, authenticated actions, Cloud Variables, CloudDatabase/CloudRequests, Watcher, Analyzer/ProjectDiff and diagnostics, while producing contextual PHP snippets.

Security model:

- passwords are not persisted,
- Scratch session IDs remain server-side in PHP session state,
- Wizard requests use CSRF protection,
- sensitive token/session/password-like response fields are redacted,
- destructive actions require confirmation,
- HTTPS is recommended for authenticated use.

---

# Reliability and tooling

```php
$scratch->retry()
    ->maxAttempts(4)
    ->backoff('exponential')
    ->retryOn([429, 500, 502, 503]);
```

```php
$scratch->circuitBreaker()->threshold(5)->cooldown(30);
print_r($scratch->metrics()->summary());
print_r($scratch->healthCheck(true));
```

CLI:

```bash
php bin/scratchbyphp version
php bin/scratchbyphp doctor --json
php bin/scratchbyphp project 104 --json
php bin/scratchbyphp user griffpatch --json
php bin/scratchbyphp studio 123456 --json
php bin/scratchbyphp analyze 104 --json
php bin/scratchbyphp check-api --json
php bin/scratchbyphp sb3:validate project.sb3 --json
php bin/scratchbyphp metrics --json
```

Testing helpers include FakeScratch, PHPUnit/PHPStan configuration and browser test panels under `/test-panels/index.php`.

---

# Security

ScratchByPHP includes authenticated-host restrictions, redirect hardening, sensitive log redaction, session-ID validation, bounded compressed-session decoding, file-path safeguards, Wizard server-side session/CSRF handling and CloudDB Pro prepared statements/identifier validation.

Application developers should still:

- keep `.env`, passwords, session IDs and database credentials out of Git,
- keep credential/config JSON files outside `public_html`,
- avoid passing raw user-controlled filesystem paths to helpers,
- use authenticated actions only for accounts they are authorized to operate,
- not use ScratchByPHP for spam, artificial engagement, CAPTCHA bypass or abuse.

See [SECURITY.md](SECURITY.md).

---

# Links

- Website: https://www.blocklandin.com/scratchbyphp/
- Documentation: https://www.blocklandin.com/scratchbyphp/docs
- GitHub: https://github.com/scratchbyphp/scratchbyphp
- Packagist: https://packagist.org/packages/scratchbyphp/scratchbyphp
- AI/LLM reference: [`docs/llms.txt`](docs/llms.txt)
- Examples: [`examples/`](examples/)

# Credits

[TimMcCool/scratchattach](https://github.com/TimMcCool/scratchattach) has been an important reference and inspiration for API design and feature scope. ScratchByPHP is an independent PHP implementation and is not an official PHP port of scratchattach.

# License

[MIT License](LICENSE)

ScratchByPHP is not affiliated with the Scratch Foundation. Scratch and related marks belong to their respective owners.
