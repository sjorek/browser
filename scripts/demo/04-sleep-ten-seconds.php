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
    $stdout->info('Sleep 10 seconds');
    $stdout->progressStart(10);
    foreach (range(0, 9) as $iteration) {
        sleep(1);
        $stdout->progressAdvance(1);
    }
    $stdout->progressFinish();
} else {
    sleep(10);
}
