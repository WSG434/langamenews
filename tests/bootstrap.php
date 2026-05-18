<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Run migrations before test suite
$process = new Process(['php', 'bin/console', 'doctrine:migrations:migrate', '--no-interaction', '--env=test'], dirname(__DIR__));
$process->run();
if (!$process->isSuccessful()) {
    echo "Migration failed: " . $process->getErrorOutput() . "\n";
}
