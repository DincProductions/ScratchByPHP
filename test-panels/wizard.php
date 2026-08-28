<?php
declare(strict_types=1);

require dirname(__DIR__) . '/autoload.php';
use ScratchByPHP\Scratch;

$scratch = new Scratch();
$wizard = $scratch->wizard([
    'allow_auth' => true,
    'allow_writes' => true,
    'cloud_request_handlers' => [
        'ping' => fn(array $params) => $params[0] ?? 'pong',
        'sum' => fn(array $params) => array_sum(array_map('floatval', $params)),
    ],
]);

$wizard->handle();
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ScratchByPHP Wizard Pro Test</title>
<style>
body{font-family:Arial,sans-serif;background:#f7f5fb;margin:0;padding:32px;color:#2d2440}
.demo{max-width:900px;margin:auto;background:white;border-radius:22px;padding:28px;box-shadow:0 12px 45px #6f42c11c}
h1{margin-top:0;color:#6f42c1}.tag{display:inline-block;background:#fff1df;color:#c96c00;padding:5px 10px;border-radius:999px;font-weight:700;font-size:12px}
</style>
</head>
<body>
<div class="demo">
<span class="tag">v0.8.5 Wizard Pro</span>
<h1>ScratchByPHP Control Center Test</h1>
<p>Sağ alttaki ScratchByPHP butonunu aç. Modal sürüklenebilir, köşeden resize edilebilir ve □ ile tam ekrana alınabilir.</p>
<p>Public işlemler girişsiz; AUTH etiketli işlemler için modal içinden Scratch hesabına giriş yap.</p>
</div>
<?= $wizard->render(['start_open'=>false]) ?>
</body>
</html>
