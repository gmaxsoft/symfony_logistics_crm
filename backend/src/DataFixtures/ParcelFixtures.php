<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Parcel;
use App\Enum\ParcelStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ParcelFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getParcelData() as $index => $data) {
            $parcel = new Parcel();
            $parcel->setSenderAddress($data['senderAddress']);
            $parcel->setReceiverAddress($data['receiverAddress']);
            $parcel->setWeight($data['weight']);
            $parcel->setCourierName($data['courierName']);
            $parcel->setStatus($data['status']->value);
            $parcel->setSenderLatitude($data['senderLat']);
            $parcel->setSenderLongitude($data['senderLng']);
            $parcel->setReceiverLatitude($data['receiverLat']);
            $parcel->setReceiverLongitude($data['receiverLng']);

            if ($data['status'] === ParcelStatus::DELIVERED) {
                $parcel->setDeliveredAt(new \DateTimeImmutable('-1 hour'));
            }

            $manager->persist($parcel);
            $this->addReference('parcel_' . $index, $parcel);
        }

        $manager->flush();
    }

    /**
     * @return list<array{
     *     senderAddress: string,
     *     receiverAddress: string,
     *     weight: string,
     *     status: ParcelStatus,
     *     courierName: string,
     *     senderLat: string,
     *     senderLng: string,
     *     receiverLat: string,
     *     receiverLng: string
     * }>
     */
    private function getParcelData(): array
    {
        return [
            [
                'senderAddress' => 'ul. Marszałkowska 1, 00-001 Warszawa',
                'receiverAddress' => 'ul. Floriańska 10, 31-021 Kraków',
                'weight' => '2.500',
                'status' => ParcelStatus::DRAFT,
                'courierName' => 'Jan Kowalski',
                'senderLat' => '52.2297',
                'senderLng' => '21.0122',
                'receiverLat' => '50.0647',
                'receiverLng' => '19.9450',
            ],
            [
                'senderAddress' => 'ul. Świdnicka 5, 50-066 Wrocław',
                'receiverAddress' => 'ul. Długa 3, 61-848 Poznań',
                'weight' => '0.750',
                'status' => ParcelStatus::PICKED_UP,
                'courierName' => 'Anna Nowak',
                'senderLat' => '51.1079',
                'senderLng' => '17.0385',
                'receiverLat' => '52.4064',
                'receiverLng' => '16.9252',
            ],
            [
                'senderAddress' => 'ul. Piotrkowska 50, 90-001 Łódź',
                'receiverAddress' => 'ul. Batorego 8, 40-091 Katowice',
                'weight' => '5.200',
                'status' => ParcelStatus::IN_SORTING_CENTER,
                'courierName' => 'Piotr Wiśniewski',
                'senderLat' => '51.7592',
                'senderLng' => '19.4560',
                'receiverLat' => '50.2649',
                'receiverLng' => '19.0238',
            ],
            [
                'senderAddress' => 'ul. Gdańska 12, 85-005 Bydgoszcz',
                'receiverAddress' => 'ul. Kościuszki 7, 87-100 Toruń',
                'weight' => '1.100',
                'status' => ParcelStatus::OUT_FOR_DELIVERY,
                'courierName' => 'Maria Zielińska',
                'senderLat' => '53.1235',
                'senderLng' => '18.0084',
                'receiverLat' => '53.0138',
                'receiverLng' => '18.5984',
            ],
            [
                'senderAddress' => 'ul. Nowy Świat 22, 00-373 Warszawa',
                'receiverAddress' => 'ul. Krakowska 15, 35-111 Rzeszów',
                'weight' => '3.000',
                'status' => ParcelStatus::DELIVERED,
                'courierName' => 'Tomasz Szymański',
                'senderLat' => '52.2297',
                'senderLng' => '21.0122',
                'receiverLat' => '50.0412',
                'receiverLng' => '21.9991',
            ],
        ];
    }
}
