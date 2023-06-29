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

use Sjorek\Browser\Script\Crawl\Url\NewUrl;
use Sjorek\Browser\Script\Crawl\Url\PendingUrl;
use Sjorek\Browser\Script\Crawl\Url\RunningUrl;
use Sjorek\Browser\Script\Crawl\Url\UrlInterface;

class Queue
{
    /** @var UrlInterface[] */
    protected array $urls = [];

    public function __construct(protected Scope $scope)
    {
    }

    public function addUrl(string $url): UrlInterface
    {
        $url = new NewUrl($url, $this->scope);

        return $url->transformToNextState();
    }

    public function addUrls(array $urls, bool $sort = true): array
    {
        $urls = array_map([$this, 'addUrl'], $urls);

        return $sort ? self::sortUrls($urls) : $urls;
    }

    public function hasUrl(UrlInterface $url, bool $strict = false): bool
    {
        return $strict
            ? \in_array($url, $this->urls, true)
            : isset($this->urls[self::normalizeCanonical($url)])
        ;
    }

    public function getUrls(bool $sort = true): array
    {
        return $sort ? self::sortUrls($this->urls) : $this->urls;
    }

    public function getUrl(UrlInterface $url): UrlInterface
    {
        $canonical = self::normalizeCanonical($url);
        if (!isset($this->urls[$canonical])) {
            throw new \InvalidArgumentException('Given url is not in queue: ' . $url);
        }

        return $this->urls[$canonical];
    }

    public function setUrl(UrlInterface $url): void
    {
        $this->urls[self::normalizeCanonical($url)] = $url;
    }

    public function hasPending(): bool
    {
        foreach ($this->urls as $url) {
            if ($url instanceof PendingUrl) {
                return true;
            }
        }

        return false;
    }

    public function getPending(): PendingUrl
    {
        foreach ($this->urls as $url) {
            if ($url instanceof PendingUrl) {
                return $url;
            }
        }

        throw new \RuntimeException('There is no pending url left');
    }

    public function isRunning(): bool
    {
        foreach ($this->urls as $url) {
            if ($url instanceof RunningUrl) {
                return true;
            }
        }

        return false;
    }

    public function isDone(): bool
    {
        foreach ($this->urls as $url) {
            if ($url instanceof PendingUrl || $url instanceof RunningUrl) {
                return false;
            }
        }

        return true;
    }

    protected static function normalizeCanonical(UrlInterface $url): string
    {
        return rtrim($url->getCanonical(), '/');
    }

    protected const URL_SORT_PATTERNS = [
        'done' => '010-%09d-%s',
        'new' => '020-%09d-%s',
        'running' => '030-%09d-%s',
        'pending' => '040-%09d-%s',
        'failed' => '050-%09d-%s',
        'skipped' => '060-%2$s-%1$d',
    ];

    protected static function sortUrls(array $urls): array
    {
        $prefix = fn (UrlInterface $url): string => sprintf(
            self::URL_SORT_PATTERNS[$url->getState()],
            $url->getDepth(),
            $url->getCanonical()
        );

        uasort($urls, fn (UrlInterface $a, UrlInterface $b): int => $prefix($a) <=> $prefix($b));

        return $urls;
    }
}
