<?php

namespace App\Tests;

use App\Entity\Trip;
use App\Entity\User;
use App\Repository\TripRepository;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TripRepositoryTest extends KernelTestCase
{
    use FixturesTrait;

    /**
     * @var TripRepository
     */
    private $tripRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->loadFixtures(['App\DataFixtures\TripFixture']);

        $this->tripRepository = $this->getContainer()->get(TripRepository::class);
        $this->userRepository = $this->getContainer()->get(UserRepository::class);
    }

    private function authUser(User $user, string $password = 'password')
    {
        $token = new UsernamePasswordToken($user, $password, 'main', $user->getRoles());

        $this->getContainer()->get('security.token_storage')->setToken($token);

        return $token->getUser();
    }

    /** @test */
    public function test_find_owned_by_auth_user()
    {
        /** @var User $user first user */
        $user = $this->userRepository->findOneBy([]);

        $this->authUser($user);

        $userTrips = $this->tripRepository->findBy(['user' => $user]);

        // Get random trip from userTrips array
        $randomTrip = $userTrips[array_rand($userTrips)];

        // This ensures that findOwnedByAuthUser works
        $queryTrip = $this->tripRepository->findOwnedByAuthUser($randomTrip->getId());

        $this->assertEquals($randomTrip->getUser()->getId(), $queryTrip->getUser()->getId());
    }

    /** @test */
    public function test_find_one_except()
    {
        /** @var Trip[] $trips */
        $allTrips = $this->tripRepository->findAll();

        // take for example, first trip in array
        $exceptionTrip = $allTrips[0];

        $expectedNull = $this->tripRepository->findOneExcept($exceptionTrip->getId(), ['name' => $exceptionTrip->getName()]);
        $trip = $this->tripRepository->findOneExcept($exceptionTrip->getId(), ['name' => $allTrips[1]->getName()]);

        $this->assertNull($expectedNull);
        $this->assertEquals($allTrips[1], $trip);
    }
}
