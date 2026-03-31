<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Parcel;
use App\Enum\ParcelStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ParcelControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createTestParcel(string $status = 'draft'): Parcel
    {
        $parcel = new Parcel();
        $parcel->setSenderAddress('ul. Testowa 1, 00-001 Warszawa');
        $parcel->setReceiverAddress('ul. Odbiorcza 5, 31-001 Kraków');
        $parcel->setWeight('2.500');
        $parcel->setStatus($status);

        $this->em->persist($parcel);
        $this->em->flush();

        return $parcel;
    }

    public function testListParcelsReturnsOk(): void
    {
        $this->client->request('GET', '/api/parcels');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testCreateParcelReturns201(): void
    {
        $this->client->request(
            'POST',
            '/api/parcels',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'senderAddress' => 'ul. Nowa 1, 00-001 Warszawa',
                'receiverAddress' => 'ul. Odbiorcza 5, 31-001 Kraków',
                'weight' => 1.5,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('trackingNumber', $data);
        $this->assertSame('draft', $data['status']);
        $this->assertStringStartsWith('PLG', $data['trackingNumber']);
    }

    public function testCreateParcelValidatesRequiredFields(): void
    {
        $this->client->request(
            'POST',
            '/api/parcels',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetParcelByIdReturnsParcel(): void
    {
        $parcel = $this->createTestParcel();
        $id = (string) $parcel->getId();

        $this->client->request('GET', "/api/parcels/{$id}");
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($id, $data['id']);
        $this->assertSame('draft', $data['status']);
    }

    public function testGetNonExistentParcelReturns404(): void
    {
        $this->client->request('GET', '/api/parcels/00000000-0000-0000-0000-000000000000');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetTransitionsForDraftParcel(): void
    {
        $parcel = $this->createTestParcel('draft');
        $id = (string) $parcel->getId();

        $this->client->request('GET', "/api/parcels/{$id}/transitions");
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('draft', $data['current_status']);

        $transitionNames = array_column($data['available_transitions'], 'name');
        $this->assertContains('pick_up', $transitionNames);
    }

    public function testApplyPickUpTransition(): void
    {
        $parcel = $this->createTestParcel('draft');
        $id = (string) $parcel->getId();

        $this->client->request('PATCH', "/api/parcels/{$id}/transition/pick_up");
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('picked_up', $data['parcel']['status']);
    }

    public function testCannotApplyInvalidTransition(): void
    {
        $parcel = $this->createTestParcel('draft');
        $id = (string) $parcel->getId();

        $this->client->request('PATCH', "/api/parcels/{$id}/transition/confirm_delivery");
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testFullWorkflowViaApi(): void
    {
        $parcel = $this->createTestParcel('draft');
        $id = (string) $parcel->getId();

        $transitions = ['pick_up', 'sort', 'deliver_start', 'confirm_delivery'];
        $expectedStatuses = ['picked_up', 'in_sorting_center', 'out_for_delivery', 'delivered'];

        foreach ($transitions as $i => $transition) {
            $this->client->request('PATCH', "/api/parcels/{$id}/transition/{$transition}");
            $this->assertResponseIsSuccessful();

            $data = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertSame($expectedStatuses[$i], $data['parcel']['status']);
        }

        // Verify delivered_at is set
        $this->client->request('GET', "/api/parcels/{$id}");
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotNull($data['deliveredAt']);
    }

    public function testDeleteDraftParcel(): void
    {
        $parcel = $this->createTestParcel('draft');
        $id = (string) $parcel->getId();

        $this->client->request('DELETE', "/api/parcels/{$id}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify it's gone
        $this->client->request('GET', "/api/parcels/{$id}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCannotDeleteNonDraftParcel(): void
    {
        $parcel = $this->createTestParcel('picked_up');
        $id = (string) $parcel->getId();

        $this->client->request('DELETE', "/api/parcels/{$id}");
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testFilterByStatus(): void
    {
        $this->createTestParcel('draft');
        $this->createTestParcel('draft');
        $this->createTestParcel('picked_up');

        $this->client->request('GET', '/api/parcels?status=draft');
        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        foreach ($data as $item) {
            $this->assertSame('draft', $item['status']);
        }
    }
}
