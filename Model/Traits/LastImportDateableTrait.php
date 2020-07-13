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

use Doctrine\ORM\Mapping as ORM;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
trait LastImportDateableTrait
{
    /**
     * @ORM\Column(type="json")
     */
    protected ?array $lastImportDates = [];

    /**
     * @return string[]
     *
     * @see LastImportAtableInterface::getLastImportDateNames()
     */
    public function getLastImportDateNames(): array
    {
        return array_keys($this->lastImportDates ?? []);
    }

    /**
     * @see LastImportAtableInterface::setLastImportDates()
     */
    public function setLastImportDates(array $services): void
    {
        foreach ($services as $service => $dateTime) {
            if (null === $dateTime) {
                $this->removeLastImportDate($service);
            } else {
                $this->addLastImportDate($service, $dateTime);
            }
        }
    }

    /**
     * @see LastImportAtableInterface::getLastImportDates()
     */
    public function getLastImportDates(): array
    {
        $values = [];

        foreach ($this->getLastImportDateNames() as $service) {
            $values[$service] = $this->getLastImportDate($service);
        }

        return $values;
    }

    /**
     * @see LastImportAtableInterface::hasLastImportDate()
     */
    public function hasLastImportDate(string $service): bool
    {
        return isset($this->lastImportDates[$service]);
    }

    /**
     * @see LastImportAtableInterface::getLagetLastImportDate()
     */
    public function getLastImportDate(string $service): ?\DateTimeInterface
    {
        if (isset($this->lastImportDates[$service])) {
            $date = \DateTimeImmutable::createFromFormat(
                \DateTime::ATOM,
                $this->lastImportDates[$service]
            );

            return $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }

        return null;
    }

    /**
     * @see LastImportAtableInterface::addLastImportDate()
     */
    public function addLastImportDate(string $service, \DateTimeInterface $datetime): void
    {
        $this->lastImportDates[$service] = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $datetime->format(\DateTime::ATOM)
        )
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(\DateTime::ATOM)
        ;
    }

    /**
     * @see LastImportAtableInterface::removeLastImportDate()
     */
    public function removeLastImportDate(string $service): void
    {
        unset($this->lastImportDates[$service]);
    }

    public function clearLastImportDates(): void
    {
        $this->lastImportDates = [];
    }
}
