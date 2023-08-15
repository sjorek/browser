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
use HeadlessChromium\Page;
use Sjorek\Browser\Script\Crawl\Scope;
use Sjorek\Browser\Script\ScopeInterface;
use Sjorek\Browser\Script\Script;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CrawlCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('crawl')
            ->setDescription('crawl website')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'The url to crawl'
            )
            ->addOption(
                'depth',
                'd',
                InputOption::VALUE_REQUIRED,
                'The depth to crawl',
                0
            )
            ->addOption(
                'script',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Run given script, after loading an url',
                null,
                $this->suggestScripts()
            )
            ->addOption(
                'before-script',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Run given script, before any other scripts',
                null,
                $this->suggestScripts()
            )
            ->addOption(
                'after-script',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Run given script, after any other scripts',
                null,
                $this->suggestScripts()
            )
            // TODO add more help here!
            ->setHelp(
                self::COMMAND_HELP . <<<'HELP'

                        <info>browser crawl --browser=path/to/executable …</info>

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

        $depth = filter_var(
            $input->getOption('depth'),
            \FILTER_VALIDATE_INT,
            [
                'flags' => \FILTER_NULL_ON_FAILURE,
                'options' => [
                    'min_range' => 0,
                ],
            ]
        );
        if (null === $depth) {
            $this->stderr->error('Invalid depth given: ' . $input->getOption('depth'));

            return self::FAILURE;
        }
        $input->setOption('depth', $depth);

        $this->browserScripts = array_merge(
            $input->getOption('before-script'),
            [
                'crawl/show-queue',
                'crawl/open-url-and-await-navigation',
            ],
            $input->getOption('script'),
            [
                'crawl/collect-anchor-urls',
            ],
            $input->getOption('after-script'),
        );

        return parent::setup();
    }

    protected function createScope(Browser $browser, Page $page): ScopeInterface
    {
        return new Scope($this->input->getArgument('url'), $browser, $page, $this->input, $this->output, $this->stdout, $this->stderr);
    }

    /**
     * @return Script[]
     */
    protected function iterateBrowserScripts(Browser $browser, Page $page, ScopeInterface $scope): \Generator
    {
        if (!$scope instanceof Scope) {
            throw new \InvalidArgumentException('Invalid scope given');
        }

        $scripts = $this->browserScripts;
        while ($scope->getQueue()->hasPending()) {
            if ($scope->getQueue()->isRunning()) {
                throw new \RuntimeException('The queue must not be running');
            }

            $url = $scope->getQueue()->getPending()->transformToRunning();
            $scope->setUrl($url);

            foreach ($scripts as $script) {
                yield new Script($script);
            }

            $scope->setUrl($url->transformToDone());
        }
    }
}
