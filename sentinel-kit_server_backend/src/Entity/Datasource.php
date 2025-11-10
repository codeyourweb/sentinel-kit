<?php

namespace App\Entity;

use App\Repository\DatasourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DatasourceRepository::class)]
class Datasource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $ingestKey = null;

    #[ORM\Column(length: 128)]
    private ?string $targetIndex = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $validFrom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $validTo = null;

    #[ORM\Column]
    private ?\DateTime $createdOn = null;

    private function guidv4($data = null) {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return base64_encode(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
    }

    public function __construct()
    {
        $this->createdOn = new \DateTime();
        $this->ingestKey = $this->guidv4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getIngestKey(): ?string
    {
        return $this->ingestKey;
    }

    public function setIngestKey(string $ingestKey): static
    {
        $this->ingestKey = $ingestKey;

        return $this;
    }

    public function getTargetIndex(): ?string
    {
        return $this->targetIndex;
    }

    public function setTargetIndex(string $targetIndex): static
    {
        $this->targetIndex = $targetIndex;

        return $this;
    }

    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    public function setValidFrom(?\DateTime $validFrom): static
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidTo(): ?\DateTime
    {
        return $this->validTo;
    }

    public function setValidTo(?\DateTime $validTo): static
    {
        $this->validTo = $validTo;

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
