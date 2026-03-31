<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ParcelStatus;
use App\Repository\ParcelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ParcelRepository::class)]
#[ORM\Table(name: 'parcels')]
#[ORM\HasLifecycleCallbacks]
class Parcel
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 32, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 32)]
    private ?string $trackingNumber = null;

    #[ORM\Column(length: 50)]
    private string $status = ParcelStatus::DRAFT->value;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    private ?string $senderAddress = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    private ?string $receiverAddress = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3)]
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(1000)]
    private ?string $weight = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $senderLatitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $senderLongitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $receiverLatitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $receiverLongitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $courierName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;

        if ($this->trackingNumber === null) {
            $this->trackingNumber = strtoupper('PLG' . substr(str_replace('-', '', (string) Uuid::v4()), 0, 12));
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusEnum(): ParcelStatus
    {
        return ParcelStatus::from($this->status);
    }

    public function getSenderAddress(): ?string
    {
        return $this->senderAddress;
    }

    public function setSenderAddress(string $senderAddress): static
    {
        $this->senderAddress = $senderAddress;
        return $this;
    }

    public function getReceiverAddress(): ?string
    {
        return $this->receiverAddress;
    }

    public function setReceiverAddress(string $receiverAddress): static
    {
        $this->receiverAddress = $receiverAddress;
        return $this;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(string $weight): static
    {
        $this->weight = $weight;
        return $this;
    }

    public function getSenderLatitude(): ?string
    {
        return $this->senderLatitude;
    }

    public function setSenderLatitude(?string $senderLatitude): static
    {
        $this->senderLatitude = $senderLatitude;
        return $this;
    }

    public function getSenderLongitude(): ?string
    {
        return $this->senderLongitude;
    }

    public function setSenderLongitude(?string $senderLongitude): static
    {
        $this->senderLongitude = $senderLongitude;
        return $this;
    }

    public function getReceiverLatitude(): ?string
    {
        return $this->receiverLatitude;
    }

    public function setReceiverLatitude(?string $receiverLatitude): static
    {
        $this->receiverLatitude = $receiverLatitude;
        return $this;
    }

    public function getReceiverLongitude(): ?string
    {
        return $this->receiverLongitude;
    }

    public function setReceiverLongitude(?string $receiverLongitude): static
    {
        $this->receiverLongitude = $receiverLongitude;
        return $this;
    }

    public function getCourierName(): ?string
    {
        return $this->courierName;
    }

    public function setCourierName(?string $courierName): static
    {
        $this->courierName = $courierName;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }
}
