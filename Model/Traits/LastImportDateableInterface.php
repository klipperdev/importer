<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Importer\Model\Traits;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface LastImportDateableInterface
{
    /**
     * @return string[]
     */
    public function getLastImportDateNames(): array;

    /**
     * @param array|\DateTimeInterface[] $services The map of service names and date times
     */
    public function setLastImportDates(array $services): void;

    public function getLastImportDates(): array;

    public function hasLastImportDate(string $service): bool;

    public function getLastImportDate(string $service): ?\DateTimeInterface;

    public function addLastImportDate(string $service, \DateTimeInterface $datetime): void;

    public function removeLastImportDate(string $service): void;
}
