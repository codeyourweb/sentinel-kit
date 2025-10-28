<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AgentRepository;
use App\Model\ServerToken;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgentRepository::class)]
#[ApiResource()]
class Agent extends ServerToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $hostname = null;

    #[ORM\Column(length: 255)]
    private ?string $osName = null;

    #[ORM\Column(length: 255)]
    private ?string $osVersion = null;

    #[ORM\Column(length: 255)]
    private ?string $clientToken = null;

    #[ORM\Column]
    private ?\DateTime $createdOn = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedOn = null;

    public function __construct()
    {
        $this->createdOn = new \DateTime();
        $this->updatedOn = null;
        $this->clientToken = bin2hex(random_bytes(64));
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    public function setHostname(string $hostname): static
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function getOsName(): ?string
    {
        return $this->osName;
    }

    public function setOsName(string $osName): static
    {
        $this->osName = $osName;

        return $this;
    }

    public function getOsVersion(): ?string
    {
        return $this->osVersion;
    }

    public function setOsVersion(string $osVersion): static
    {
        $this->osVersion = $osVersion;

        return $this;
    }

    public function getClientToken(): ?string
    {
        return $this->clientToken;
    }

    public function getCreatedOn(): ?\DateTime
    {
        return $this->createdOn;
    }

    public function getUpdatedOn(): ?\DateTime
    {
        return $this->updatedOn;
    }
}
