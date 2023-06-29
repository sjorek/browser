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

namespace Sjorek\Browser\Script;

class Script
{
    public function __construct(protected string $script)
    {
    }

    public function __invoke(Scope $scope): bool
    {
        $script = $this->getScriptPath($scope);
        if (null === $script) {
            return false;
        }

        return (static function (Scope $scope) use ($script): bool {
            $result = include $script;

            return 1 === $result;
        })($scope);
    }

    protected function getScriptPath(Scope $scope): ?string
    {
        $script = $this->script;
        if ('' === pathinfo($script, \PATHINFO_EXTENSION)) {
            $script .= '.php';
        }
        $script = stream_resolve_include_path($script);
        if (false === $script) {
            $scope->getStderr()->error(
                implode(
                    \PHP_EOL,
                    array_merge(
                        [
                            sprintf('Given script "%s" does not exist in include-path:', $this->script),
                            '',
                        ],
                        array_map(
                            fn (string $path): string => sprintf(' • %s', $path),
                            explode(\PATH_SEPARATOR, get_include_path() ?: '')
                        )
                    )
                )
            );

            return null;
        }

        return $script;
    }
}
