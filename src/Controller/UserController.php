<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'app_users', methods: ['GET'])]
    #[IsGranted('view_users', message: "Accès refusé !")] 
    public function getUsersByCustomer(
        UserRepository $userRepository,
        Request $request,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        /** @var \App\Entity\Customer $customer */
        $customer = $this->getUser();

        // Récupérer les paramètres de pagination
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);

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
    
        return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/user/{id}', name: 'app_user_details', methods: ['GET'])]
    #[IsGranted('view_user_details', subject: 'user', message: "Accès refusé !")] 
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
    
        return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/user', name: 'app_user_create', methods: ['POST'])]
    #[IsGranted('create_user', message: "Vous n'avez pas l'autorisation de créer un utilisateur !")]
    public function createUser(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator) 
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $errorsString = [];
            foreach ($errors as $error) {
                $errorsString[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsString], JsonResponse::HTTP_BAD_REQUEST);
        }

        $customer = $this->getUser();

        $user->setCustomer($customer);

        $em->persist($user);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getUsers']);

        $json = $serializer->serialize($user, 'json', $context);

        return new JsonResponse($json, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route('/api/user/{id}', name: 'app_user_delete', methods:['DELETE'])]
    #[IsGranted('delete_user', subject: 'user', message: "Vous n'avez pas l'autorisation de supprimer cet utilisateur !")]
    public function deleteUser(User $user, EntityManagerInterface $em)
    {
        $em->remove($user);
        $em->flush();
        
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/user/{id}', name: 'app_user_update', methods: ['PUT'])]
    #[IsGranted('update_user', subject: 'user', message: "Vous n'avez pas l'autorisation de modifier cet utilisateur !")]
    public function updateUser(
        Request $request,
        User $user,
        SymfonySerializerInterface $serializer, 
        EntityManagerInterface $em,
        ValidatorInterface $validator) 
        {
        $serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $user]
        );

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $errorsString = [];
            foreach ($errors as $error) {
                $errorsString[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsString], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->flush();
    
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
