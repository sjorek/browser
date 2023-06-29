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

final class ScriptLoader
{
    protected const SCRIPT_PATH = [__DIR__, '..', '..', 'scripts'];
    protected const RESOURCE_PATH = [__DIR__, '..', '..', 'res'];

    public static function getScriptPath(): string|bool
    {
        return realpath(implode(\DIRECTORY_SEPARATOR, self::SCRIPT_PATH));
    }

    public static function getResourcePath(): string|bool
    {
        return realpath(implode(\DIRECTORY_SEPARATOR, self::RESOURCE_PATH));
    }

    public static function initializeIncludePathInAutoloader(): void
    {
        static $includePath;

        if (isset($includePath)) {
            return;
        }

        $includePath = get_include_path();
        if (false === $includePath) {
            return;
        }

        $includePath = array_filter(
            [
                false === getcwd() ? '.' : getcwd(),
                self::getScriptPath(),
                self::getResourcePath(),
                $includePath,
            ],
            fn (string|bool $path): bool => false !== $path && '' !== $path
        );

        if ([] !== $includePath) {
            set_include_path(implode(\PATH_SEPARATOR, $includePath));
        }
    }
}
