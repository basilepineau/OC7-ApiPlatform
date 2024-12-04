<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'app_users', methods: ['GET'])]
    #[IsGranted('view_users')] 
    public function getUsersByCustomer(
        UserRepository $userRepository,
        Request $request,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $customer = $this->getUser();

        // Récupérer les paramètres de pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $context = SerializationContext::create()->setGroups(['getUsers']);

        $cacheKey = sprintf('getUsersByCustomer-customer-%s-page-%s-limit-%s', $customer->getId(), $page, $limit);
        $json = $cachePool->get($cacheKey, function (ItemInterface $item) use ($userRepository, $serializer, $customer, $page, $limit, $context) {
            $item->tag("userCache");
            $item->expiresAfter(3600); // Cache expire après 1h
            $users = $userRepository->findByCustomerWithPagination($customer, $page, $limit);
            $total = $userRepository->countByCustomer($customer);
    
            $data = [
                'data' => $users,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit),
                ],
            ];
    
            // Sérialiser directement les données au format JSON
            return $serializer->serialize($data, 'json', $context);
        });
    
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/user/{id}', name: 'app_user_details', methods: ['GET'])]
    #[IsGranted('view_user_details', subject: 'user', message:"Accès refusé !")] 
    public function getUserDetails(
        User $user,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $cacheKey = sprintf('getUserDetails-user-%s', $user->getId());

        $context = SerializationContext::create()->setGroups(['getUsers']);
    
        $json = $cachePool->get($cacheKey, function (ItemInterface $item) use ($user, $serializer, $context) {
            $item->tag("userCache");
            $item->expiresAfter(3600); // Cache expire après 1h

            return $serializer->serialize($user, 'json', $context);
        });
    
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    // #[Route('/api/users', name: 'app_user_create', methods:['POST'])]
    // public function createUser(Request $request, EntityManagerInterface $em, CustomerRepository $customerRepository, SerializerInterface $serializer) 
    // {
    //     $user = $serializer->deserialize($request->getContent(), User::class, 'json');

    //     $content = $request->toArray();

    //     $idCustomer = $content['customerId'] ?? -1;
    //     $user->setCustomer($customerRepository->find($idCustomer));

    //     $em->persist($user);
    //     $em->flush();

    //     $json = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);

    //     return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    // }

    // #[Route('/api/user/{id}', name: 'app_user_delete', methods:['DELETE'])]
    // public function deleteUser(User $user, EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    // {
    //     $customer = $tokenStorage->getToken()?->getUser();

    //     if ($user->getCustomer()?->getId() !== $customer->getId()) {
    //         return new JsonResponse(['message' => 'Unauthorized : this user does not belong to you'], Response::HTTP_FORBIDDEN);
    //     }
     
    //     $em->remove($user);
    //     $em->flush();
        
    //     return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    // }
}
