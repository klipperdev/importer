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

/**
 * Check if the pipeline support the batch.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface BatchablePipelineInterface extends PipelineInterface
{
    /**
     * Get the size of the batch.
     */
    public function getBatchSize(): int;
}
