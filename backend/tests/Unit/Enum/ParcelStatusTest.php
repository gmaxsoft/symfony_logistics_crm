<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\ParcelStatus;
use PHPUnit\Framework\TestCase;

class ParcelStatusTest extends TestCase
{
    public function testAllStatusesHaveLabels(): void
    {
        foreach (ParcelStatus::cases() as $status) {
            $this->assertNotEmpty($status->label(), "Status {$status->value} has no label");
        }
    }

    public function testAllStatusesHaveColors(): void
    {
        foreach (ParcelStatus::cases() as $status) {
            $this->assertNotEmpty($status->color(), "Status {$status->value} has no color");
        }
    }

    public function testDeliveredStatusHasGreenColor(): void
    {
        $this->assertSame('green', ParcelStatus::DELIVERED->color());
    }

    public function testFailedStatusHasRedColor(): void
    {
        $this->assertSame('red', ParcelStatus::FAILED->color());
    }

    public function testFromStringReturnCorrectEnum(): void
    {
        $this->assertSame(ParcelStatus::DRAFT, ParcelStatus::from('draft'));
        $this->assertSame(ParcelStatus::PICKED_UP, ParcelStatus::from('picked_up'));
        $this->assertSame(ParcelStatus::DELIVERED, ParcelStatus::from('delivered'));
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(ParcelStatus::tryFrom('invalid_status'));
    }
}
