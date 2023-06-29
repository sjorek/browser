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
$page = $scope->getPage();

$retries = 10;
while (--$retries > 0) {
    $ucUiExists = $page
        ->callFunction('function() { return typeof window.UC_UI === "object" }')
        ->getReturnValue()
    ;
    if ($ucUiExists) {
        break;
    }
    sleep(3);
}

$allConsentsAreAccepted = $page
    ->callFunction('function() { return window.UC_UI.areAllConsentsAccepted(); }')
    ->getReturnValue()
;

if ($allConsentsAreAccepted) {
    if ($stdout->isVerbose()) {
        $stdout->info('All usercentrics consents have already been accepted');
    }
} else {
    $page
        ->callFunction('async function() { await window.UC_UI.acceptAllConsents(); }')
        ->waitForPageReload()
    ;
    if ($stdout->isVerbose()) {
        $stdout->info('Accepted all usercentrics consents');
    }
}
