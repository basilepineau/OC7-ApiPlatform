<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserDataProvider implements ProviderInterface
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|object|null
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new \RuntimeException('User not authenticated.');
        }

        // Vérifie si c'est une collection ou un item
        if (!empty($uriVariables['id'])) {
            // Récupération d'un item spécifique
            return $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['id' => $uriVariables['id'], 'customer' => $user]);
        }

        // Récupération d'une collection
        return $this->entityManager
            ->getRepository(User::class)
            ->findBy(['customer' => $user]);
    }
}
