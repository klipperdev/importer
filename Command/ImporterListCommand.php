<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Importer\Command;

use Klipper\Component\Importer\ImporterManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ImporterListCommand extends Command
{
    private ImporterManagerInterface $importerManager;

    public function __construct(ImporterManagerInterface $importerManager)
    {
        parent::__construct();

        $this->importerManager = $importerManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('importer:list')
            ->setDescription('List all available pipelines for the importation')
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $names = array_keys($this->importerManager->getPipelines());

        if (!empty($names)) {
            $style->writeln([
                '',
                'Available pipelines:',
                '',
            ]);
            $style->listing($names);
        } else {
            $style->warning('No pipeline is configured');
        }

        return 0;
    }
}
