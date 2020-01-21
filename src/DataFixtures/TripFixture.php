<?php

namespace App\DataFixtures;

use App\Entity\Trip;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class TripFixture extends BaseFixture implements DependentFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(15, 'trips', function (int $i) {
            $trip = new Trip();
            $trip->setName($this->faker->city . ' - ' . $this->faker->city);
            $trip->setCreator($this->faker->name);
            /** @var User $user */
            $user = $this->getRandomReference('users');
            $trip->setUser($user);

            return $trip;
        });

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return class-string[]
     */
    public function getDependencies()
    {
        return [UserFixture::class];
    }
}
