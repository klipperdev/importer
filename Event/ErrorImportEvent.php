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

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ErrorImportEvent extends AbstractImportEvent
{
    private string $message;

    private ?\Throwable $exception;

    public function __construct(
        string $pipelineName,
        ContextInterface $context,
        string $message,
        ?\Throwable $exception = null
    ) {
        parent::__construct($pipelineName, $context);

        $this->message = $message;
        $this->exception = $exception;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getThrowable(): ?\Throwable
    {
        return $this->exception;
    }
}
