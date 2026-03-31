<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Parcel;
use App\Enum\ParcelStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Parcel>
 */
class ParcelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parcel::class);
    }

    /**
     * @return list<Parcel>
     */
    public function findByStatus(ParcelStatus $status): array
    {
        /* @var list<Parcel> */
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status->value)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Parcel>
     */
    public function findActiveForCourier(?string $courierName = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status NOT IN (:terminalStatuses)')
            ->setParameter('terminalStatuses', [ParcelStatus::DELIVERED->value, ParcelStatus::FAILED->value])
            ->orderBy('p.updatedAt', 'DESC');

        if ($courierName !== null) {
            $qb->andWhere('p.courierName = :courier')
                ->setParameter('courier', $courierName);
        }

        /* @var list<Parcel> */
        return $qb->getQuery()->getResult();
    }

    public function findByTrackingNumber(string $trackingNumber): ?Parcel
    {
        return $this->findOneBy(['trackingNumber' => $trackingNumber]);
    }
}
