<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Importer\Pipeline;

use Klipper\Component\Resource\Domain\DomainManagerInterface;
use Klipper\Component\Resource\ResourceListInterface;
use Psr\Log\LoggerInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface PipelineInterface
{
    /**
     * Get the unique name of the configured pipeline.
     */
    public function getName(): string;

    /**
     * Get the size of the batch.
     */
    public function getBatchSize(): int;

    /**
     * Get the dedicated logger for the pipeline.
     */
    public function getLogger(): ?LoggerInterface;

    /**
     * Extract the data from the source.
     *
     * @return array[] The list of source data
     */
    public function extract(int $cursor, ?\DateTimeInterface $startAt = null): array;

    /**
     * Transform the source data into the resources.
     *
     * @param array[] $sourceData The list of source data
     */
    public function transform(array $sourceData): array;

    /**
     * Load the transformed data into the main database.
     *
     * @param array[] $transformedData The transformed data with field names formatted for the property path
     */
    public function load(DomainManagerInterface $domainManager, array $transformedData): ResourceListInterface;
}
