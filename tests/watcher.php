<?php
declare(strict_types=1);

require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Watch\ProjectWatch;

$old = [
    'views' => 100,
    'loves' => 10,
    'favorites' => 5,
    'remixes' => 2,
    'latest_comment_id' => '111',
];

$new = [
    'views' => 103,
    'loves' => 11,
    'favorites' => 5,
    'remixes' => 3,
    'latest_comment_id' => '222',
];

$changes = ProjectWatch::diffStates($old, $new);

foreach (['views', 'loves', 'remixes', 'comments'] as $field) {
    if (!array_key_exists($field, $changes)) {
        throw new RuntimeException('Watcher change eksik: ' . $field);
    }
}

if (isset($changes['favorites'])) {
    throw new RuntimeException('Değişmeyen favorites event üretti.');
}

echo "ScratchByPHP watcher regression test OK\n";
