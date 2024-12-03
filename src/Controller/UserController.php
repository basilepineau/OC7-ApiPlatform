<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'app_user_create', methods:['POST'])]
    public function createUser(Request $request, EntityManagerInterface $em, CustomerRepository $customerRepository, SerializerInterface $serializer) 
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $content = $request->toArray();

        $idCustomer = $content['customerId'] ?? -1;
        $user->setCustomer($customerRepository->find($idCustomer));

        $em->persist($user);
        $em->flush();

        $json = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/user/{id}', name: 'app_user_delete', methods:['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $customer = $tokenStorage->getToken()?->getUser();

        if ($user->getCustomer()?->getId() !== $customer->getId()) {
            return new JsonResponse(['message' => 'Unauthorized : this user does not belong to you'], Response::HTTP_FORBIDDEN);
        }
     
        $em->remove($user);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
