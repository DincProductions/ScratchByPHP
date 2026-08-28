# ScratchByPHP'e Katkı / Contributing

## Türkçe

Katkılar hoş karşılanır. Özellikle şunlar değerlidir:

- Scratch endpoint değişiklikleri için düzeltmeler
- Hata mesajlarının iyileştirilmesi
- PHP 8.1+ uyumluluğu
- Türkçe ve İngilizce dokümantasyon
- Yeni örnekler ve testler

### Başlangıç

```bash
git clone https://github.com/scratchbyphp/scratchbyphp.git
cd scratchbyphp
composer install
composer lint
php tests/smoke.php
```

Yeni özelliklerde mümkün olduğunca küçük, anlaşılır API'ler tercih edin.
Kullanıcı parolası/session/token bilgisini loglamayın.

## English

Contributions are welcome, especially fixes for changing Scratch endpoints,
better error messages, PHP compatibility, documentation, examples, and tests.

Run `composer lint` and `php tests/smoke.php` before opening a pull request.
Never log or commit passwords, session IDs, or tokens.
