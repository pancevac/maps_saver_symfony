<?php

namespace App\Tests;

use App\Entity\Trip;
use App\Entity\User;
use App\Service\GpxConverter;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TripControllerTest extends AbstractControllerTest
{
    use FixturesTrait;

    private $entityManager;

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->loadFixtures(['App\DataFixtures\PointFixture']); // this will trigger creation of other related fixtures as well

        $this->entityManager = self::$container->get('doctrine')->getManager();

        // Get first user
        $this->user = $this->entityManager->getRepository(User::class)->findOneBy([]);
    }

    /** @test */
    public function itShouldLoadDocPage()
    {
        $crawler = self::createClient()->request('GET', '/api/doc');

        $this->assertResponseIsSuccessful();
        //$this->assertSelectorTextContains('h2', 'Maps Saver Service');
    }

    /** @test */
    public function a_guest_can_not_access_trips()
    {
        $crawler = self::createClient()->request('GET', '/api/trips');

        $this->assertResponseStatusCodeSame(401);
    }

    /** @test */
    public function it_should_list_trips_which_belongs_to_auth_user()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/trips');
        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true)['data'];

        // check if trips from json response match trips from database
        $expectedTripsCount = $this->user->getTrips()->count();

        $this->assertCount($expectedTripsCount, $responseData);
    }

    /** @test */
    public function it_should_return_trip_by_id_which_belongs_to_auth_user()
    {
        $client = $this->createAuthenticatedClient();

        /** @var Trip $trip */
        $trip = $this->user->getTrips()->first();

        $client->request('GET', '/api/trips/' . $trip->getId());
        $response = $client->getResponse();

        $responseData = json_decode($response->getContent(), true);

        $this->assertSame($trip->getName(), $responseData['name']);
    }

    /** @test */
    public function it_should_return_404_if_trip_does_not_exist()
    {
        $client = $this->createAuthenticatedClient();

        $this->expectException(NotFoundHttpException::class);
        $client->catchExceptions(false);
        $client->request('GET', '/api/trips/100');
    }

    /** @test */
    public function it_should_return_404_if_trip_does_not_belong_to_given_auth_user()
    {
        $client = $this->createAuthenticatedClient();

        $this->expectException(NotFoundHttpException::class);
        $client->catchExceptions(false);

        /** @var Trip[] $trips */
        $trips = array_filter($this->entityManager->getRepository(Trip::class)->findAll(), function (Trip $trip) {
            return $trip->getUser()->getId() !== $this->user->getId();
        });

        $client->request(
            'GET',
            '/api/trips/' . $trips[0]->getId()
        );
    }

    /** @test */
    public function it_should_return_trip_xml_representation()
    {
        $client = $this->createAuthenticatedClient();

        /** @var Trip $trip */
        $trip = $this->user->getTrips()->first();

        /** @var GpxConverter $gpxConverter */
        $gpxConverter = self::$container->get(GpxConverter::class);

        $gpx = $gpxConverter->makeGPXFile($trip);
        $xml = $gpx->toXML()->saveXML();

        $client->request('GET', '/api/trips/gpx/' . $trip->getId());
        $response = $client->getResponse();

        $this->assertSame(['response' => $xml], json_decode($response->getContent(), true));
    }

    /** @test */
    public function a_auth_user_can_create_trip()
    {
        $client = $this->createAuthenticatedClient();

        $tripFilePath = self::$container->get('kernel')->getProjectDir() . '/tests/files/map.gpx';

        $gpxFile = new UploadedFile($tripFilePath, 'map.gpx', 'application/gpx+xml', null);

        $client->request(
            'POST',
            '/api/trips',
            ['name' => 'Test trip'],
            ['trip' => $gpxFile]
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(['message' => 'Successfully saved trip!'], $response);
    }

    /** @test */
    public function a_auth_user_can_not_create_trip_with_same_name()
    {
        /** @var Trip $trip */
        $trip = $this->getAuthUser()->getTrips()->first();

        $tripFilePath = self::$container->get('kernel')->getProjectDir() . '/tests/files/map.gpx';

        $gpxFile = new UploadedFile($tripFilePath, 'map.gpx', 'application/gpx+xml', null);

        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/trips',
            ['name' => $trip->getName()],
            ['trip' => $gpxFile]
        );

        $this->assertSame(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());
    }

    /** @test */
    public function a_auth_user_can_update_trip_name()
    {
        $client = $this->createAuthenticatedClient();

        /** @var Trip $trip */
        $trip = $this->user->getTrips()->first();

        $client->request(
            'PUT',
            '/api/trips/' . $trip->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Updated trip name'])
        );

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(['message' => 'Trip successfully updated.'], $responseData);

        // Assert also that trip is updated in db
        $this->entityManager->refresh($trip);

        $this->assertSame('Updated trip name', $trip->getName());
    }

    /** @test */
    public function a_auth_user_can_not_update_trip_with_already_existing_name()
    {
        $client = $this->createAuthenticatedClient();

        /** @var Trip[] $trip */
        $trips = $this->user->getTrips();

        $client->request(
            'PUT',
            '/api/trips/' . $trips[1]->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => $trips[0]->getName()])
        );

        $this->assertSame(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());
    }

    /** @test */
    public function a_auth_user_can_not_update_someone_else_trip_name()
    {
        $client = $this->createAuthenticatedClient();

        $this->expectException(NotFoundHttpException::class);
        $client->catchExceptions(false);

        /** @var Trip[] $trips */
        $trips = array_filter($this->entityManager->getRepository(Trip::class)->findAll(), function (Trip $trip) {
            return $trip->getUser()->getId() !== $this->user->getId();
        });

        $client->request(
            'PUT',
            '/api/trips/' . $trips[0]->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Updated trip name'])
        );
    }

    /** @test */
    public function a_auth_user_can_delete_trip()
    {
        $client = $this->createAuthenticatedClient();

        /** @var Trip $trip */
        $trip = $this->user->getTrips()->first();

        $client->request(
            'DELETE',
            '/api/trips/' . $trip->getId()
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(['message' => 'Successfully deleted trip.'], $response);
    }

    /** @test */
    public function a_auth_user_can_not_delete_someone_else_trip()
    {
        $client = $this->createAuthenticatedClient();

        $this->expectException(NotFoundHttpException::class);
        $client->catchExceptions(false);

        /** @var Trip[] $trips */
        $trips = array_filter($this->entityManager->getRepository(Trip::class)->findAll(), function (Trip $trip) {
            return $trip->getUser()->getId() !== $this->user->getId();
        });

        $client->request(
            'DELETE',
            '/api/trips/' . $trips[0]->getId()
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * @inheritDoc
     */
    protected function getAuthUser(): User
    {
        return $this->user;
    }
}
