<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Importer;

use Klipper\Component\Importer\Event\ErrorImportEvent;
use Klipper\Component\Importer\Event\PartialImportEvent;
use Klipper\Component\Importer\Event\PostImportEvent;
use Klipper\Component\Importer\Event\PreImportEvent;
use Klipper\Component\Importer\Exception\InvalidArgumentException;
use Klipper\Component\Importer\Pipeline\CleanableLoadedDataPipelineInterface;
use Klipper\Component\Importer\Pipeline\IncrementablePipelineInterface;
use Klipper\Component\Importer\Pipeline\LoggablePipelineInterface;
use Klipper\Component\Importer\Pipeline\PipelineInterface;
use Klipper\Component\Importer\Pipeline\RequiredOrganizationPipelineInterface;
use Klipper\Component\Importer\Pipeline\RequiredUserPipelineInterface;
use Klipper\Component\Resource\Domain\DomainManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Lock\LockFactory;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ImporterManager implements ImporterManagerInterface
{
    private LockFactory $lockFactory;

    private DomainManagerInterface $domainManager;

    private EventDispatcherInterface $dispatcher;

    private LoggerInterface $logger;

    /**
     * @var PipelineInterface[]
     */
    private array $pipelines = [];

    /**
     * @param PipelineInterface[] $pipelines The configured pipelines
     */
    public function __construct(
        LockFactory $lockFactory,
        DomainManagerInterface $domainManager,
        EventDispatcherInterface $dispatcher,
        array $pipelines = [],
        ?LoggerInterface $logger = null
    ) {
        $this->lockFactory = $lockFactory;
        $this->domainManager = $domainManager;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger ?? new NullLogger();

        foreach ($pipelines as $pipeline) {
            $this->pipelines[$pipeline->getName()] = $pipeline;
        }
    }

    public function addPipeline(PipelineInterface $pipeline)
    {
        $this->pipelines[$pipeline->getName()] = $pipeline;

        return $this;
    }

    public function hasPipeline(string $name): bool
    {
        return isset($this->pipelines[$name]);
    }

    /**
     * @throws InvalidArgumentException When the pipeline does not exist
     */
    public function getPipeline(string $name): PipelineInterface
    {
        if ($this->hasPipeline($name)) {
            return $this->pipelines[$name];
        }

        throw new InvalidArgumentException(sprintf(
            'The importer pipeline "%s" does not exit',
            $name
        ));
    }

    public function getPipelines(): array
    {
        return $this->pipelines;
    }

    public function import($pipeline, ContextInterface $context): ImportResultInterface
    {
        set_time_limit(0);
        $startedTime = microtime(true);
        $errors = 0;
        $lock = null;
        $pipelineName = $pipeline instanceof PipelineInterface
            ? $pipeline->getName()
            : (string) $pipeline;

        try {
            if (!$pipeline instanceof PipelineInterface) {
                $pipeline = $this->getPipeline($pipelineName);
            }

            if ($pipeline instanceof RequiredUserPipelineInterface) {
                $context->setUsername($pipeline->getUsername());
            }

            if ($pipeline instanceof RequiredOrganizationPipelineInterface) {
                $context->setOrganizationName($pipeline->getOrganizationName());
            }

            $this->dispatcher->dispatch(new PreImportEvent($pipelineName, $context));
            $startAt = $context->getStartAt();

            $lock = $this->lockFactory->createLock('importer:'.$pipeline->getName());

            if (!$lock->acquire()) {
                return new ImportResult(null);
            }

            if (null !== $startAt && !$pipeline instanceof IncrementablePipelineInterface) {
                throw new InvalidArgumentException(sprintf(
                    'The "%s" pipeline does not support the incremental import',
                    $pipelineName
                ));
            }

            $errors += $this->doImport($pipeline, $context);
            $duration = round((microtime(true) - $startedTime) * 10) / 10;

            if (0 === $errors) {
                if ($pipeline instanceof CleanableLoadedDataPipelineInterface) {
                    try {
                        $pipeline->cleanLoadedData($this->domainManager, $startAt);
                    } catch (\Throwable $e) {
                        ++$errors;
                        $msg = sprintf(
                            'Cleaning data after import from the "%s" pipeline is finished with an error: '.$e->getMessage(),
                            $pipeline->getName(),
                        );
                        $this->dispatcher->dispatch(new ErrorImportEvent($pipelineName, $context, $msg, $e));
                        $this->getLogger($pipeline)->error(
                            $msg,
                            ['importer_pipeline' => $pipeline->getName(), 'exception' => $e],
                        );
                    }
                }

                if (0 === $errors) {
                    $this->getLogger($pipeline)->info(sprintf(
                        'Import data from the "%s" pipeline is finished with successfully in %s s',
                        $pipeline->getName(),
                        $duration
                    ), ['importer_pipeline' => $pipeline->getName(), 'duration' => $duration]);
                }
            } else {
                ++$errors;
                $msg = sprintf(
                    'Import data from the "%s" pipeline is finished with an error in %s s',
                    $pipeline->getName(),
                    $duration
                );
                $this->dispatcher->dispatch(new ErrorImportEvent($pipelineName, $context, $msg));
                $this->getLogger($pipeline)->error(
                    $msg,
                    ['importer_pipeline' => $pipeline->getName(), 'duration' => $duration],
                );
            }
        } catch (\Throwable $e) {
            ++$errors;
            $this->dispatcher->dispatch(new ErrorImportEvent($pipelineName, $context, $e->getMessage(), $e));
            $this->getLogger()->critical($e->getMessage(), ['importer_pipeline' => $pipelineName, 'exception' => $e]);
        }

        $this->dispatcher->dispatch(new PostImportEvent($pipelineName, $context, $errors));

        if ($lock) {
            $lock->release();
        }

        return new ImportResult($errors);
    }

    private function getLogger(?PipelineInterface $pipeline = null): LoggerInterface
    {
        if ($pipeline instanceof LoggablePipelineInterface && $logger = $pipeline->getLogger()) {
            return $logger;
        }

        return $this->logger;
    }

    /**
     * @return int The count of error
     */
    private function doImport(PipelineInterface $pipeline, ContextInterface $context): int
    {
        $startAt = $context->getStartAt();
        $cursor = 0;
        $errors = 0;
        $finish = false;

        while (!$finish) {
            $result = $pipeline->extract($cursor, $startAt);
            $finish = empty($result);

            if (!$finish) {
                $result = $pipeline->transform($result);
                $resList = $pipeline->load($this->domainManager, $result);
                $errors += \count($resList->getErrors());

                $this->dispatcher->dispatch(new PartialImportEvent(
                    $pipeline->getName(),
                    $context,
                    $resList
                ));

                ++$cursor;
            }
        }

        return $errors;
    }
}
