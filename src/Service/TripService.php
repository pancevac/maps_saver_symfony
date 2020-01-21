<?php


namespace App\Service;


use App\Entity\Point;
use App\Entity\Route;
use App\Entity\Track;
use App\Entity\Trip;
use Doctrine\ORM\EntityManagerInterface;

class TripService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Trip
     */
    private $trip;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function save(): void
    {
        $this->entityManager->persist($this->trip);
        $this->entityManager->flush();
    }

    public function setTrip(Trip $trip): self
    {
        $this->trip = $trip;
        $this->entityManager->persist($trip);

        return $this;
    }

    public function setTracks(array $tracks): self
    {
        foreach ($tracks as $track) {
            $trackEntity = new Track();
            $trackEntity->setName($track['name']);

            foreach ($track['points'] as $point) {
                $pointEntity = new Point();
                $pointEntity->hydrate($point);
                $trackEntity->addPoint($pointEntity);
            }

            $this->trip->addTrack($trackEntity);
        }

        return $this;
    }

    public function setRoutes(array $routes): self
    {
        foreach ($routes as $route) {
            $routeEntity = new Route();
            $routeEntity->setName($route['name']);

            foreach ($route['points'] as $point) {
                $pointEntity = new Point();
                $pointEntity->hydrate($point);
                $routeEntity->addPoint($pointEntity);
            }

            $this->trip->addRoute($routeEntity);
        }

        return $this;
    }

    public function setWaypoints(array $points): self
    {
        foreach ($points as $waypoint) {
            $point = new Point();
            $point->hydrate($waypoint);
            $this->trip->addPoint($point);
        }

        return $this;
    }
}