<?php

namespace App\Entity;

use App\Repository\AgentInformationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgentInformationRepository::class)]
class AgentInformation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $createdOn = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $cpuUsage = null;

    #[ORM\Column(nullable: true)]
    private ?float $memoryTotal = null;

    #[ORM\Column(nullable: true)]
    private ?float $memoryAvailable = null;

    #[ORM\Column(nullable: true)]
    private ?float $systemDiskTotalSize = null;

    #[ORM\Column(nullable: true)]
    private ?float $systemDiskAvailableSize = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $processesList = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $processesListHash = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $servicesList = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $servicesListHash = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $scheduledTaskList = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $scheduledTaskListHash = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $registryRunKeys = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $registryRunKeysHash = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $startupFoldersPersistence = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $startupFoldersPersistenceHash = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalInformations = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $additionalInformationsHash = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agent $agent = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCpuUsage(): ?string
    {
        return $this->cpuUsage;
    }

    public function setCpuUsage(?string $cpuUsage): static
    {
        $this->cpuUsage = $cpuUsage;

        return $this;
    }

    public function getMemoryTotal(): ?float
    {
        return $this->memoryTotal;
    }

    public function setMemoryTotal(?float $memoryTotal): static
    {
        $this->memoryTotal = $memoryTotal;

        return $this;
    }

    public function getMemoryAvailable(): ?float
    {
        return $this->memoryAvailable;
    }

    public function setMemoryAvailable(?float $memoryAvailable): static
    {
        $this->memoryAvailable = $memoryAvailable;

        return $this;
    }

    public function getSystemDiskTotalSize(): ?float
    {
        return $this->systemDiskTotalSize;
    }

    public function setSystemDiskTotalSize(?float $systemDiskTotalSize): static
    {
        $this->systemDiskTotalSize = $systemDiskTotalSize;

        return $this;
    }

    public function getSystemDiskAvailableSize(): ?float
    {
        return $this->systemDiskAvailableSize;
    }

    public function setSystemDiskAvailableSize(?float $systemDiskAvailableSize): static
    {
        $this->systemDiskAvailableSize = $systemDiskAvailableSize;

        return $this;
    }

    public function getProcessesList(): ?string
    {
        return $this->processesList;
    }

    public function setProcessesList(?string $processesList): static
    {
        $this->processesList = $processesList;

        return $this;
    }

    public function getProcessesListHash(): ?string
    {
        return $this->processesListHash;
    }

    public function setProcessesListHash(?string $processesListHash): static
    {
        $this->processesListHash = $processesListHash;

        return $this;
    }

    public function getServicesList(): ?string
    {
        return $this->servicesList;
    }

    public function setServicesList(?string $servicesList): static
    {
        $this->servicesList = $servicesList;

        return $this;
    }

    public function getServicesListHash(): ?string
    {
        return $this->servicesListHash;
    }

    public function setServicesListHash(?string $servicesListHash): static
    {
        $this->servicesListHash = $servicesListHash;

        return $this;
    }

    public function getScheduledTaskList(): ?string
    {
        return $this->scheduledTaskList;
    }

    public function setScheduledTaskList(?string $scheduledTaskList): static
    {
        $this->scheduledTaskList = $scheduledTaskList;

        return $this;
    }

    public function getScheduledTaskListHash(): ?string
    {
        return $this->scheduledTaskListHash;
    }

    public function setScheduledTaskListHash(?string $scheduledTaskListHash): static
    {
        $this->scheduledTaskListHash = $scheduledTaskListHash;

        return $this;
    }

    public function getRegistryRunKeys(): ?string
    {
        return $this->registryRunKeys;
    }

    public function setRegistryRunKeys(?string $registryRunKeys): static
    {
        $this->registryRunKeys = $registryRunKeys;

        return $this;
    }

    public function getRegistryRunKeysHash(): ?string
    {
        return $this->registryRunKeysHash;
    }

    public function setRegistryRunKeysHash(?string $registryRunKeysHash): static
    {
        $this->registryRunKeysHash = $registryRunKeysHash;

        return $this;
    }

    public function getStartupFoldersPersistence(): ?string
    {
        return $this->startupFoldersPersistence;
    }

    public function setStartupFoldersPersistence(?string $startupFoldersPersistence): static
    {
        $this->startupFoldersPersistence = $startupFoldersPersistence;

        return $this;
    }

    public function getStartupFoldersPersistenceHash(): ?string
    {
        return $this->startupFoldersPersistenceHash;
    }

    public function setStartupFoldersPersistenceHash(?string $startupFoldersPersistenceHash): static
    {
        $this->startupFoldersPersistenceHash = $startupFoldersPersistenceHash;

        return $this;
    }

    public function getAdditionalInformations(): ?string
    {
        return $this->additionalInformations;
    }

    public function setAdditionalInformations(?string $additionalInformations): static
    {
        $this->additionalInformations = $additionalInformations;

        return $this;
    }

    public function getAdditionalInformationsHash(): ?string
    {
        return $this->additionalInformationsHash;
    }

    public function setAdditionalInformationsHash(?string $additionalInformationsHash): static
    {
        $this->additionalInformationsHash = $additionalInformationsHash;

        return $this;
    }

    public function getAgent(): ?Agent
    {
        return $this->agent;
    }

    public function setAgent(?Agent $agent): static
    {
        $this->agent = $agent;

        return $this;
    }
}
