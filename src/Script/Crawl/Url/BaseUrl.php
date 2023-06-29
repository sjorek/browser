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

class BaseUrl extends AbstractUrl
{
    public function setCanonical(string $url): void
    {
        $url = filter_var($url, \FILTER_VALIDATE_URL, \FILTER_NULL_ON_FAILURE);
        if (null === $url) {
            throw new \InvalidArgumentException('Invalid base url given: ' . $url);
        }
        if (!(str_starts_with($url, 'http://') || str_starts_with($url, 'https://'))) {
            throw new \InvalidArgumentException('Invalid protocol-scheme in base url: ' . $url);
        }
        parent::setCanonical($url);
    }
}
