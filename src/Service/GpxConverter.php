<?php


namespace App\Service;


use App\Entity\Point;
use App\Entity\Route;
use App\Entity\Track;
use App\Entity\Trip;
use Doctrine\Common\Collections\Collection;
use phpGPX\Models\GpxFile;
use phpGPX\Models\Track as GPXTrack;
use phpGPX\Models\Point as GPXPoint;
use phpGPX\Models\Route as GPXRoute;
use phpGPX\Models\Segment as GPXSegment;
use phpGPX\Models\Metadata as GPXMetadata;
use phpGPX\phpGPX;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class GpxConverter
{
    /**
     * @var Request|null
     */
    private $request;
    /**
     * @var phpGPX
     */
    private $GPX;

    /**
     * @param RequestStack $request
     * @param phpGPX $GPX
     */
    public function __construct(RequestStack $request, phpGPX $GPX)
    {
        $this->request = $request->getCurrentRequest();
        $this->GPX = $GPX;
    }

    /**
     * Return gpx file with hydrated data.
     *
     * @param Trip $trip
     * @return GpxFile
     */
    public function makeGPXFile(Trip $trip): GpxFile
    {
        $gpx = new GpxFile();

        // Load trip relations(tracks, routes, waypoints)
        $tracks = $trip->getTracks();
        $routes = $trip->getRoutes();
        $waypoints = $trip->getPoints();

        // fill gpxFile Object with composed tracks, waypoints and routes...
        $gpx->creator       = $trip->getCreator();
        $gpx->tracks        = $this->composeTracks($tracks);
        $gpx->waypoints     = $this->composeWaypoints($waypoints);
        $gpx->routes        = $this->composeRoutes($routes);
        //$gpx->metadata      = $this->composeMetadata();

        return $gpx;
    }

    /**
     * Compose tracks with segment and its points(track-points).
     *
     * @param Collection $tracks
     * @return Track[]
     */
    private function composeTracks(Collection $tracks): array
    {
        $mappedTracks = $tracks->map(function (Track $trackEntity) {
            $segment = new GPXSegment();
            $track = new GPXTrack();
            $points = $this->composePoints($trackEntity->getPoints(), GPXPoint::TRACKPOINT);
            $segment->points = $points;
            $track->segments[] = $segment;

            return $track;
        });

        return $mappedTracks->toArray();
    }

    /**
     * Compose route with its point(route-points).
     *
     * @param Collection $routes
     * @return Route[]
     */
    private function composeRoutes(Collection $routes): array
    {
        $mappedRoutes = $routes->map(function (Route $routeEntity) {
            $route = new GPXRoute();
            $route->points = $this->composePoints($routeEntity->getPoints(), GPXPoint::ROUTEPOINT);

            return $route;
        });

        return $mappedRoutes->toArray();
    }

    /**
     * @param Collection $waypoints
     * @return Point[]
     */
    private function composeWaypoints(Collection $waypoints): array
    {
        return $this->composePoints($waypoints, GPXPoint::WAYPOINT);
    }

    /**
     * @param Collection $points
     * @param string $pointType
     * @return Point[]
     */
    private function composePoints(Collection $points, string $pointType): array
    {
        $mappedPoints = $points->map(function (Point $pointEntity) use ($pointType) {
            $point = new GPXPoint($pointType);
            $point->latitude 			= $pointEntity->getLatitude();
            $point->longitude 			= $pointEntity->getLongitude();
            $point->elevation 			= $pointEntity->getElevation();
            $point->time 				= $pointEntity->getTime();
            $point->name                = $pointEntity->getName();
            $point->description         = $pointEntity->getDescription();

            return $point;
        });

        return $mappedPoints->toArray();
    }

    /**
     * Compose metadata from passed array.
     *
     * @param array $data
     * @return GPXMetadata
     */
    private function composeMetadata(array $data = []): GPXMetadata
    {
        // TODO implement
    }
}