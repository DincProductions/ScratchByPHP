<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="docs/assets/brand/scratchbyphp-logo-full-dark.png">
    <img src="docs/assets/brand/scratchbyphp-logo-full-light.png" alt="ScratchByPHP" width="760">
  </picture>
</p>

<p align="center">
  <strong>PHP ile Scratch arasında köprü.</strong><br>
  Scratch projelerini, kullanıcılarını, stüdyolarını, oturum işlemlerini ve Cloud Variables altyapısını PHP uygulamalarına taşımayı kolaylaştıran açık kaynak araç seti.
</p>

<p align="center">
  <a href="https://github.com/scratchbyphp/scratchbyphp/actions/workflows/ci.yml"><img alt="CI" src="https://github.com/scratchbyphp/scratchbyphp/actions/workflows/ci.yml/badge.svg"></a>
  <a href="https://www.php.net/"><img alt="PHP 8.1+" src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white"></a>
  <a href="LICENSE"><img alt="MIT License" src="https://img.shields.io/badge/license-MIT-2ea44f"></a>
  <a href="https://packagist.org/packages/scratchbyphp/scratchbyphp"><img alt="Packagist" src="https://img.shields.io/badge/Packagist-scratchbyphp%2Fscratchbyphp-f28d1a?logo=packagist&logoColor=white"></a>
</p>

<p align="center">
  <strong>Türkçe</strong> · <a href="README.en.md">English</a> · <a href="https://scratchbyphp.github.io/scratchbyphp/">Dokümantasyon</a> · <a href="docs/brand.html">Brand Assets</a>
</p>

---

## ScratchByPHP nedir?

ScratchByPHP, **PHP tabanlı web siteleri ve uygulamalar ile Scratch arasında yeniden kullanılabilir bir bağlantı katmanı** oluşturmayı amaçlar.

Scratch ile çalışan bir web sitesi, panel, backend veya servis geliştirirken her endpoint'i, cookie/header yapısını, session akışını ve Cloud WebSocket protokolünü ayrı ayrı yazmak yerine daha okunabilir PHP nesneleri kullanabilirsiniz.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();
$project = $scratch->project(104);

echo $project->title();
echo $project->views();
```

Projenin temel fikri:

```text
Scratch
   ↕
API / Session / Cloud Variables
   ↕
ScratchByPHP
   ↕
PHP Website / Backend / Panel / Application
```

> ScratchByPHP, Scratch Foundation tarafından geliştirilmiş veya resmî olarak desteklenen bir SDK değildir. Scratch'in resmî olmayan/uygulama içi endpoint'leri zaman içinde değişebilir.

## Neden ScratchByPHP?

PHP web tarafında hâlâ çok yaygın kullanılan bir teknolojidir; buna rağmen Scratch ile PHP'yi bir araya getirmek isteyen geliştiriciler çoğu işlemi kendileri uygulamak zorunda kalabilir.

ScratchByPHP'nin amacı Scratch'i yeni kullanım alanlarına açmaktır: klasik web siteleri, kontrol panelleri, küçük backend servisleri, shared-hosting projeleri ve Cloud Variable tabanlı deneyler.

| Alan | ScratchByPHP ile |
|---|---|
| Public API | Proje, kullanıcı ve stüdyo verilerini okuyun |
| Authentication | Kullanıcı adı/parola veya session ID ile oturum oluşturun |
| Projects | İstatistik, yorum, remix, paylaşım ve analiz işlemleri |
| Users | Profil, takip, projeler ve kullanıcı verileri |
| Studios | Proje, curator/manager ve stüdyo yönetimi |
| Cloud | Cloud Variables okuyun, yazın ve değişiklikleri dinleyin |
| CloudRequests | Scratch → PHP RPC benzeri iletişim kurun |
| CloudDatabase | Küçük Cloud tabanlı key/value verileri yönetin |
| Project Analyzer | Scratch proje JSON'unu analiz edin |
| Player | Scratch/TurboWarp iframe yardımcılarını kullanın |
| Registration Assistant | Tek hesaplık credential üretimi ve kayıt yardımcısı |

---

## Kurulum

### Composer ile — önerilen

```bash
composer require scratchbyphp/scratchbyphp
```

Ardından:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();
```

### Composer nedir?

Composer, PHP'nin paket yöneticisidir. Node.js'teki `npm` benzeri şekilde bağımlılıkları indirir ve autoload işlemini hazırlar.

Composer kullanmak istemiyorsanız repoyu ZIP olarak indirip kendi autoload dosyamızı da kullanabilirsiniz:

```php
require __DIR__ . '/scratchbyphp/autoload.php';
```

## Gereksinimler

- PHP **8.1+**
- `ext-curl`
- `ext-openssl`
- `ext-json`
- Cloud özellikleri için dışarı WebSocket/TLS bağlantısına izin veren bir sunucu

---

## Hızlı başlangıç

### Public proje bilgisi — giriş gerekmez

