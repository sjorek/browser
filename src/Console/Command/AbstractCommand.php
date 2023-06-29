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

namespace Sjorek\Browser\Console\Command;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use Sjorek\Browser\Console\Style\SymfonyStyle;
use Sjorek\Browser\Script\Scope;
use Sjorek\Browser\Script\ScopeInterface;
use Sjorek\Browser\Script\Script;
use Sjorek\Browser\Tool\CompletionHandler;
use Sjorek\Browser\Tool\EmulatedDevices;
use Sjorek\Browser\Tool\ScriptLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class AbstractCommand extends Command
{
    protected SymfonyStyle $stdout;
    protected SymfonyStyle $stderr;

    protected InputInterface $input;
    protected OutputInterface $output;

    protected ?string $browserExecutable = null;
    protected array $browserViewport = [];
    protected array $browserOptions = [];
    protected array $browserScripts = [];

    protected const COMMAND_HELP = <<<'HELP'
        Using different (chrome) browser executable
        ===========================================

        When starting, the factory will look for the environment variable
        "CHROME_PATH" to use as the chrome executable. If the variable is
        not found, it will try to guess the correct executable path according
        to your OS or use "chrome" as the default.

        You are also able to explicitly set up any executable of your choice
        (like chromium-browser instead of chrome) by setting the <info>--browser</info>
        option:

        HELP;

    protected function configure()
    {
        $this
            ->addOption(
                'width',
                null,
                InputOption::VALUE_REQUIRED,
                'The browser width (default: none)'
            )
            ->addOption(
                'height',
                null,
                InputOption::VALUE_REQUIRED,
                'The browser height (default: none)'
            )
            ->addOption(
                'device',
                null,
                InputOption::VALUE_REQUIRED,
                'Set viewport size based upon given device',
                null,
                $this->suggestDevices()
            )
            ->addOption(
                'orientation',
                null,
                InputOption::VALUE_REQUIRED,
                'Set viewport orientation for given device',
                'vertical',
                [
                    'horizontal',
                    'vertical',
                ]
            )
            ->addOption(
                'viewport',
                null,
                InputOption::VALUE_REQUIRED,
                'The viewport size as width x height, ie. 1024x768',
                null,
                $this->suggestViewports()
            )
            ->addOption(
                'headless',
                null,
                InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE,
                'Explictly enable or disable headless mode (default: true)',
            )
            ->addOption(
                'notifications',
                null,
                InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE,
                'Explictly enable or disable browser notifications (default: false)'
            )
            ->addOption(
                'images',
                null,
                InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE,
                'Explictly enable or disable loading of images (default: true)'
            )
            ->addOption(
                'strict',
                null,
                InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE,
                'Explictly enable or disable ignoring ssl errors (default: true)'
            )
            ->addOption(
                'keep-alive',
                null,
                InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE,
                'Explictly enable or disable keep alive of the chrome instance when the script terminates (default: false)'
            )
            ->addOption(
                'sandbox',
                null,
                InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE,
                'Explictly enable or disable sandbox mode, useful to run in a docker container (default: false)'
            )
            ->addOption(
                'proxy',
                null,
                InputOption::VALUE_REQUIRED,
                'Proxy server to use. ex: `127.0.0.1:8080` (default: none)'
            )
            ->addOption(
                'user-agent',
                null,
                InputOption::VALUE_REQUIRED,
                'User agent to use for the whole browser'
            )
            ->addOption(
                'header',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Additional custom HTTP header',
            )
            ->addOption(
                'env',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Additional environment variable to pass to the process (example DISPLAY variable)'
            )
            ->addOption(
                'flag',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Additional command line flag to pass to the execuable'
            )
            ->addOption(
                'sync-timeout',
                null,
                InputOption::VALUE_REQUIRED,
                'Default timeout in seconds for sending sync messages (default: 5 seconds)'
            )
            ->addOption(
                'startup-timeout',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum time in seconds to wait for chrome to start (default: 30 seconds)'
            )
            ->addOption(
                'delay',
                null,
                InputOption::VALUE_REQUIRED,
                'Delay to apply between each operation for debugging purposes (default: none)'
            )
            ->addOption(
                'browser',
                null,
                InputOption::VALUE_REQUIRED,
                'Override the path to the (chrome) browser executable'
            )
            ->addOption(
                'data-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Chrome user data dir (default: a new empty dir is generated temporarily)'
            )
            ->addOption(
                'dump-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'The directory crashpad should store dumps in (crash reporter will be enabled automatically)'
            )
            ->addOption(
                'basic-auth',
                null,
                InputOption::VALUE_REQUIRED,
                'Set basic authorization header formatted as "username":"password"'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $this->stdout = new SymfonyStyle($input, $output);
        $this->stderr = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);

        $result = $this->setup();
        if (self::SUCCESS !== $result) {
            return $result;
        }

        $result = self::SUCCESS;
        try {
            $browser = $this->createBrowser();
            $page = $browser->createPage($browser);
            $scope = $this->createScope($browser, $page);

            foreach ($this->iterateBrowserScripts($browser, $page, $scope) as $script) {
                if (false === $script($scope)) {
                    return self::FAILURE;
                }
            }
        } catch (\Throwable $t) {
            $messages = ['Something went wrong: ' . $t->getMessage()];
            if ($this->stdout->isVerbose()) {
                $messages[] = $t->getTraceAsString();
            }
            $this->stderr->error($messages);
            $result = self::FAILURE;
        } finally {
            // bye
            $browser->close();
        }

        return $result;
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @see InputInterface::bind()
     * @see InputInterface::validate()
     */
    protected function setup(): int
    {
        $input = $this->input;

        $width = $input->getOption('width');
        if (null !== $width) {
            $width = filter_var(
                $width,
                \FILTER_VALIDATE_INT,
                [
                    'flags' => \FILTER_NULL_ON_FAILURE,
                    'options' => [
                        'min_range' => 1,
                    ],
                ]
            );
            if (null === $width) {
                $this->stderr->error('Invalid width given: ' . $input->getOption('width'));

                return self::FAILURE;
            }
        }

        $height = $input->getOption('height');
        if (null !== $height) {
            $height = filter_var(
                $height,
                \FILTER_VALIDATE_INT,
                [
                    'flags' => \FILTER_NULL_ON_FAILURE,
                    'options' => [
                        'min_range' => 1,
                    ],
                ]
            );
            if (null === $height) {
                $this->stderr->error('Invalid height given: ' . $input->getOption('height'));

                return self::FAILURE;
            }
        }

        if (null !== $width && null === $height) {
            $this->stderr->error('Missing height, because only a width has been given: ' . $width);

            return self::FAILURE;
        } elseif (null === $width && null !== $height) {
            $this->stderr->error('Missing width, because only a height has been given: ' . $height);

            return self::FAILURE;
        }

        $orientation = $input->getOption('orientation');
        if (!\in_array($orientation, [null, 'horizontal', 'vertical'], true)) {
            $this->stderr->error('Unknown orientation given: ' . $input->getOption('orientation'));

            return self::FAILURE;
        }

        $device = $input->getOption('device');
        if (null !== $device) {
            $device = EmulatedDevices::getData()[$device] ?? null;
            if (null === $device) {
                $this->stderr->error('Unknown device given: ' . $input->getOption('device'));

                return self::FAILURE;
            }
        }

        $viewport = $input->getOption('viewport');
        if (null !== $viewport) {
            $viewport = array_map(
                fn (string $i): int|null => filter_var(
                    $i,
                    \FILTER_VALIDATE_INT,
                    [
                        'flags' => \FILTER_NULL_ON_FAILURE,
                        'options' => [
                            'min_range' => 1,
                        ],
                    ]
                ),
                explode('x', $viewport, 2)
            );
            if (\in_array(null, $viewport, true)) {
                $this->stderr->error('Invalid viewport given: ' . $input->getOption('viewport'));

                return self::FAILURE;
            }
            $viewport = match ($orientation) {
                'horizontal' => $viewport[0] >= $viewport[1] ? $viewport : array_reverse($viewport),
                'vertical' => $viewport[0] <= $viewport[1] ? $viewport : array_reverse($viewport),
                null => $viewport
            };
        }

        if (null !== $device && null !== $orientation) {
            if (null !== $viewport) {
                $this->stderr->warning('Overiding existing viewport definition with given device\'s viewport');
            }

            $viewport = [$device['screen'][$orientation]['width'], $device['screen'][$orientation]['height']];
        } elseif (null !== $device && null === $orientation) {
            if (null === $viewport) {
                $this->stderr->error('Cannot set viewport for device, as the orientation is missing: ' . $device);

                return self::FAILURE;
            } else {
                $this->stderr->warning(
                    'Skipped overriding existing viewport with given device\'s' . \PHP_EOL .
                    'viewport, as the orientation is missing: ' . $device
                );
            }
        }

        $headless = $input->getOption('headless') ?? true;
        $notifications = $input->getOption('notifications') ?? false;
        $images = $input->getOption('images') ?? true;
        $strict = $input->getOption('strict') ?? true;
        $keepAlive = $input->getOption('keep-alive') ?? false;
        $sandbox = $input->getOption('sandbox') ?? false;

        $proxy = $input->getOption('proxy');
        $userAgent = $input->getOption('user-agent');
        $env = $input->getOption('env') ?: null;
        $flags = $input->getOption('flag') ?: null;

        $delay = $input->getOption('delay');
        if (null !== $delay) {
            $delay = filter_var(
                $delay,
                \FILTER_VALIDATE_FLOAT,
                [
                    'flags' => \FILTER_NULL_ON_FAILURE,
                    'options' => [
                        'decimal' => 1,
                        'min_range' => 0,
                    ],
                ]
            );
            if (null === $delay) {
                $this->stderr->error('Invalid delay given: ' . $input->getOption('delay'));

                return self::FAILURE;
            }
        }

        $syncTimeout = $input->getOption('sync-timeout');
        if (null !== $syncTimeout) {
            $syncTimeout = filter_var(
                $syncTimeout,
                \FILTER_VALIDATE_INT,
                [
                    'flags' => \FILTER_NULL_ON_FAILURE,
                    'options' => [
                        'min_range' => 0,
                    ],
                ]
            );
            if (null === $syncTimeout) {
                $this->stderr->error('Invalid sync-timeout given: ' . $input->getOption('sync-timeout'));

                return self::FAILURE;
            }
            $syncTimeout *= 1000;
        }

        $startupTimeout = $input->getOption('startup-timeout');
        if (null !== $startupTimeout) {
            $startupTimeout = filter_var(
                $startupTimeout,
                \FILTER_VALIDATE_INT,
                [
                    'flags' => \FILTER_NULL_ON_FAILURE,
                    'options' => [
                        'min_range' => 0,
                    ],
                ]
            );
            if (null === $startupTimeout) {
                $this->stderr->error('Invalid startup-timeout given: ' . $input->getOption('startup-timeout'));

                return self::FAILURE;
            }
        }

        $dataDir = $input->getOption('data-dir');
        if (null !== $dataDir && !is_dir($dataDir)) {
            $this->stderr->error('Given data-directory does not exist: ' . $dataDir);

            return self::FAILURE;
        }

        $dumpDir = $input->getOption('dump-dir');
        if (null !== $dumpDir && !is_dir($dumpDir)) {
            $this->stderr->error('Given dump-directory does not exist: ' . $dumpDir);

            return self::FAILURE;
        }

        $headers = $input->getOption('header') ?: null;
        if (null !== $headers) {
            $headerMap = [];
            foreach ($headers as $header) {
                if (!str_contains($header, ':')) {
                    $this->stderr->error('Invalid header given: ' . $header);

                    return self::FAILURE;
                }

                [$key, $value] = explode(':', $header, 2);
                $key = strtolower(trim($key));
                if (isset($headerMap[$key])) {
                    $this->stderr->error('Duplicate header given: ' . $header);

                    return self::FAILURE;
                }
                $headerMap[$key] = $value;
            }
            $headers = $headerMap;
            unset($headerMap);
        }

        $this->browserExecutable = $input->getOption('browser');
        $this->browserViewport = $viewport ?? [];
        $this->browserOptions = array_filter(
            [
                'connectionDelay' => $delay,
                'customFlags' => $flags,
                'debugLogger' => $this->stdout->isDebug() ? 'php://stdout' : null,
                'disableNotifications' => false === $notifications,
                'enableImages' => $images,
                'envVariables' => $env,
                'headers' => $headers,
                'headless' => $headless,
                'ignoreCertificateErrors' => false === $strict,
                'keepAlive' => $keepAlive,
                'noSandbox' => false === $sandbox,
                'proxyServer' => $proxy,
                'sendSyncDefaultTimeout' => $syncTimeout,
                'startupTimeout' => $startupTimeout,
                'userAgent' => $userAgent,
                'userDataDir' => $dataDir,
                'userCrashDumpsDir' => $dumpDir,
                'windowSize' => isset($width, $height) ? [$width, $height] : null,
            ],
            fn ($value): bool => null !== $value
        );

        if ($this->stdout->isVeryVerbose()) {
            $this->stdout->note('Browser');
            $this->stdout->horizontalTable(
                array_merge(
                    [
                        'executable',
                        'device',
                        'orientation',
                        'viewport',
                    ],
                    array_keys($this->browserOptions)
                ),
                [
                    array_map(
                        fn (mixed $value): string => match (\gettype($value)) {
                            'array' => implode(',', $value),
                            'boolean' => $value ? 'on' : 'off',
                            default => (string) ($value ?? 'default')
                        },
                        array_merge(
                            [
                                $this->browserExecutable,
                                $device ? $device['title'] : null,
                                $orientation,
                                $viewport ? sprintf('%dx%d', ...$viewport) : null,
                            ],
                            array_values($this->browserOptions)
                        )
                    ),
                ]
            );
        }

        return self::SUCCESS;
    }

    /**
     * Create and start browser instance.
     */
    protected function createBrowser(): Browser
    {
        $browserFactory = new BrowserFactory($this->browserExecutable);
        $browserFactory->addOptions($this->browserOptions);

        return $browserFactory->createBrowser();
    }

    protected function createPage(Browser $browser): Page
    {
        $page = $browser->createPage();

        if ([] !== $this->browserViewport) {
            $page->setViewport(...$this->browserViewport)->await();
        }

        return $page;
    }

    protected function createScope(Browser $browser, Page $page): ScopeInterface
    {
        return new Scope($browser, $page, $this->input, $this->output, $this->stdout, $this->stderr);
    }

    /**
     * @return Script[]
     */
    protected function iterateBrowserScripts(Browser $browser, Page $page, ScopeInterface $scope): \Generator
    {
        foreach ($this->browserScripts as $script) {
            yield new Script($script);
        }
    }

    /**
     * Suggest devices, while taking given options in acount.
     */
    protected function suggestDevices(): \Closure
    {
        return function (CompletionInput $input): array {
            $completionValue = $input->getCompletionValue();
            $names = CompletionHandler::getDevices();

            return '' === $completionValue ? $names : array_filter(
                $names,
                fn (string $name): bool => str_starts_with($name, $completionValue)
            );
        };
    }

    /**
     * Suggest device names, while taking given options in acount.
     */
    protected function suggestViewports(): \Closure
    {
        return function (CompletionInput $input): array {
            $completionValue = $input->getCompletionValue();
            $viewports = CompletionHandler::getViewports();
            $orientation = $input->getOption('orientation');
            if (null !== $orientation) {
                $viewports = array_filter(
                    $viewports,
                    fn (string $viewport): bool => match ($orientation) {
                        'horizontal' => (([$w, $h] = explode('x', $viewport, 2)) && $w >= $h),
                        'vertical' => (([$w, $h] = explode('x', $viewport, 2)) && $w <= $h),
                        default => true
                    }
                );
            }

            return '' === $completionValue ? $viewports : array_filter(
                $viewports,
                fn (string $viewport): bool => str_starts_with($viewport, $completionValue)
            );
        };
    }

    /**
     * Suggest device names, while taking given options in acount.
     */
    protected function suggestScripts(): \Closure
    {
        return function (CompletionInput $input): array {
            $scriptPath = ScriptLoader::getScriptPath();
            if (false === $scriptPath) {
                return [];
            }

            $paths = [$scriptPath];
            $currentPath = getcwd();
            if (false !== $currentPath && $currentPath !== $scriptPath) {
                $paths[] = $currentPath;
            }

            $finder = Finder::create()
                ->files()
                ->in($paths)
                ->name('*.php')
                ->sortByName()
            ;

            $completionValue = $input->getCompletionValue();
            if ('' !== $completionValue) {
                $finder = $finder->filter(
                    fn (SplFileInfo $file): bool => str_starts_with($file->getRelativePathname(), $completionValue)
                );
            }

            return array_map(
                fn (SplFileInfo $file): string => ltrim(
                    implode(
                        \DIRECTORY_SEPARATOR,
                        [$file->getRelativePath(), $file->getFilenameWithoutExtension()]
                    ),
                    \DIRECTORY_SEPARATOR
                ),
                iterator_to_array($finder)
            );
        };
    }
}
