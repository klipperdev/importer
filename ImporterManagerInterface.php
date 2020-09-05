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

use Klipper\Component\Importer\Exception\InvalidArgumentException;
use Klipper\Component\Importer\Pipeline\PipelineInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface ImporterManagerInterface
{
    /**
     * @return static
     */
    public function addPipeline(PipelineInterface $pipeline);

    public function hasPipeline(string $name): bool;

    /**
     * @throws InvalidArgumentException When the pipeline does not exist
     */
    public function getPipeline(string $name): PipelineInterface;

    /**
     * @return PipelineInterface[]
     */
    public function getPipelines(): array;

    /**
     * @param PipelineInterface[]|string[] $pipelines The pipeline instances or names
     *
     * @return PipelineInterface[]
     */
    public function getStackOfPipelines(array $pipelines): array;

    /**
     * @param PipelineInterface|string $pipeline The pipeline instance or name
     *
     * @throws InvalidArgumentException When the pipeline does not exist
     */
    public function import($pipeline, ContextInterface $context): ImportResultInterface;

    /**
     * @param PipelineInterface[]|string[] $pipelines The pipeline instances or names
     *
     * @throws InvalidArgumentException When the pipeline does not exist
     */
    public function imports(array $pipelines, ContextInterface $context, bool $stopOnError = true): ImportResultListInterface;
}
