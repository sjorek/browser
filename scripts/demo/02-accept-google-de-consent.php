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
    $stdout->info('Accept consent on www.google.de');
}

$page = $scope->getPage();
/** @var \HeadlessChromium\Dom\Node $button */
$button = $page->dom()->search("//button/div[contains(text(),'Alle akzeptieren')]//parent::button")[0];
$button->scrollIntoView();
$page->mouse()->find('#' . $button->getAttribute('id'))->click();

sleep(1);
