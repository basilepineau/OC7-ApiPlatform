<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Groups;
use Hateoas\Configuration\Annotation as Hateoas;
use ApiPlatform\Metadata\ApiResource;
use App\DataProvider\ProductDataProvider;

#[ApiResource(
    normalizationContext: ['groups' => ['getProducts']],
    denormalizationContext: ['groups' => ['getProducts']],
    provider: ProductDataProvider::class
)]

#[Hateoas\Relation(
    'self',
    href: new Hateoas\Route(
        'app_product_details',
        parameters: ['id' => 'expr(object.getId())']
    ),
    exclusion: new Hateoas\Exclusion(groups:["getProducts"])
)]

#[Hateoas\Relation(
    "collection",
    href: new Hateoas\Route("app_products"),
    exclusion: new Hateoas\Exclusion(groups:["getProducts"])
)]

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getProducts'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getProducts'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'getProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['getProducts'])]
    private ?string $price = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getProducts'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getProducts'])]
    private ?string $brand = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }
}
