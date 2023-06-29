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
$stdout = $scope->getStdout();
if ($stdout->isVerbose()) {
    $stdout->info('Collect anchor urls from: ' . $scope->getUrl()->getCanonical());
}

$urls = array_unique(
    array_map(
        fn (\HeadlessChromium\Dom\Node $node): string => $node->getAttribute('href') ?? '',
        $scope->getPage()->dom()->querySelectorAll('a[href]')
    )
);
sort($urls);
$scope->getQueue()->addUrls($urls);
