#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use App\Migration;
use App\ElasticBulk;

$application = new Application();

$application->add(new Migration());
$application->add(new ElasticBulk());

$application->run();


?>