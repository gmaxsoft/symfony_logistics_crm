<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Parcel;
use App\Enum\ParcelStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class ParcelWorkflowTest extends KernelTestCase
{
    private WorkflowInterface $workflow;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->workflow = static::getContainer()->get('state_machine.parcel');
    }

    private function createParcel(string $status = ParcelStatus::DRAFT->value): Parcel
    {
        $parcel = new Parcel();
        $parcel->setSenderAddress('Sender Address');
        $parcel->setReceiverAddress('Receiver Address');
        $parcel->setWeight('1.000');
        $parcel->setStatus($status);
        return $parcel;
    }

    public function testInitialStatusIsDraft(): void
    {
        $parcel = $this->createParcel();
        $this->assertSame('draft', $parcel->getStatus());
    }

    public function testPickUpTransitionFromDraft(): void
    {
        $parcel = $this->createParcel('draft');
        $this->assertTrue($this->workflow->can($parcel, 'pick_up'));
        $this->workflow->apply($parcel, 'pick_up');
        $this->assertSame('picked_up', $parcel->getStatus());
    }

    public function testSortTransitionFromPickedUp(): void
    {
        $parcel = $this->createParcel('picked_up');
        $this->assertTrue($this->workflow->can($parcel, 'sort'));
        $this->workflow->apply($parcel, 'sort');
        $this->assertSame('in_sorting_center', $parcel->getStatus());
    }

    public function testDeliverStartFromSortingCenter(): void
    {
        $parcel = $this->createParcel('in_sorting_center');
        $this->assertTrue($this->workflow->can($parcel, 'deliver_start'));
        $this->workflow->apply($parcel, 'deliver_start');
        $this->assertSame('out_for_delivery', $parcel->getStatus());
    }

    public function testConfirmDeliveryFromOutForDelivery(): void
    {
        $parcel = $this->createParcel('out_for_delivery');
        $this->assertTrue($this->workflow->can($parcel, 'confirm_delivery'));
        $this->workflow->apply($parcel, 'confirm_delivery');
        $this->assertSame('delivered', $parcel->getStatus());
    }

    public function testCannotConfirmDeliveryFromDraft(): void
    {
        $parcel = $this->createParcel('draft');
        $this->assertFalse($this->workflow->can($parcel, 'confirm_delivery'));
    }

    public function testCannotConfirmDeliveryFromPickedUp(): void
    {
        $parcel = $this->createParcel('picked_up');
        $this->assertFalse($this->workflow->can($parcel, 'confirm_delivery'));
    }

    public function testMarkFailedFromPickedUp(): void
    {
        $parcel = $this->createParcel('picked_up');
        $this->assertTrue($this->workflow->can($parcel, 'mark_failed'));
        $this->workflow->apply($parcel, 'mark_failed');
        $this->assertSame('failed', $parcel->getStatus());
    }

    public function testMarkFailedFromOutForDelivery(): void
    {
        $parcel = $this->createParcel('out_for_delivery');
        $this->assertTrue($this->workflow->can($parcel, 'mark_failed'));
        $this->workflow->apply($parcel, 'mark_failed');
        $this->assertSame('failed', $parcel->getStatus());
    }

    public function testCannotMarkFailedFromDraft(): void
    {
        $parcel = $this->createParcel('draft');
        $this->assertFalse($this->workflow->can($parcel, 'mark_failed'));
    }

    public function testCannotTransitionFromDelivered(): void
    {
        $parcel = $this->createParcel('delivered');
        $this->assertEmpty($this->workflow->getEnabledTransitions($parcel));
    }

    public function testFullHappyPath(): void
    {
        $parcel = $this->createParcel('draft');

        $this->workflow->apply($parcel, 'pick_up');
        $this->assertSame('picked_up', $parcel->getStatus());

        $this->workflow->apply($parcel, 'sort');
        $this->assertSame('in_sorting_center', $parcel->getStatus());

        $this->workflow->apply($parcel, 'deliver_start');
        $this->assertSame('out_for_delivery', $parcel->getStatus());

        $this->workflow->apply($parcel, 'confirm_delivery');
        $this->assertSame('delivered', $parcel->getStatus());
    }
}
