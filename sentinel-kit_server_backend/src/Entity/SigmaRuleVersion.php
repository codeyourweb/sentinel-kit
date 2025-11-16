<?php

namespace App\Entity;

use App\Repository\SigmaRuleVersionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SigmaRuleVersionRepository::class)]
class SigmaRuleVersion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['rule_details'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['rule_details'])]
    private ?string $content = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $hash = null;

    #[ORM\ManyToOne(inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SigmaRule $rule = null;

    #[ORM\Column]
    #[Groups(['rule_details'])]
    private ?\DateTime $createdOn = null;

    public function __construct()
    {
        $this->createdOn = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        $this->setHash(md5($content));

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    private function setHash(string $hash): static
    {
        $this->hash = $hash;

        return $this;
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
