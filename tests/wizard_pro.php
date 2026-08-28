<?php
declare(strict_types=1);
require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Scratch;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$s = new Scratch();
$w = $s->wizard(['allow_auth'=>true,'allow_writes'=>true]);
$html = $w->render(['tailwind_cdn'=>false]);

$needles = [
    'ScratchByPHP Control Center',
    'sbpw-card',
    'resize:both',
    'sbpw-max',
    'auth.login',
    '#855CD6',
    '#ff9f1c',
];

foreach ($needles as $needle) {
    if (!str_contains($html, $needle)) {
        throw new RuntimeException('Wizard Pro render eksik: ' . $needle);
    }
}


$rm = new ReflectionMethod($w, 'clientActions');
$rm->setAccessible(true);
$ids = array_column($rm->invoke($w), 'id');
foreach (['cloud.requests_once','watcher.baseline','project.love','studio.add_project','dev.health'] as $id) {
    if (!in_array($id, $ids, true)) {
        throw new RuntimeException('Wizard action eksik: ' . $id);
    }
}

if (Scratch::version() !== '0.8.5') {
    throw new RuntimeException('Version 0.8.5 değil.');
}

echo "ScratchByPHP Wizard Pro local test OK\n";