```php
$project = $scratch->project(104);

echo 'Başlık: ' . $project->title() . PHP_EOL;
echo 'Yazar: ' . $project->author() . PHP_EOL;
echo 'Görüntülenme: ' . $project->views() . PHP_EOL;
echo 'Beğeni: ' . $project->loves() . PHP_EOL;
echo 'Favori: ' . $project->favorites() . PHP_EOL;
```

### Scratch hesabına giriş

```php
$session = $scratch->login(
    'KullaniciAdi',
    'Sifre'
);

echo $session->username();
```

Session ID ile:

```php
$session = $scratch->loginWithSessionId(
    getenv('SCRATCH_SESSION_ID')
);
```

Başarılı girişten sonra session üzerinden auth gerektiren nesneleri oluşturabilirsiniz:

```php
$project = $session->project(104);
$user    = $session->user('BirKullanici');
$studio  = $session->studio(123456);
$cloud   = $session->cloud(104);
```

---

## Project API

```php
$project = $scratch->project(104);

echo $project->title();
echo $project->author();
echo $project->views();
echo $project->loves();
echo $project->favorites();

$comments = $project->comments();
$remixes = $project->remixes();
$remixInfo = $project->remixInfo();
```

Auth gerektiren örnekler:

```php
$project = $session->project(104);

$project->love();
$project->favorite();
$project->postComment('Merhaba Scratch!');
```

Projeyi paylaşma/paylaşımdan kaldırma:

```php
$project->share();
$project->unshare();
```

### Project Analyzer

```php
$analysis = $scratch->project(104)->analyze();

print_r($analysis->summary());
```

Proje yapısındaki sprite, block, costume, sound, variable, Cloud Variable ve extension gibi bilgileri incelemek için kullanılabilir.

### Project Player

```php
$project = $scratch->project(104);

echo $project->player(800, 600);
```

TurboWarp:

```php
echo $project->run([
    'engine' => 'turbowarp',
    'width' => 900,
    'height' => 650,
]);
```

> Player helper'ları iframe üretir. Scratch görüntülenme sayısını artırmak için tasarlanmış özel bir özellik değildir.

---

## User API

```php
$user = $scratch->user('griffpatch');

$data = $user->get();
$projects = $user->projects();
$followers = $user->followers();
$following = $user->following();
$favorites = $user->favorites();
```

Auth ile:

```php
$user = $session->user('BirKullanici');

$user->follow();
$user->unfollow();
```

Kendi profiliniz üzerinde desteklenen alanları güncelleyebilirsiniz:

```php
$me = $session->user($session->username());

$me->setBio('Yeni bio');
$me->setStatus('Yeni durum');
```

---

## Studio API

```php
$studio = $scratch->studio(123456);

$projects = $studio->projects();
$curators = $studio->curators();
$managers = $studio->managers();
```

Auth ile:

```php
$studio = $session->studio(123456);

$studio->addProject(104);
$studio->removeProject(104);

$studio->inviteCurator('Kullanici');
$studio->promoteCurator('Kullanici');
$studio->removeCurator('Kullanici');

$studio->setTitle('Yeni isim');
$studio->setDescription('Yeni açıklama');
```

---

## Scratch Cloud Variables

```php
$cloud = $session->cloud(104);
$cloud->connect();

$value = $cloud->getRemote('score');
$result = $cloud->setVerified('score', 500);

$cloud->disconnect();
```

`setVerified()` yalnızca local cache'i güncellemek yerine mümkün olduğunda Scratch tarafındaki değeri de doğrular.

### Değişiklik dinleme

```php
$cloud->connect();

$cloud->onVariable('score', function ($value, $variable) {
    echo 'Yeni skor: ' . $value . PHP_EOL;
});

$cloud->listen();
```

Uzun süre çalışan listener'ları normal HTTP request yerine CLI/worker süreçlerinde kullanmak daha uygundur.

### CloudRequests

```php
$cloud = $session->cloud(104);
$cloud->connect();

$rpc = $cloud->requests('request', 'response');

$rpc->on('sum', function (array $params) {
    return array_sum($params);
});

$rpc->run();
```

### CloudDatabase

```php
$db = $cloud->database('db');

$db->set('level', 12);
echo $db->get('level');
$db->delete('level');
```

CloudDatabase, MySQL/SQLite yerine kullanılacak genel amaçlı bir veritabanı değildir; Scratch Cloud limitlerine uygun küçük durum verileri içindir.

---

## Registration Assistant

Registration Assistant CAPTCHA çözmez veya atlamaz. Kullanıcı kayıt işlemini Scratch'in resmî sayfasında tamamlar.

```php
$registration = $scratch->registration();

$result = $registration->generateAvailableCredentials('ScratchUser');

echo $registration->joinUrl();
```

Credential JSON:

```php
$json = $registration->credentialsJson(
    'ScratchUser_ABC123',
    'StrongPassword123!',
    'mail@example.com'
);
```

> Credential JSON formatında parola bilerek plaintext tutulabilir. Bu dosyaları parola dosyası gibi koruyun ve repoya commit etmeyin.

