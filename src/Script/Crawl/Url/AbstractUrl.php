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

use Sjorek\Browser\Script\Crawl\Scope;

abstract class AbstractUrl implements UrlInterface
{
    protected string $url;
    protected array $parts;
    protected int $depth;
    protected Scope $scope;

    public function __construct(string $url, Scope $scope)
    {
        $this->scope = $scope;
        $this->setCanonical($url);

        $path = trim($this->getPart('path') ?? '', '/');
        $this->setDepth('' === $path ? 0 : (substr_count($path, '/') + 1));
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function getState(): string
    {
        $className = static::class;

        return strtolower(substr($className, strrpos($className, '\\') + 1, -3));
    }

    protected function setCanonical(string $url): void
    {
        $this->url = $url;
        $this->parts = parse_url($url);
    }

    public function getCanonical(): string
    {
        return $this->url;
    }

    /**
     * @return string[]
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    public function getPart(string $part): ?string
    {
        return $this->getParts()[$part] ?? null;
    }

    protected function setDepth(int $depth): void
    {
        $this->depth = $depth;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function __toString(): string
    {
        return sprintf('%s [%s]', $this->getCanonical(), $this->getState());
    }
}
