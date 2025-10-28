<?php

namespace App\Entity;

use App\Repository\UserJWTRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserJWTRepository::class)]
class UserJWT
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userJWT')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTime $createdOn = null;

    #[ORM\Column(length: 32)]
    private ?string $uniqueId = null;

    #[ORM\Column]
    private ?int $remainingTry = null;

    public function __construct()
    {
        $this->createdOn = new \DateTime();
        $this->remainingTry = 3;
        $this->uniqueId = md5(uniqid(mt_rand(), true));
    }

    public function isExpired(): bool
    {
        $now = new \DateTime();
        $interval = $now->getTimestamp() - $this->createdOn->getTimestamp();
        return $interval > 300;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedOn(): ?\DateTime
    {
        return $this->createdOn;
    }

    public function setCreatedOn(\DateTime $createdOn): static
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function getUniqueId(): ?string
    {
        return $this->uniqueId;
    }

    public function setUniqueId(string $uniqueId): static
    {
        $this->uniqueId = $uniqueId;

        return $this;
    }

    public function getRemainingTry(): ?int
    {
        return $this->remainingTry;
    }

    public function setRemainingTry(int $remainingTry): static
    {
        $this->remainingTry = $remainingTry;

        return $this;
    }
}