---

## Hata yönetimi

```php
use ScratchByPHP\Exceptions\ApiException;
use ScratchByPHP\Exceptions\LoginException;

try {
    $session = $scratch->login($username, $password);
} catch (LoginException $e) {
    echo 'Giriş başarısız: ' . $e->getMessage();
} catch (ApiException $e) {
    echo 'Scratch API hatası: ' . $e->getMessage();
}
```

---

## Güvenlik

ScratchByPHP `v0.5.1` ile auth istemcisinde ek güvenlik sertleştirmeleri içerir:

- Credential taşıyan HTTP istekleri HTTPS Scratch hostlarıyla sınırlandırılır.
- Auth taşıyan isteklerde otomatik cross-host redirect engellenir.
- Logger token/session/cookie gibi hassas alanları maskeler.
- Session ID için uzunluk ve kontrol karakteri doğrulaması yapılır.
- Sıkıştırılmış session payload decode boyutu sınırlandırılır.

Ayrıca:

- `.env`, password, session ID ve token değerlerini commit etmeyin.
- Kullanıcıdan gelen filesystem path'lerini doğrudan indirme/yükleme metodlarına vermeyin.
- Kendi hesabınız veya işlem yapmaya yetkili olduğunuz hesaplarla kullanın.
- CAPTCHA/anti-abuse mekanizmalarını atlatmak, spam veya yapay etkileşim üretmek için kullanmayın.

Detaylar: [SECURITY.md](SECURITY.md)

---

## Dokümantasyon

Ana dil **Türkçe**dir. İngilizce dokümantasyon da birlikte tutulur.

- 🇹🇷 [Türkçe Dokümantasyon](https://scratchbyphp.github.io/scratchbyphp/)
- 🇬🇧 [English Documentation](https://scratchbyphp.github.io/scratchbyphp/en.html)
- 🎨 [Brand Assets](docs/brand.html)
- 🧪 [`examples/`](examples/)
- 🧰 [`tests/`](tests/)

Dokümantasyondaki kod snippet'lerinde tek tık **Kopyala** butonu bulunur.

---

## Proje yapısı

```text
ScratchByPHP/
├── .github/             # CI, Pages, issue ve PR şablonları
├── docs/                # TR + EN GitHub Pages dokümantasyonu
│   └── assets/brand/    # Logo / marka dosyaları
├── examples/            # Başlangıç örnekleri
├── src/                 # Kütüphane kaynak kodu
├── tests/               # Smoke + security testleri
├── README.md            # Türkçe ana README
├── README.en.md         # English README
├── SECURITY.md
├── CONTRIBUTING.md
├── THIRD_PARTY_NOTICES.md
├── CHANGELOG.md
├── LICENSE
└── composer.json
```

---

## Marka dosyaları

Repo genelinde standart logo isimleri şunlardır:

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

README ve dokümantasyon bu isimleri referans alır.

---

## Test ve geliştirme

```bash
composer install
composer validate --strict
composer lint
php tests/smoke.php
php tests/security.php
```

GitHub Actions, PHP **8.1–8.4** üzerinde otomatik kontrol çalıştıracak şekilde hazırlanmıştır.

---

## Packagist'e yayınlama

GitHub'a push ettikten sonra bir release/tag oluşturun:

```bash
git tag v0.5.1
git push origin v0.5.1
```

Ardından repository'yi Packagist'e ekleyin. Yayın sonrasında kullanıcılar:

```bash
composer require scratchbyphp/scratchbyphp
```

ile kurabilir.

Semantic Versioning yaklaşımı önerilir:

```text
0.5.1  hata/güvenlik düzeltmeleri
0.6.0  yeni özellikler
1.0.0  kararlı public API
```

---

## Teşekkür ve kaynaklar

ScratchByPHP'nin API tasarımı ve özellik kapsamı geliştirilirken **TimMcCool tarafından geliştirilen [scratchattach](https://github.com/TimMcCool/scratchattach)** önemli bir referans ve ilham kaynağı olmuştur.

`scratchattach`, Python tarafında kapsamlı bir Scratch API ve Cloud araç setidir ve MIT lisansı ile yayımlanmaktadır.

ScratchByPHP bağımsız bir PHP uygulamasıdır; scratchattach'ın resmî PHP portu değildir. Ayrıntılı bildirim için [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) dosyasına bakın.

---

## Katkı

Pull request ve issue'lar açıktır. Katkıda bulunmadan önce [CONTRIBUTING.md](CONTRIBUTING.md) dosyasını inceleyin.

Hata bildirirken mümkünse:

- ScratchByPHP sürümü
- PHP sürümü
- minimal örnek kod
- alınan exception/HTTP sonucu

bilgilerini ekleyin; **credential paylaşmayın**.

---

## Lisans

[MIT License](LICENSE)

ScratchByPHP, Scratch Foundation ile bağlantılı değildir. “Scratch” ve ilgili markalar ilgili sahiplerine aittir.
