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

namespace Sjorek\Browser\Console\Style;

class SymfonyStyle extends \Symfony\Component\Console\Style\SymfonyStyle
{
    public function getLineLength(): int
    {
        $reflectionObject = new \ReflectionObject($this);
        $reflectionClass = $reflectionObject->getParentClass();

        $reflectionProperty = $reflectionClass->getProperty('lineLength');
        $reflectionProperty->setAccessible(true);

        $lineLength = $reflectionProperty->getValue($this);

        $reflectionProperty->setAccessible(false);

        return $lineLength;
    }
}
