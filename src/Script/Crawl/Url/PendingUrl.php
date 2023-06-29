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

class PendingUrl extends AbstractImmutableUrl
{
    public function __construct(NewUrl $url)
    {
        parent::__construct($url);
    }

    public function transformToRunning(): RunningUrl
    {
        $url = new RunningUrl($this);
        $this->scope->getQueue()->setUrl($url);

        return $url;
    }
}
