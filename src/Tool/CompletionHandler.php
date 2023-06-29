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

namespace Sjorek\Browser\Tool;

final class CompletionHandler
{
    protected const COMPLETION_DEVICES = 'completion-devices.json';
    protected const COMPLETION_VIEWPORTS = 'completion-viewports.json';
    protected const COMPLETION_SCREENS = 'completion-screens.json';

    public static function getDevices(): array
    {
        return json_decode(file_get_contents(self::COMPLETION_DEVICES, true), true);
    }

    public static function getViewports(): array
    {
        return json_decode(file_get_contents(self::COMPLETION_VIEWPORTS, true), true);
    }

    public static function getScreens(): array
    {
        return json_decode(file_get_contents(self::COMPLETION_SCREENS, true), true);
    }

    public static function updateResources(): void
    {
        $sortDimensions = static function (string $a, string $b): int {
            $a = array_map('intval', explode('x', $a, 2));
            $b = array_map('intval', explode('x', $b, 2));

            return $a <=> $b;
        };

        self::updateResource(self::COMPLETION_DEVICES, EmulatedDevices::iterateDeviceIdentifier(true));
        self::updateResource(self::COMPLETION_VIEWPORTS, EmulatedDevices::iterateViewports(true), $sortDimensions);
        self::updateResource(self::COMPLETION_SCREENS, EmulatedDevices::iterateScreens(true), $sortDimensions);
    }

    protected static function updateResource(string $file, \Traversable $iterator, callable $sort = null): void
    {
        $resourcesPath = ScriptLoader::getResourcePath();
        if (false === $resourcesPath) {
            throw new \RuntimeException('Resource path does not exist or is inaccessible');
        }

        $completion = iterator_to_array($iterator);
        if ($sort) {
            usort($completion, $sort);
        } else {
            sort($completion);
        }
        array_unique($completion);

        $file = $resourcesPath . \DIRECTORY_SEPARATOR . $file;
        if (false === file_put_contents($file, json_encode($completion))) {
            throw new \RuntimeException('Could not store completion-data in file: ' . $file);
        }
    }
}
