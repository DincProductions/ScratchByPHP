# ScratchByPHP

> **A bridge between PHP and Scratch.** An open-source PHP library designed to make Scratch projects, users, studios, authenticated sessions, and Cloud Variables easier to use from PHP applications.

[![CI](https://github.com/scratchbyphp/scratchbyphp/actions/workflows/ci.yml/badge.svg)](https://github.com/scratchbyphp/scratchbyphp/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

[Türkçe README](README.md) · **English** · [Documentation](docs/en.html)

## What is ScratchByPHP?

ScratchByPHP aims to connect ordinary PHP websites and backend applications to Scratch without requiring every developer to manually implement Scratch endpoints, authentication details, cookies, tokens, and the Cloud WebSocket protocol.

```php
use ScratchByPHP\Scratch;

$scratch = new Scratch();

$project = $scratch->project(104);

echo $project->title();
echo $project->views();
```

The project's broader goal is to become a practical bridge:

```text
Scratch Project
      ↕
Scratch API / Cloud Variables
      ↕
ScratchByPHP
      ↕
PHP Website / Backend / Application
```

ScratchByPHP is not an official Scratch Foundation SDK. Scratch endpoints can change.

## Installation

```bash
composer require scratchbyphp/scratchbyphp
```

```php
require __DIR__ . '/vendor/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();
```

PHP 8.1+, cURL, OpenSSL, and JSON extensions are required.

## Public project data

```php
$project = $scratch->project(104);

echo $project->title();
echo $project->author();
echo $project->views();
echo $project->loves();
echo $project->favorites();
```

## Authentication

```php
$session = $scratch->login(
    'Username',
    'Password'
);

echo $session->username();
```

Or:

```php
$session = $scratch->loginWithSessionId(
    getenv('SCRATCH_SESSION_ID')
);
```

Never commit passwords, session IDs, CSRF tokens, or X-Tokens.

## Users

```php
$user = $scratch->user('griffpatch');

$data = $user->get();
$projects = $user->projects();
$followers = $user->followers();
```

Authenticated actions:

```php
$user = $session->user('ExampleUser');

$user->follow();
$user->unfollow();
```

## Projects

```php
$project = $session->project(104);

$project->love();
$project->favorite();
$project->postComment('Hello from PHP');
```

Other project helpers include comments, remixes, sharing, thumbnails, analysis, and project-player URLs.

```php
echo $project->player(800, 600);

echo $project->run([
    'engine' => 'turbowarp',
    'width' => 900,
    'height' => 650
]);
```

The player helpers are iframe helpers and are not designed as view-count manipulation functions.

## Studios

```php
$studio = $session->studio(123456);

$studio->addProject(104);
$studio->removeProject(104);

$studio->inviteCurator('ExampleUser');
$studio->promoteCurator('ExampleUser');

$studio->setTitle('New title');
$studio->setDescription('New description');
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

Long-running listeners should normally run in a CLI/worker process rather than a normal HTTP request.

## CloudRequests

```php
$cloud = $session->cloud(104)->connect();

$rpc = $cloud->requests();

$rpc->on('sum', function (array $params) {
    return array_sum($params);
});

$rpc->run();
```

## Registration Assistant

The registration helper does not solve or bypass CAPTCHA.

```php
$registration = $scratch->registration();

$result = $registration->generateAvailableCredentials('ScratchUser');

echo $registration->joinUrl();
```

## Error handling

```php
use ScratchByPHP\Exceptions\LoginException;

try {
    $session = $scratch->login($username, $password);
} catch (LoginException $e) {
    echo $e->getMessage();
}
```

## Publishing to Packagist

Create a GitHub repository, push this repository, create a `v0.5.0` tag, and submit the repository URL to Packagist. Users can then install with:

```bash
composer require scratchbyphp/scratchbyphp
```

## Responsible use

Use authenticated actions only with accounts you own or are authorized to use. Do not use ScratchByPHP for CAPTCHA bypass, spam, artificial engagement, credential abuse, or other platform abuse.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT. See [LICENSE](LICENSE).

ScratchByPHP is not affiliated with the Scratch Foundation. Scratch and related marks belong to their respective owners.
