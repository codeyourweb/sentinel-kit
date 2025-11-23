<?php

namespace App\Entity;

use App\Repository\AlertRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?SigmaRule $rule = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?SigmaRuleVersion $sigmaRuleVersion = null;

    #[ORM\Column(length: 128)]
    private ?string $elastic_document = null;

    #[ORM\Column]
    private ?\DateTime $event_createdAt = null;

    #[ORM\Column]
    private ?\DateTime $createdOn = null;

    public function __construct()
    {
        $this->createdOn = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRule(): ?SigmaRule
    {
        return $this->rule;
    }

    public function setRule(?SigmaRule $rule): static
    {
        $this->rule = $rule;

        return $this;
    }

    public function getSigmaRuleVersion(): ?SigmaRuleVersion
    {
        return $this->sigmaRuleVersion;
    }

    public function setSigmaRuleVersion(?SigmaRuleVersion $sigmaRuleVersion): static
    {
        $this->sigmaRuleVersion = $sigmaRuleVersion;

        return $this;
    }

    public function getElasticDocument(): ?string
    {
        return $this->elastic_document;
    }

    public function setElasticDocument(string $elastic_document): static
    {
        $this->elastic_document = $elastic_document;

        return $this;
    }

    public function getEventCreatedAt(): ?\DateTime
    {
        return $this->event_createdAt;
    }

    public function setEventCreatedAt(?\DateTime $event_createdAt): static
    {
        $this->event_createdAt = $event_createdAt;

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
}
