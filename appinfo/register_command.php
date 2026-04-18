<?php

declare(strict_types=1);

/** @var \Symfony\Component\Console\Application $application */
/** @var \OCP\IServerContainer $serverContainer */

$application->add($serverContainer->get(\OCA\Lists\Command\DebugSeed::class));
