<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Création d'un utilisateur spécifique
        $specificCustomer = new Customer();
        $specificCustomer->setUsername('basile');
        $specificCustomer->setName('Basile');
        $specificCustomer->setCreatedAt(new \DateTime());
        $specificCustomer->setRoles(['ROLE_USER']);
    
        // Hachage du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($specificCustomer, 'basile');
        $specificCustomer->setPassword($hashedPassword);
    
        $manager->persist($specificCustomer);
    
        // Création des produits pour cet utilisateur spécifique
        for ($j = 1; $j <= 50; $j++) {
            $product = new Product();
            $product->setName("Product $j of Basile");
            $product->setPrice($faker->randomFloat(2, 5, 100)); 
            $product->setDescription($faker->sentence);
            $product->setBrand($faker->company);
            $product->setCustomer($specificCustomer); 
    
            $manager->persist($product);
        }
    
        // Création des utilisateurs aléatoires
        for ($i = 2; $i <= 6; $i++) {
            $customer = new Customer();
            $customer->setUsername("customer$i");
            $customer->setName("Customer $i");
            $customer->setCreatedAt(new \DateTime());
            $customer->setRoles(['ROLE_USER']);
    
            $hashedPassword = $this->passwordHasher->hashPassword($customer, 'password');
            $customer->setPassword($hashedPassword);
    
            $manager->persist($customer);
    
            for ($j = 1; $j <= 3; $j++) {
                $product = new Product();
                $product->setName("Product $j of Customer $i");
                $product->setPrice($faker->randomFloat(2, 5, 100)); 
                $product->setDescription($faker->sentence);
                $product->setBrand($faker->company);
                $product->setCustomer($customer);
    
                $manager->persist($product);
            }
    
            for ($k = 1; $k <= 2; $k++) {
                $user = new User();
                $user->setCustomer($customer);
                $user->setEmail("user$k.customer$i@example.com");
                $user->setFirstName("UserFirst$k");
                $user->setLastName("UserLast$k");
                $user->setPhone('123456789');
    
                $manager->persist($user);
            }
        }
    
        $manager->flush();
    }

}
