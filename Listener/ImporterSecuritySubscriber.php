<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Importer\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Klipper\Component\Importer\Event\PreImportEvent;
use Klipper\Component\Importer\Exception\InvalidArgumentException;
use Klipper\Component\Security\Model\UserInterface;
use Klipper\Component\Security\Organizational\OrganizationalContext;
use Klipper\Component\Security\Token\ConsoleToken;
use Klipper\Component\SecurityExtra\Helper\OrganizationalContextHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ImporterSecuritySubscriber implements EventSubscriberInterface
{
    private EventDispatcherInterface $dispatcher;

    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    private ?OrganizationalContext $orgContext;

    private ?OrganizationalContextHelper $orgContextHelper;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        ?OrganizationalContext $orgContext,
        ?OrganizationalContextHelper $orgContextHelper
    ) {
        $this->dispatcher = $dispatcher;
        $this->em = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->orgContext = $orgContext;
        $this->orgContextHelper = $orgContextHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreImportEvent::class => ['onPreImport', 0],
        ];
    }

    public function onPreImport(PreImportEvent $event): void
    {
        $username = $event->getContext()->getUsername();
        $organization = $event->getContext()->getOrganizationName();

        if (empty($username) && empty($organization)) {
            return;
        }

        if (empty($username) && !empty($organization)) {
            throw new InvalidArgumentException(
                'The username is required if the organization name is defined'
            );
        }

        if (!empty($username)) {
            $this->authUser($username);
        }

        if (null !== $this->orgContext && null !== $this->orgContextHelper && !empty($organization)) {
            $this->authOrganization($organization);
        }
    }

    private function authUser(string $username): void
    {
        $repo = $this->em->getRepository(UserInterface::class);
        /** @var null|UserInterface $user */
        $user = $repo->findOneBy(['username' => $username]);

        if (null === $user) {
            throw new InvalidArgumentException(sprintf(
                'The user with the username "%s" does not exist',
                $username
            ));
        }

        $token = new ConsoleToken('importer', $user, $user->getRoles());

        $this->tokenStorage->setToken($token);
        $this->dispatcher->dispatch(
            new AuthenticationSuccessEvent($token),
            AuthenticationEvents::AUTHENTICATION_SUCCESS
        );
    }

    private function authOrganization(string $organizationName): void
    {
        $this->orgContextHelper->setCurrentOrganizationUser($organizationName);

        if (!$this->orgContext->isOrganization()) {
            throw new InvalidArgumentException(sprintf(
                'The organization with the name "%s" does not exist',
                $organizationName
            ));
        }
    }
}
