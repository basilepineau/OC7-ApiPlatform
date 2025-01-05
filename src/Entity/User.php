<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
// use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Groups;
use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(
    normalizationContext: ['groups' => ['getUsers']],
    denormalizationContext: ['groups' => ['getUsers']]
)]

#[Hateoas\Relation(
    "collection",
    href: new Hateoas\Route("app_users"),
    exclusion: new Hateoas\Exclusion(groups: ["getUsers"])
)]

#[Hateoas\Relation(
    'self',
    href: new Hateoas\Route(
        'app_user_details',
        parameters: ['id' => 'expr(object.getId())']
    ),
    exclusion: new Hateoas\Exclusion(groups: ["getUsers"])
)]

#[Hateoas\Relation(
    'delete',
    href: new Hateoas\Route(
        'app_user_delete',
        parameters: ['id' => 'expr(object.getId())']
    ),
    exclusion: new Hateoas\Exclusion(groups: ["getUsers"])
)]
#[Hateoas\Relation(
    'update',
    href: new Hateoas\Route(
        'app_user_update',
        parameters: ['id' => 'expr(object.getId())']
    ),
    exclusion: new Hateoas\Exclusion(groups: ["getUsers"])
)]

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getUsers'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le prénom ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Groups(['getUsers'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Groups(['getUsers'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email {{ value }} n'est pas valide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'email ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Groups(['getUsers'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le téléphone est obligatoire.")]
    #[Assert\Length(
        max: 15,
        maxMessage: "Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[0-9+\-\s]+$/",
        message: "Le numéro de téléphone ne doit contenir que des chiffres, espaces, + ou -."
    )]
    #[Groups(['getUsers'])]
    private ?string $phone = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

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
}
