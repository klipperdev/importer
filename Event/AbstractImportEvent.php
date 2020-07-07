<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Importer\Event;

use Klipper\Component\Importer\ContextInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
abstract class AbstractImportEvent extends Event
{
    protected string $pipelineName;

    protected ContextInterface $context;

    public function __construct(string $pipelineName, ContextInterface $context)
    {
        $this->pipelineName = $pipelineName;
        $this->context = $context;
    }

    public function getPipelineName(): string
    {
        return $this->pipelineName;
    }

    public function getContext(): ContextInterface
    {
        return $this->context;
    }
}
