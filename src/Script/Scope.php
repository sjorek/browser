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

class Scope implements ScopeInterface
{
    public function __construct(
        protected Browser $browser,
        protected Page $page,
        protected InputInterface $input,
        protected OutputInterface $output,
        protected SymfonyStyle $stdout,
        protected SymfonyStyle $stderr)
    {
    }

    public function getBrowser(): Browser
    {
        return $this->browser;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function getStdout(): SymfonyStyle
    {
        return $this->stdout;
    }

    public function getStderr(): SymfonyStyle
    {
        return $this->stderr;
    }
}
