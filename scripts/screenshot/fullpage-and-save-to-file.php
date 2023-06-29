<?php

declare(strict_types=1);

/*
 * This file is part of the sjorek/browser package.
 *
 * © Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/** @var \Sjorek\Browser\Script\Scope $scope */
$page = $scope->getPage();
$file = $scope->getInput()->getArgument('file');
$stdout = $scope->getStdout();
if ($stdout->isVerbose()) {
    $stdout->info('Take full-page screenshot of current page and save it to file: ' . $file);
}

$page->screenshot([
    'captureBeyondViewport' => true,
    'clip' => $page->getFullPageClip(),
    'format' => 'png', // default to 'png' - possible values: 'png', 'jpeg',
])->saveToFile($file);
