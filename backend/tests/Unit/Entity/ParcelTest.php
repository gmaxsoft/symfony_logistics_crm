<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Parcel;
use App\Enum\ParcelStatus;
use PHPUnit\Framework\TestCase;

class ParcelTest extends TestCase
{
    public function testDefaultStatusIsDraft(): void
    {
        $parcel = new Parcel();
        $this->assertSame(ParcelStatus::DRAFT->value, $parcel->getStatus());
    }

    public function testSetAndGetStatus(): void
    {
        $parcel = new Parcel();
        $parcel->setStatus(ParcelStatus::PICKED_UP->value);
        $this->assertSame(ParcelStatus::PICKED_UP->value, $parcel->getStatus());
    }

    public function testGetStatusEnumReturnsProperlyCastedEnum(): void
    {
        $parcel = new Parcel();
        $parcel->setStatus(ParcelStatus::DELIVERED->value);
        $this->assertSame(ParcelStatus::DELIVERED, $parcel->getStatusEnum());
    }

    public function testSenderAndReceiverAddress(): void
    {
        $parcel = new Parcel();
        $parcel->setSenderAddress('ul. Testowa 1, 00-001 Warszawa');
        $parcel->setReceiverAddress('ul. Odbiorcza 5, 31-001 Kraków');

        $this->assertSame('ul. Testowa 1, 00-001 Warszawa', $parcel->getSenderAddress());
        $this->assertSame('ul. Odbiorcza 5, 31-001 Kraków', $parcel->getReceiverAddress());
    }

    public function testWeightSetAndGet(): void
    {
        $parcel = new Parcel();
        $parcel->setWeight('2.500');
        $this->assertSame('2.500', $parcel->getWeight());
    }

    public function testPrePersistSetsTimestamps(): void
    {
        $parcel = new Parcel();
        $parcel->setSenderAddress('Sender');
        $parcel->setReceiverAddress('Receiver');
        $parcel->setWeight('1.000');

        $parcel->prePersist();

        $this->assertNotNull($parcel->getCreatedAt());
        $this->assertNotNull($parcel->getUpdatedAt());
        $this->assertNotNull($parcel->getTrackingNumber());
        $this->assertStringStartsWith('PLG', $parcel->getTrackingNumber());
    }

    public function testDeliveredAtCanBeSet(): void
    {
        $parcel = new Parcel();
        $now = new \DateTimeImmutable();
        $parcel->setDeliveredAt($now);

        $this->assertSame($now, $parcel->getDeliveredAt());
    }

    public function testCoordinatesCanBeSetAndRetrieved(): void
    {
        $parcel = new Parcel();
        $parcel->setSenderLatitude('52.2297');
        $parcel->setSenderLongitude('21.0122');
        $parcel->setReceiverLatitude('50.0647');
        $parcel->setReceiverLongitude('19.9450');

        $this->assertSame('52.2297', $parcel->getSenderLatitude());
        $this->assertSame('21.0122', $parcel->getSenderLongitude());
        $this->assertSame('50.0647', $parcel->getReceiverLatitude());
        $this->assertSame('19.9450', $parcel->getReceiverLongitude());
    }
}
