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

namespace Sjorek\Browser\Script\Crawl\Url;

class NewUrl extends AbstractUrl
{
    protected function setCanonical(string $url): void
    {
        parent::setCanonical($url);

        $base = $this->scope->getBaseUrl()->getParts();
        $parts = $this->parts;
        $hasScheme = isset($parts['scheme']);

        $parts = array_combine(
            array_keys($parts),
            array_map(
                fn (string $value, string $part) => match ($part) {
                    'scheme', 'user', 'pass', 'host', 'port', 'query', 'fragment' => $value,
                    'path' => ($hasScheme || str_starts_with($value, '/'))
                        ? $value
                        : sprintf('%s/%s', rtrim($base['path'] ?? '', '/'), $value)
                    ,
                },
                $parts,
                array_keys($parts)
            )
        );

        $parts = array_merge(
            array_filter(
                $base,
                fn (string $part): bool => match ($part) {
                    'scheme', 'user', 'pass', 'host', 'port', 'path' => !$hasScheme,
                    'query', 'fragment' => false,
                },
                \ARRAY_FILTER_USE_KEY
            ),
            $parts
        );

        $canonical =
            (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
            ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
            (isset($parts['user']) ? "{$parts['user']}" : '') .
            (isset($parts['pass']) ? ":{$parts['pass']}" : '') .
            (isset($parts['user']) ? '@' : '') .
            (isset($parts['host']) ? "{$parts['host']}" : '') .
            (isset($parts['port']) ? ":{$parts['port']}" : '') .
            (isset($parts['path']) ? "{$parts['path']}" : '') .
            (isset($parts['query']) ? "?{$parts['query']}" : '') .
            (isset($parts['fragment']) ? "#{$parts['fragment']}" : '')
        ;

        $this->url = $canonical;
        $this->parts = $parts;
    }

    protected function setDepth(int $depth): void
    {
        $this->depth = $this->isWithinBase() ? $depth - $this->scope->getBaseUrl()->getDepth() : $depth;
    }

    public function isWithinBase(): bool
    {
        return str_starts_with($this->getCanonical(), $this->scope->getBaseUrl()->getCanonical());
    }

    public function isOutsideBase(): bool
    {
        return !$this->isWithinBase();
    }

    public function isWithinMaximumDepth(): bool
    {
        return $this->getDepth() <= $this->scope->getDepth();
    }

    public function isOutsideMaximumDepth(): bool
    {
        return $this->getDepth() > $this->scope->getDepth();
    }

    public function transformToNextState(): UrlInterface
    {
        if (!$this->scope->getQueue()->hasUrl($this)) {
            $this->scope->getQueue()->setUrl(
                ($this->isWithinBase() && $this->isWithinMaximumDepth())
                    ? new PendingUrl($this)
                    : new SkippedUrl($this)
            );
        }

        return $this->scope->getQueue()->getUrl($this);
    }
}
