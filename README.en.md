<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="docs/assets/brand/scratchbyphp-logo-full-dark.png">
    <img src="docs/assets/brand/scratchbyphp-logo-full-light.png" alt="ScratchByPHP" width="760">
  </picture>
</p>

<p align="center">
  <strong>A bridge between PHP and Scratch.</strong><br>
  An open-source toolkit that makes Scratch projects, users, studios, authenticated sessions and Cloud Variables easier to use from PHP applications.
</p>

<p align="center">
  <a href="https://github.com/scratchbyphp/scratchbyphp/actions/workflows/ci.yml"><img alt="CI" src="https://github.com/scratchbyphp/scratchbyphp/actions/workflows/ci.yml/badge.svg"></a>
  <a href="https://www.php.net/"><img alt="PHP 8.1+" src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white"></a>
  <a href="LICENSE"><img alt="MIT License" src="https://img.shields.io/badge/license-MIT-2ea44f"></a>
</p>

<p align="center">
  <a href="README.md">Türkçe</a> · <strong>English</strong> · <a href="https://scratchbyphp.github.io/scratchbyphp/en.html">Documentation</a> · <a href="docs/brand.html">Brand Assets</a>
</p>

---

## What is ScratchByPHP?

ScratchByPHP aims to provide a reusable bridge between **PHP websites/backends and Scratch**.

Instead of implementing Scratch endpoints, session cookies/headers and the Cloud WebSocket protocol from scratch for every project, developers can work with a more readable PHP API.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();
$project = $scratch->project(104);

echo $project->title();
echo $project->views();
```

```text
Scratch
   ↕
API / Session / Cloud Variables
   ↕
ScratchByPHP
   ↕
PHP Website / Backend / Panel / Application
```

> ScratchByPHP is not an official Scratch Foundation SDK. Unofficial/internal Scratch endpoints may change over time.

## Features

- Public user, project and studio data
- Username/password and session-ID authentication
- Project comments, likes/favorites, sharing and analysis
- User/profile helpers
- Studio project and curator/manager management
- Scratch Cloud Variables
- Cloud change listeners
- CloudRequests RPC layer
- CloudDatabase helper
- Project player helpers for Scratch and TurboWarp
- Registration Assistant and credential JSON helpers

---

## Installation

```bash
composer require scratchbyphp/scratchbyphp
```

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();
```

Requirements: PHP **8.1+**, cURL, OpenSSL and JSON extensions.

## Quick start

### Public project

```php
$project = $scratch->project(104);

echo $project->title();
echo $project->author();
echo $project->views();
echo $project->loves();
echo $project->favorites();
```

### Authentication

```php
$session = $scratch->login(
    'Username',
    'Password'
);
```

Or:

```php
$session = $scratch->loginWithSessionId(
    getenv('SCRATCH_SESSION_ID')
);
```

Never commit passwords, session IDs, X-Tokens or CSRF values.

---

## Projects

```php
$project = $session->project(104);

$project->love();
$project->favorite();
$project->postComment('Hello from PHP');
```

Project analysis:

```php
$analysis = $scratch->project(104)->analyze();
print_r($analysis->summary());
```

Player helper:

```php
echo $project->run([
    'engine' => 'turbowarp',
    'width' => 900,
    'height' => 650,
]);
```

Player helpers generate iframes; they are not designed as view-count manipulation features.

## Studios

```php
$studio = $session->studio(123456);

$studio->addProject(104);
$studio->removeProject(104);
$studio->inviteCurator('ExampleUser');
$studio->promoteCurator('ExampleUser');
$studio->setTitle('New title');
```

## Cloud Variables

```php
$cloud = $session->cloud(104);
$cloud->connect();

$value = $cloud->getRemote('score');
$result = $cloud->setVerified('score', 500);

$cloud->disconnect();
```

Listen for changes:

```php
$cloud->connect();

$cloud->onVariable('score', function ($value) {
    echo $value;
});

$cloud->listen();
```

Long-running listeners are best used in CLI/worker processes.

## CloudRequests

```php
$cloud = $session->cloud(104);
$cloud->connect();

$rpc = $cloud->requests('request', 'response');

$rpc->on('sum', function (array $params) {
    return array_sum($params);
});

$rpc->run();
```

## Registration Assistant

The helper does **not** solve or bypass CAPTCHA.

```php
$registration = $scratch->registration();
$result = $registration->generateAvailableCredentials('ScratchUser');

echo $registration->joinUrl();
```

Credential JSON may intentionally contain a plaintext password. Protect these files as secrets and do not commit them.

---

## Security

ScratchByPHP `v0.5.1` includes security hardening for authenticated HTTP requests, redirects, logging and session parsing.

See [SECURITY.md](SECURITY.md) for details.

---

## Documentation

- 🇹🇷 [Turkish documentation](https://scratchbyphp.github.io/scratchbyphp/)
- 🇬🇧 [English documentation](https://scratchbyphp.github.io/scratchbyphp/en.html)
- 🎨 [Brand assets](docs/brand.html)
- 🧪 [`examples/`](examples/)

Turkish is the primary documentation language; English documentation is maintained alongside it.

## Brand files

```text
docs/assets/brand/
├── scratchbyphp-app-icon-light.png
├── scratchbyphp-app-icon-dark.png
├── scratchbyphp-icon-light.png
├── scratchbyphp-logo-compact-light.png
├── scratchbyphp-logo-compact-dark.png
├── scratchbyphp-logo-full-light.png
└── scratchbyphp-logo-full-dark.png
```

---

## Development

```bash
composer install
composer validate --strict
composer lint
php tests/smoke.php
php tests/security.php
```

GitHub Actions is configured for PHP **8.1–8.4**.

## Credits

While designing ScratchByPHP's API and feature scope, **[TimMcCool/scratchattach](https://github.com/TimMcCool/scratchattach)** has been an important reference and source of inspiration.

ScratchByPHP is an independent PHP implementation and is not an official PHP port of scratchattach. See [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

[MIT License](LICENSE)

ScratchByPHP is not affiliated with the Scratch Foundation. Scratch and related marks belong to their respective owners.
