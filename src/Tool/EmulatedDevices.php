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

final class EmulatedDevices
{
    protected const RESOURCE_FILE = 'emulated-devices.json';
    protected const JSON_SOURCE_URL = 'https://raw.githubusercontent.com/DevExpress/device-specs/master/emulated-devices.json';

    /**
     * @return array[]
     */
    public static function iterateDevices(bool $all = false): \Generator
    {
        foreach (self::getData() as $identifier => $device) {
            if ($all || ($device['show-by-default'] ?? false)) {
                yield $identifier => $device;
            }
        }
    }

    /**
     * @return string[]
     */
    public static function iterateDeviceIdentifier(bool $all = false): \Generator
    {
        foreach (self::iterateDevices($all) as $device) {
            yield $device['identifier'];
        }
    }

    /**
     * @return string[]
     */
    public static function iterateDeviceNames(bool $all = false): \Generator
    {
        foreach (self::iterateDevices($all) as $device) {
            yield $device['title'];
        }
    }

    /**
     * @return string[]
     */
    public static function iterateViewports(bool $all = false, array $orientations = ['horizontal', 'vertical']): \Generator
    {
        foreach (self::iterateDevices($all) as $device) {
            foreach ($orientations as $orientation) {
                if (!isset($device['screen'][$orientation])) {
                    continue;
                }
                yield sprintf(
                    '%dx%d',
                    $device['screen'][$orientation]['width'],
                    $device['screen'][$orientation]['height']
                );
            }
        }
    }

    /**
     * @return string[]
     */
    public static function iterateScreens(bool $all = false, array $orientations = ['horizontal', 'vertical']): \Generator
    {
        foreach (self::iterateDevices($all) as $device) {
            foreach ($orientations as $orientation) {
                if (!isset($device['screen'][$orientation])) {
                    continue;
                }
                $ratio = ($device['screen']['device-pixel-ratio'] ?? 1);
                yield sprintf(
                    '%dx%d',
                    $device['screen'][$orientation]['width'] * $ratio,
                    $device['screen'][$orientation]['height'] * $ratio
                );
            }
        }
    }

    protected static array $data;

    public static function getData(): array
    {
        if (isset(self::$data)) {
            return self::$data;
        }

        return self::$data = json_decode(file_get_contents(self::RESOURCE_FILE, true), true) ?: [];
    }

    public static function updateResource(): void
    {
        $resourcesPath = ScriptLoader::getResourcePath();
        if (false === $resourcesPath) {
            throw new \RuntimeException('Resource path does not exist or is inaccessible');
        }

        $url = self::JSON_SOURCE_URL;
        $data = file_get_contents($url);
        if (false === $data) {
            throw new \RuntimeException('Could not download emulated devices list from url: ' . $url);
        }

        self::$data = [];
        foreach (json_decode($data, true) as $device) {
            $identifier = $device['identifier'] = preg_replace('/\W/u', '-', strtolower($device['title']));
            self::$data[$identifier] = $device;
        }

        $file = $resourcesPath . \DIRECTORY_SEPARATOR . 'emulated-devices.json';
        if (false === file_put_contents($file, json_encode(self::$data))) {
            throw new \RuntimeException('Could not store emulated devices list in file: ' . $file);
        }
    }
}
