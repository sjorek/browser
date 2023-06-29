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

/** @var \Sjorek\Browser\Script\Crawl\Scope $scope */
$url = $scope->getUrl();
$stdout = $scope->getStdout();
if ($stdout->isVerbose()) {
    $stdout->info('Open url in browser page and await navigation: ' . $url->getCanonical());
}

$scope->getPage()->navigate($url->getCanonical())->waitForNavigation();
