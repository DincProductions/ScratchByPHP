<?php
declare(strict_types=1);

require dirname(__DIR__) . '/autoload.php';

use ScratchByPHP\Scratch;

$scratch = new Scratch();

if (Scratch::VERSION === '') {
    throw new RuntimeException('VERSION boş.');
}

$project = $scratch->project(104);

if ($project->id() !== '104') {
    throw new RuntimeException('Project factory başarısız.');
}

if (!str_contains($project->url(), '/projects/104/')) {
    throw new RuntimeException('Project URL helper başarısız.');
}

$registration = $scratch->registration();
$credentials = $registration->generateCredentials('Test', 5, 14);

if (empty($credentials['username']) || empty($credentials['password'])) {
    throw new RuntimeException('Credential generation başarısız.');
}

echo "ScratchByPHP smoke test OK\n";
