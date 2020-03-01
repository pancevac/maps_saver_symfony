<?php

namespace App\Tests;

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
    private $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

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
}
