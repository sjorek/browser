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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ScreenshotCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('screenshot')
            ->setDescription('screenshot a website')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'The url to take screenshot from'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'The file.png to safe the screenshot to'
            )
            ->addOption(
                'script',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Run given script, before taking the screenshot',
                null,
                $this->suggestScripts()
            )
            // TODO add more help here!
            ->setHelp(
                self::COMMAND_HELP . <<<'HELP'

                        <info>browser screenshot --browser=path/to/executable …</info>

                    HELP
            )
        ;

        parent::configure();
    }

    protected function setup(): int
    {
        $input = $this->input;

        $url = filter_var(
            $input->getArgument('url'),
            \FILTER_VALIDATE_URL,
            \FILTER_NULL_ON_FAILURE
        );
        if (null === $url) {
            $this->stderr->error('Invalid url given: ' . $input->getArgument('url'));

            return self::FAILURE;
        }

        $file = $input->getArgument('file');
        if (file_exists($file)) {
            $this->stderr->error('The given file already exists: ' . $file);

            return self::FAILURE;
        }

        $extension = pathinfo($file, \PATHINFO_EXTENSION);
        if ('png' !== strtolower($extension)) {
            $this->stderr->error('Only png-files are supported: ' . $file);

            return self::FAILURE;
        }

        $directory = pathinfo($file, \PATHINFO_DIRNAME);
        if (!is_dir($directory)) {
            $this->stderr->error('The folder to save the file to does not exist: ' . $directory);

            return self::FAILURE;
        }

        $scripts = $input->getOption('script');
        array_unshift($scripts, 'screenshot/open-url-and-await-navigation');
        $scripts[] = 'screenshot/fullpage-and-save-to-file';

        $this->browserScripts = $scripts;

        return parent::setup();
    }
}
