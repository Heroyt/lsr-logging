<?php

define('ROOT', dirname(__DIR__) . '/');
const LOG_DIR = ROOT . 'tests/logs/';
const PROTECTED_DIR = ROOT . 'tests/logs/protected';

require_once ROOT . 'vendor/autoload.php';

ini_set('open_basedir', ROOT);

// Create log directory if not already created
if (!file_exists(LOG_DIR) && !mkdir(LOG_DIR) && !is_dir(LOG_DIR)) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', LOG_DIR));
}
// Create log directory if not already created
if (!file_exists(PROTECTED_DIR) && !mkdir(PROTECTED_DIR, 0500) && !is_dir(PROTECTED_DIR)) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', PROTECTED_DIR));
}

chmod(PROTECTED_DIR, 0500);
