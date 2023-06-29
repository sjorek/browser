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

interface UrlInterface
{
    public function getScope(): Scope;

    public function getState(): string;

    public function getCanonical(): string;

    /**
     * @return string[]
     */
    public function getParts(): array;

    public function getPart(string $part): ?string;

    public function getDepth(): int;

    public function __toString(): string;
}
