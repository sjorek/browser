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
    $stdout->info('Crawling');
    $lineLength = $stdout->getLineLength() - 15;
    $stdout->table(
        ['state', 'url'],
        array_map(
            fn (\Sjorek\Browser\Script\Crawl\Url\UrlInterface $url): array => [
                $url->getState(),
                strlen($url->getCanonical()) < $lineLength
                    ? $url->getCanonical()
                    : substr($url->getCanonical(), 0, $lineLength) . '…',
            ],
            $scope->getQueue()->getUrls()
        )
    );
}
