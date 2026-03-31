<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateParcelDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        public readonly string $senderAddress,

        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        public readonly string $receiverAddress,

        #[Assert\NotNull]
        #[Assert\Positive]
        #[Assert\LessThanOrEqual(1000)]
        public readonly float $weight,

        #[Assert\Length(max: 255)]
        public readonly ?string $courierName = null,

        #[Assert\Range(min: -90, max: 90)]
        public readonly ?float $senderLatitude = null,

        #[Assert\Range(min: -180, max: 180)]
        public readonly ?float $senderLongitude = null,

        #[Assert\Range(min: -90, max: 90)]
        public readonly ?float $receiverLatitude = null,

        #[Assert\Range(min: -180, max: 180)]
        public readonly ?float $receiverLongitude = null,

        public readonly ?string $notes = null,
    ) {
    }
}
