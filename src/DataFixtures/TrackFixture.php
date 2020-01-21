<?php

namespace App\DataFixtures;

use App\Entity\Track;
use App\Entity\Trip;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class TrackFixture extends BaseFixture implements DependentFixtureInterface
{
    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(20, 'tracks', function (int $i) {
            $track = new Track();
            $track->setName('Track_' . $this->faker->numberBetween(1, 200));
            $track->setDescription($this->faker->text);

            /** @var Trip $trip */
            $trip = $this->getRandomReference('trips');

            $track->setTrip($trip);

            return $track;
        });

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array class-string[]
     */
    public function getDependencies()
    {
        return [TripFixture::class];
    }
}
