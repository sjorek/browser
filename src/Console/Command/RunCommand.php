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

class RunCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('run')
            ->setDescription('run browser scripts')
            ->addArgument(
                'scripts',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The scripts to run in given order',
                null,
                $this->suggestScripts()
            )
            // TODO add more help here!
            ->setHelp(
                self::COMMAND_HELP . <<<'HELP'

                        <info>browser run --browser=path/to/executable …</info>

                    HELP
            )
        ;

        parent::configure();
    }

    protected function setup(): int
    {
        $input = $this->input;

        $scripts = $input->getArgument('scripts');
        if ([] === $scripts) {
            $this->stderr->error('Missing scripts to run');
        }

        $this->browserScripts = $scripts;

        return parent::setup();
    }
}
