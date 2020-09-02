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

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface ImportResultListInterface
{
    public function isSkipped(): bool;

    public function isSuccess(): bool;

    public function getCountErrors(): int;

    /**
     * @return ImportResultInterface[]
     */
    public function getResults(): array;
}
