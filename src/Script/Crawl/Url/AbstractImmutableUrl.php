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

abstract class AbstractImmutableUrl extends AbstractUrl
{
    public function __construct(UrlInterface $url)
    {
        $this->setCanonical($url->getCanonical());
        $this->setDepth($url->getDepth());
        $this->scope = $url->getScope();
    }
}
