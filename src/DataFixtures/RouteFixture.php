<?php

namespace App\DataFixtures;

use App\Entity\Route;
use App\Entity\Trip;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class RouteFixture extends BaseFixture implements DependentFixtureInterface
{
    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(20, 'routes', function (int $i) {
            $route = new Route();
            $route->setName('Route_' . $this->faker->numberBetween(1, 200));
            $route->setDescription($this->faker->text);

            /** @var Trip $trip */
            $trip = $this->getRandomReference('trips');

            $route->setTrip($trip);

            return $route;
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
