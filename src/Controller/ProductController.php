<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductController extends AbstractController
{
    #[Route('/api/products', name: 'app_products', methods: ['GET'])]
    #[IsGranted('view_products')] 
    public function getProductsByCustomer(
        ProductRepository $productRepository,
        Request $request,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $customer = $this->getUser();

        // Récupérer les paramètres de pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $context = SerializationContext::create()->setGroups(['getProducts']);

        $cacheKey = sprintf('getProductsByCustomer-customer-%s-page-%s-limit-%s', $customer->getId(), $page, $limit);
        $json = $cachePool->get($cacheKey, function (ItemInterface $item) use ($productRepository, $serializer, $customer, $page, $limit, $context) {
            $item->tag("productCache");
            $item->expiresAfter(3600); // Cache expire après 1h
            $products = $productRepository->findByCustomerWithPagination($customer, $page, $limit);
            $total = $productRepository->countByCustomer($customer);
    
            $data = [
                'data' => $products,
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

    #[Route('/api/product/{id}', name: 'app_product_details', methods: ['GET'])]
    #[IsGranted('view_product_details', subject: 'product', message:"Accès refusé !")] 
    public function getProductDetails(
        Product $product,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $cacheKey = sprintf('getProductDetails-product-%s', $product->getId());

        $context = SerializationContext::create()->setGroups(['getProducts']);
    
        $json = $cachePool->get($cacheKey, function (ItemInterface $item) use ($product, $serializer, $context) {
            $item->tag("productCache");
            $item->expiresAfter(3600); // Cache expire après 1h

            return $serializer->serialize($product, 'json', $context);
        });
    
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
    
}
