<?php

if (!(@include __DIR__ . '/../vendor/autoload.php')) {
  echo 'Install Nette Tester using "composer update --dev"';
  exit(1);
}

require __DIR__ . '/FileContent.php';

date_default_timezone_set('Europe/Prague');

Tester\Environment::setup();

define('TEMP_DIR', __DIR__ . '/tmp/' . getmypid());
Tester\Helpers::purge(TEMP_DIR);
