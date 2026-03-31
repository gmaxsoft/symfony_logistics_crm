<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateParcelDto;
use App\Entity\Parcel;
use App\Enum\ParcelStatus;
use App\Repository\ParcelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/api/parcels', name: 'api_parcels_')]
class ParcelController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParcelRepository $parcelRepository,
        private readonly WorkflowInterface $parcelStateMachine,
        private readonly ValidatorInterface $validator,
    ) {}

    /**
     * GET /api/parcels - list all parcels
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $courierName = $request->query->get('courier');

        if ($status !== null) {
            $statusEnum = ParcelStatus::tryFrom($status);
            if ($statusEnum === null) {
                return $this->json(['error' => 'Invalid status value'], Response::HTTP_BAD_REQUEST);
            }
            $parcels = $this->parcelRepository->findByStatus($statusEnum);
        } elseif ($request->query->getBoolean('active')) {
            $parcels = $this->parcelRepository->findActiveForCourier($courierName);
        } else {
            $parcels = $this->parcelRepository->findBy([], ['createdAt' => 'DESC']);
        }

        return $this->json(
            array_map(fn(Parcel $p) => $this->serializeParcel($p), $parcels),
            Response::HTTP_OK,
        );
    }

    /**
     * POST /api/parcels - create new parcel
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateParcelDto $dto,
    ): JsonResponse {
        $parcel = new Parcel();
        $parcel->setSenderAddress($dto->senderAddress);
        $parcel->setReceiverAddress($dto->receiverAddress);
        $parcel->setWeight((string) $dto->weight);
        $parcel->setCourierName($dto->courierName);
        $parcel->setNotes($dto->notes);

        if ($dto->senderLatitude !== null) {
            $parcel->setSenderLatitude((string) $dto->senderLatitude);
        }
        if ($dto->senderLongitude !== null) {
            $parcel->setSenderLongitude((string) $dto->senderLongitude);
        }
        if ($dto->receiverLatitude !== null) {
            $parcel->setReceiverLatitude((string) $dto->receiverLatitude);
        }
        if ($dto->receiverLongitude !== null) {
            $parcel->setReceiverLongitude((string) $dto->receiverLongitude);
        }

        $violations = $this->validator->validate($parcel);
        if (count($violations) > 0) {
            return $this->json(
                ['errors' => $this->formatViolations($violations)],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $this->em->persist($parcel);
        $this->em->flush();

        return $this->json($this->serializeParcel($parcel), Response::HTTP_CREATED);
    }

    /**
     * GET /api/parcels/{id} - get single parcel
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $parcel = $this->findParcelOrFail($id);
        if ($parcel instanceof JsonResponse) {
            return $parcel;
        }

        return $this->json($this->serializeParcel($parcel));
    }

    /**
     * GET /api/parcels/{id}/transitions - list available transitions for a parcel
     */
    #[Route('/{id}/transitions', name: 'transitions', methods: ['GET'])]
    public function transitions(string $id): JsonResponse
    {
        $parcel = $this->findParcelOrFail($id);
        if ($parcel instanceof JsonResponse) {
            return $parcel;
        }

        $enabledTransitions = $this->parcelStateMachine->getEnabledTransitions($parcel);

        $transitions = array_map(
            fn($t) => [
                'name' => $t->getName(),
                'froms' => $t->getFroms(),
                'tos' => $t->getTos(),
                'label' => $this->getTransitionLabel($t->getName()),
            ],
            $enabledTransitions
        );

        return $this->json([
            'parcel_id' => (string) $parcel->getId(),
            'current_status' => $parcel->getStatus(),
            'available_transitions' => array_values($transitions),
        ]);
    }

    /**
     * PATCH /api/parcels/{id}/transition/{transition} - apply a workflow transition
     */
    #[Route('/{id}/transition/{transition}', name: 'apply_transition', methods: ['PATCH'])]
    public function applyTransition(string $id, string $transition): JsonResponse
    {
        $parcel = $this->findParcelOrFail($id);
        if ($parcel instanceof JsonResponse) {
            return $parcel;
        }

        if (!$this->parcelStateMachine->can($parcel, $transition)) {
            $enabledTransitions = array_map(
                fn($t) => $t->getName(),
                $this->parcelStateMachine->getEnabledTransitions($parcel)
            );

            return $this->json([
                'error' => sprintf(
                    'Transition "%s" is not allowed from status "%s".',
                    $transition,
                    $parcel->getStatus()
                ),
                'available_transitions' => $enabledTransitions,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->parcelStateMachine->apply($parcel, $transition);

        if ($parcel->getStatus() === ParcelStatus::DELIVERED->value) {
            $parcel->setDeliveredAt(new \DateTimeImmutable());
        }

        $this->em->flush();

        return $this->json([
            'message' => sprintf('Transition "%s" applied successfully.', $transition),
            'parcel' => $this->serializeParcel($parcel),
        ]);
    }

    /**
     * DELETE /api/parcels/{id} - delete a parcel (only in draft)
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $parcel = $this->findParcelOrFail($id);
        if ($parcel instanceof JsonResponse) {
            return $parcel;
        }

        if ($parcel->getStatus() !== ParcelStatus::DRAFT->value) {
            return $this->json(
                ['error' => 'Only parcels in "draft" status can be deleted.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $this->em->remove($parcel);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function findParcelOrFail(string $id): Parcel|JsonResponse
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            $parcel = $this->parcelRepository->findByTrackingNumber($id);
            if ($parcel === null) {
                return $this->json(['error' => 'Parcel not found.'], Response::HTTP_NOT_FOUND);
            }
            return $parcel;
        }

        $parcel = $this->parcelRepository->find($uuid);
        if ($parcel === null) {
            return $this->json(['error' => 'Parcel not found.'], Response::HTTP_NOT_FOUND);
        }

        return $parcel;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeParcel(Parcel $parcel): array
    {
        $statusEnum = $parcel->getStatusEnum();

        return [
            'id' => (string) $parcel->getId(),
            'trackingNumber' => $parcel->getTrackingNumber(),
            'status' => $parcel->getStatus(),
            'statusLabel' => $statusEnum->label(),
            'statusColor' => $statusEnum->color(),
            'senderAddress' => $parcel->getSenderAddress(),
            'receiverAddress' => $parcel->getReceiverAddress(),
            'weight' => (float) $parcel->getWeight(),
            'courierName' => $parcel->getCourierName(),
            'notes' => $parcel->getNotes(),
            'coordinates' => [
                'sender' => $this->buildCoordinates(
                    $parcel->getSenderLatitude(),
                    $parcel->getSenderLongitude()
                ),
                'receiver' => $this->buildCoordinates(
                    $parcel->getReceiverLatitude(),
                    $parcel->getReceiverLongitude()
                ),
            ],
            'createdAt' => $parcel->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $parcel->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'deliveredAt' => $parcel->getDeliveredAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function buildCoordinates(?string $lat, ?string $lng): ?array
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        return ['lat' => (float) $lat, 'lng' => (float) $lng];
    }

    private function getTransitionLabel(string $transitionName): string
    {
        return match($transitionName) {
            'pick_up' => 'Odbierz od nadawcy',
            'sort' => 'Przekaż do centrum sortowania',
            'deliver_start' => 'Rozpocznij doręczenie',
            'confirm_delivery' => 'Potwierdź dostarczenie',
            'mark_failed' => 'Oznacz jako nieudane',
            default => ucfirst(str_replace('_', ' ', $transitionName)),
        };
    }

    private function formatViolations(\Symfony\Component\Validator\ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }
        return $errors;
    }
}
