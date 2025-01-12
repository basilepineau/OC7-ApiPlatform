<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ProductDataProvider implements ProviderInterface
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
                ->getRepository(Product::class)
                ->findOneBy(['id' => $uriVariables['id'], 'customer' => $user]);
        }

        // Récupération d'une collection
        return $this->entityManager
            ->getRepository(Product::class)
            ->findBy(['customer' => $user]);
    }
}
