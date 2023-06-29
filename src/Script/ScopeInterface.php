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

namespace Sjorek\Browser\Script;

use HeadlessChromium\Browser;
use HeadlessChromium\Page;
use Sjorek\Browser\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface ScopeInterface
{
    public function getBrowser(): Browser;

    public function getPage(): Page;

    public function getInput(): InputInterface;

    public function getOutput(): OutputInterface;

    public function getStdout(): SymfonyStyle;

    public function getStderr(): SymfonyStyle;
}
