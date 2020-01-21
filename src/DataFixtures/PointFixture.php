<?php

namespace App\DataFixtures;

use App\Entity\Point;
use App\Entity\Track;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class PointFixture extends BaseFixture implements DependentFixtureInterface
{
    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(1000, 'points', function (int $i) {
            $point = new Point();
            $point->setElevation($this->faker->randomFloat(2, 10, 100));
            $point->setLatitude($this->faker->randomFloat( 2, 10, 100));
            $point->setLongitude($this->faker->randomFloat(2, 10, 100));
            $point->setTime($this->faker->dateTimeBetween('-1 month', 'now'));
            $point->setName('Point_' . $i);
            //$point->setDescription($this->faker->text);
            /** @var Track $track */
            $track = $this->getRandomReference('tracks');
            $point->setTrack($track);

            return $point;
        });

        $manager->flush();
    }


    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [
            RouteFixture::class,
            TrackFixture::class,
            TripFixture::class
        ];
    }
}
