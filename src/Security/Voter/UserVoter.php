<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const VIEW_USERS = 'view_users';
    public const VIEW_USER_DETAILS = 'view_user_details';
    public const CREATE_USER = 'create_user';
    public const DELETE_USER = 'delete_user';
    public const UPDATE_USER = 'update_user';

    protected function supports(string $attribute, $subject): bool
    {
        // Gérer les permissions globales et spécifiques
        return in_array(
            $attribute, [
                self::VIEW_USERS,
                self::VIEW_USER_DETAILS,
                self::CREATE_USER,
                self::DELETE_USER,
                self::UPDATE_USER
            ], true)
            && ($subject === null || $subject instanceof User);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Vérifiez si l'utilisateur est authentifié
        if (!$user instanceof \App\Entity\Customer) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW_USERS:
                return true;
        
            case self::VIEW_USER_DETAILS:
                return $subject instanceof User && $subject->getCustomer() === $user;
        
            case self::CREATE_USER:
                return true;

            case self::DELETE_USER:
                return $subject instanceof User && $subject->getCustomer() === $user;

            case self::UPDATE_USER:
                return $subject instanceof User && $subject->getCustomer() === $user;

            default :
                return false;
        }
        
    }
}
