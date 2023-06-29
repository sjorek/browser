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

namespace Sjorek\Browser\Script\Crawl;

use HeadlessChromium\Browser;
use HeadlessChromium\Page;
use Sjorek\Browser\Console\Style\SymfonyStyle;
use Sjorek\Browser\Script\Crawl\Url\BaseUrl;
use Sjorek\Browser\Script\Crawl\Url\UrlInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Scope extends \Sjorek\Browser\Script\Scope
{
    protected BaseUrl $baseUrl;
    protected Queue $queue;
    protected ?UrlInterface $url = null;
    protected int $depth;

    public function __construct(
        string $baseUrl,
        Browser $browser,
        Page $page,
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $stdout,
        SymfonyStyle $stderr)
    {
        parent::__construct($browser, $page, $input, $output, $stdout, $stderr);

        $this->baseUrl = new BaseUrl($baseUrl, $this);
        $this->depth = $input->getOption('depth');

        $this->queue = new Queue($this);
        $this->queue->addUrl($this->baseUrl->getCanonical());
    }

    public function getBaseUrl(): BaseUrl
    {
        return $this->baseUrl;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function setUrl(UrlInterface $url): void
    {
        $this->url = $url;
    }

    public function getUrl(): UrlInterface
    {
        return $this->url;
    }
}
