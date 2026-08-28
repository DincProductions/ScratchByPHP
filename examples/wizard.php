<?php
require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();

$wizard = $scratch->wizard([
    'allow_auth' => true,
    'allow_writes' => true,
    'cloud_request_handlers' => [
        'sum' => fn(array $params) => array_sum(array_map('floatval', $params)),
        'hello' => fn(array $params) => 'Merhaba ' . ($params[0] ?? 'Scratch'),
    ],

    // MySQL bilgisi browser'a gitmez. Modal sadece "main" profil adını görür.
    'clouddb_profiles' => [
        'main' => __DIR__ . '/../secure/mysql.json',
    ],
]);

$wizard->handle();
?>
<!doctype html>
<html lang="tr">
<head><meta charset="utf-8"><title>ScratchByPHP Wizard Pro</title></head>
<body>
<h1>Benim web sitem</h1>

<?= $wizard->render([
    'title' => 'ScratchByPHP Control Center',
    'button' => 'ScratchByPHP',
    'tailwind_cdn' => true,
    'width' => 980,
    'height' => 680,
]) ?>

</body>
</html>
