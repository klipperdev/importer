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

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface CleanableLoadedDataPipelineInterface extends PipelineInterface
{
    public function cleanLoadedData(DomainManagerInterface $domainManager, ?\DateTimeInterface $startAt): void;
}
