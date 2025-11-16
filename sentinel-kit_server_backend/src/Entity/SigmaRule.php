<?php

namespace App\Entity;

use App\Repository\SigmaRuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SigmaRuleRepository::class)]
class SigmaRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['rule_details'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['rule_details'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['rule_details'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $filename = null;

    #[ORM\Column]
    private ?bool $active = null;

    /**
     * @var Collection<int, SigmaRuleVersion>
     */
    #[ORM\OneToMany(targetEntity: SigmaRuleVersion::class, mappedBy: 'rule', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdOn' => 'DESC'])]
    #[Groups(['rule_details'])]
    private Collection $versions;

    #[ORM\Column]
    #[Groups(['rule_details'])]
    private ?\DateTime $createdOn = null;



    public function __construct()
    {
        $this->versions = new ArrayCollection();
        $this->createdOn = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, SigmaRuleVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(SigmaRuleVersion $version): static
    {
        if (!$this->versions->contains($version)) {
            $this->versions->add($version);
            $version->setRule($this);
        }

        return $this;
    }

    public function removeVersion(SigmaRuleVersion $version): static
    {
        if ($this->versions->removeElement($version)) {
            // set the owning side to null (unless already changed)
            if ($version->getRule() === $this) {
                $version->setRule(null);
            }
        }

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = trim($title);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
