<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class CustomerController extends AbstractController
{
    #[Route('/api/customer/{id}/users', name: 'app_customer_users', methods: ['GET'])]
    public function getUsersByCustomer(Customer $customer, UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $users = $userRepository->findBy(['customer' => $customer]);
        $json = $serializer->serialize($users, 'json', ['groups' =>'getUsers']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customer/{customerId}/users/{userId}', name: 'app_customer_user', methods: ['GET'])]
    public function getUserByCustomer(int $customerId, int $userId, CustomerRepository $customerRepository, UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $customer = $customerRepository->find($customerId);
        $user = $userRepository->find($userId);
    
        $json = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
    
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

}
