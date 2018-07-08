#!/usr/bin/env php
<?php

use App\Command\BreathalizeCommand;
use Symfony\Component\Console\Application;

set_time_limit(0);

require __DIR__.'/../vendor/autoload.php';

$application = new Application();
$application->add(new BreathalizeCommand());
$application->run();