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
class PostImportEvent extends AbstractImportEvent
{
    private int $errors;

    public function __construct(
        string $pipelineName,
        ContextInterface $context,
        int $errors
    ) {
        parent::__construct($pipelineName, $context);

        $this->errors = $errors;
    }

    public function isSuccess(): bool
    {
        return 0 === $this->errors;
    }

    public function getCountErrors(): int
    {
        return $this->errors;
    }
}
