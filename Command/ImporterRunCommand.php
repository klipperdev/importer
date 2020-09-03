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

use Klipper\Component\Importer\Context;
use Klipper\Component\Importer\ImporterManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ImporterRunCommand extends Command
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
            ->setName('importer:run')
            ->setDescription('Import data from a selected pipeline')
            ->addArgument('pipeline', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The unique names of pipelines')
            ->addOption('start-at', 'S', InputOption::VALUE_OPTIONAL, 'The ISO datetime')
            ->addOption('username', 'U', InputOption::VALUE_OPTIONAL, 'The username of User used to the import')
            ->addOption('organization', 'O', InputOption::VALUE_OPTIONAL, 'The name of Organization used to the import')
            ->addOption('auto-commit', 'A', InputOption::VALUE_NONE, 'Check if transactional mode or auto commit must be used, dy default')
            ->addOption('no-stop-on-error', '', InputOption::VALUE_NONE, 'Run all pipelines even if previous pipelines has errors')
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $pipelines = $input->getArgument('pipeline');
        $startAt = $input->getOption('start-at');
        $username = $input->getOption('username');
        $organization = $input->getOption('organization');
        $autoCommit = $input->getOption('auto-commit');
        $stopOnError = !$input->getOption('no-stop-on-error');

        try {
            $startAt = !empty($startAt) ? new \DateTime($startAt) : null;
        } catch (\Throwable $e) {
            $style->error('The "start-at" option value is not a valid datetime');

            return 1;
        }

        $results = $this->importerManager->imports(
            $pipelines,
            new Context($username, $organization, $startAt, $autoCommit),
            $stopOnError
        );

        foreach ($results->getResults() as $res) {
            $pipeline = $res->getPipelineName();

            if ($res->isSkipped()) {
                if (\count($pipelines) > 1) {
                    $style->error(sprintf(
                        'Import data from the "%s" pipeline is skipped because of the previous error',
                        $pipeline
                    ));
                } else {
                    $style->note(sprintf(
                        'Import data from the "%s" pipeline is already being processed',
                        $pipeline
                    ));
                }
            } elseif ($res->isSuccess()) {
                $style->success(sprintf(
                    'Import data from the "%s" pipeline is finished with successfully',
                    $pipeline
                ));
            } else {
                $style->error(sprintf(
                    'Import data from the "%s" pipeline is finished with %s error(s)',
                    $pipeline,
                    $res->getCountErrors()
                ));
            }
        }

        return $results->isSuccess() ? 0 : 1;
    }
}
