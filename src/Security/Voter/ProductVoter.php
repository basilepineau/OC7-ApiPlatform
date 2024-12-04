<?php

namespace App\Security\Voter;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProductVoter extends Voter
{
    public const VIEW_PRODUCTS = 'view_products';
    public const VIEW_PRODUCT_DETAILS = 'view_product_details';

    protected function supports(string $attribute, $subject): bool
    {
        // Gérer les permissions globales et spécifiques
        return in_array($attribute, [self::VIEW_PRODUCTS, self::VIEW_PRODUCT_DETAILS], true)
            && ($subject === null || $subject instanceof Product);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Vérifiez si l'utilisateur est authentifié
        if (!$user instanceof \App\Entity\Customer) {
            return false;
        }

        // Gestion des permissions globales
        if ($attribute === self::VIEW_PRODUCTS) {
            // Logique pour vérifier si l'utilisateur peut voir les produits globalement
            return true;
        }
        
        // Gestion des permissions spécifiques pour VIEW_PRODUCT_DETAILS
        if ($attribute === self::VIEW_PRODUCT_DETAILS && $subject instanceof Product) {
            // Vérifiez si l'utilisateur est propriétaire du produit
            return $subject->getCustomer() === $user;
        }

        return false;
    }
}
