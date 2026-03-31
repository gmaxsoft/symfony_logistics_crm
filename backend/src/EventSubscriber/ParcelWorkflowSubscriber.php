<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Parcel;
use App\Enum\ParcelStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;

class ParcelWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.parcel.guard.confirm_delivery' => 'guardConfirmDelivery',
            'workflow.parcel.guard.mark_failed' => 'guardMarkFailed',
            'workflow.parcel.completed' => 'onTransitionCompleted',
            'workflow.parcel.entered' => 'onStatusEntered',
        ];
    }

    /**
     * Guard: confirm_delivery is only allowed from out_for_delivery status.
     */
    public function guardConfirmDelivery(GuardEvent $event): void
    {
        $parcel = $this->extractParcel($event);

        if ($parcel->getStatus() !== ParcelStatus::OUT_FOR_DELIVERY->value) {
            $event->setBlocked(true, 'Parcel must be "out_for_delivery" to confirm delivery.');
        }
    }

    /**
     * Guard: mark_failed is only allowed from active statuses (not draft or terminal states).
     */
    public function guardMarkFailed(GuardEvent $event): void
    {
        $parcel = $this->extractParcel($event);

        $allowedStatuses = [
            ParcelStatus::PICKED_UP->value,
            ParcelStatus::IN_SORTING_CENTER->value,
            ParcelStatus::OUT_FOR_DELIVERY->value,
        ];

        if (!\in_array($parcel->getStatus(), $allowedStatuses, true)) {
            $event->setBlocked(true, 'Cannot mark as failed from current status.');
        }
    }

    /**
     * Log every completed transition.
     */
    public function onTransitionCompleted(Event $event): void
    {
        $parcel = $this->extractParcel($event);

        $this->logger->info('Parcel workflow transition completed', [
            'parcel_id' => (string) $parcel->getId(),
            'tracking_number' => $parcel->getTrackingNumber(),
            'transition' => $event->getTransition()?->getName(),
            'new_status' => $parcel->getStatus(),
        ]);
    }

    /**
     * Handle side effects when entering specific states.
     */
    public function onStatusEntered(Event $event): void
    {
        $parcel = $this->extractParcel($event);
        $marking = $event->getMarking();

        if (\array_key_exists(ParcelStatus::DELIVERED->value, $marking->getPlaces())) {
            $parcel->setDeliveredAt(new \DateTimeImmutable());

            $this->logger->info('Parcel delivered', [
                'parcel_id' => (string) $parcel->getId(),
                'tracking_number' => $parcel->getTrackingNumber(),
            ]);
        }
    }

    /**
     * Extract and assert the Parcel subject from a workflow event.
     *
     * @throws \InvalidArgumentException when subject is not a Parcel
     */
    private function extractParcel(Event $event): Parcel
    {
        $subject = $event->getSubject();

        if (!$subject instanceof Parcel) {
            throw new \InvalidArgumentException(sprintf(
                'Expected subject to be %s, got %s.',
                Parcel::class,
                \get_debug_type($subject),
            ));
        }

        return $subject;
    }
}
