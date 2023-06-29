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
$stdout = $scope->getStdout();
if ($stdout->isVerbose()) {
    $stdout->info('Search “test” on www.google.de');
}

$page = $scope->getPage();
$page->keyboard()->setKeyInterval(100);

$textarea = $page->dom()->querySelector('textarea[type="search"]');
$textarea->scrollIntoView();
$textarea->focus();
$textarea->sendKeys('test');

// $page->waitForElement('input[type=submit]');
sleep(1);

$page->dom()->querySelector('input[type=submit]')->scrollIntoView();
$page->mouse()->find('input[type=submit]')->click();
