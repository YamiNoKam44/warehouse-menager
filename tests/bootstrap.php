<?php

use Symfony\Component\Dotenv\Dotenv;

require sprintf('%s/vendor/autoload.php', dirname(__DIR__));

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(sprintf('%s/.env', dirname(__DIR__)));
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
