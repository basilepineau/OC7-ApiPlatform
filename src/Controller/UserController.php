<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class UserController extends AbstractController
{       
    
    /**
     * Cette méthode permet de récupérer l'ensemble des users associés au customer qui exécute la requête.
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des users",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     * 
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     required=true,
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'éléments que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Users")
     * @Security(name="Bearer")
     *
     * @param UserRepository $userRepository
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
    */
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
                    'page' => (int) $page,
                    'limit' => (int) $limit,
                    'pages' => (int) ceil($total / $limit),
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
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator) 
        {

        $newUser = $serializer->deserialize($request->getContent(), User::class, 'json');

        if ($newUser->getFirstName() !== null) {
            $user->setFirstName($newUser->getFirstName());
        }
        if ($newUser->getLastName() !== null) {
            $user->setLastName($newUser->getLastName());
        }
        if ($newUser->getEmail() !== null) {
            $user->setEmail($newUser->getEmail());
        }
        if ($newUser->getPhone() !== null) {
            $user->setPhone($newUser->getPhone());
        }

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $errorsString = [];
            foreach ($errors as $error) {
                $errorsString[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsString], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->flush();

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($user, 'json', $context);

        return new JsonResponse($jsonUser, JsonResponse::HTTP_OK, ['accept' => 'json'], true);
    }
}
