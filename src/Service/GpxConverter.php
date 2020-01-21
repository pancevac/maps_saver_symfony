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
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
     * @var GpxFile
     */
    private $GPXFile;

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
     * Load and parse gpx file
     *
     * @param string $key
     * @return $this
     */
    public function load(string $key = 'trip'): self
    {
        /** @var UploadedFile $file */
        $uploadedFile = $this->request->files->get($key);

        $this->GPXFile = $this->GPX->load(
            $uploadedFile->getRealPath()
        );

        return $this;
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

    public function getCreator(): string
    {
        if (!$this->GPXFile instanceof GpxFile || !$this->GPXFile->creator) {
            return '';
        }

        return $this->GPXFile->creator;
    }

    /**
     * Return parsed metadata as array if any.
     *
     * @return array
     */
    public function getMetaData(): array
    {
        if (!$this->GPXFile instanceof GpxFile || !$this->GPXFile->metadata) {
            return [];
        }

        return $this->GPXFile->metadata->toArray();
    }

    /**
     * Retrieve tracks array with points.
     *
     * @return array
     */
    public function getTracks()
    {
        $tracks = $this->GPXFile->tracks;

        return array_map(function (GPXTrack $track) {
            return [
                'name' => $track->name,
                'description' => $track->getPoints(),
                'points' => $this->mapPoints($track->getPoints()),
            ];
        }, $tracks);
    }

    /**
     * Retrieve routes array with points.
     *
     * @return array
     */
    public function getRoutes()
    {
        $routes = $this->GPXFile->routes;

        return array_map(function (GPXRoute $route) {
            return [
                'name' => $route->name,
                'description' => $route->description,
                'points' => $this->mapPoints($route->getPoints()),
            ];
        }, $routes);
    }

    /**
     * Retrieve waypoints array as points.
     *
     * @return array
     */
    public function getWaypoints()
    {
        $waypoints =  $this->GPXFile->waypoints;

        return $this->mapPoints($waypoints);
    }

    /**
     * Map points array of objects to new array.
     *
     * @param array $points
     * @return array
     */
    protected function mapPoints(array $points): array
    {
        return array_map(function (GPXPoint $point) {
            return [
                'latitude' => $point->latitude,
                'longitude' => $point->longitude,
                'elevation' => $point->elevation,
                'time' => $point->time,
                'name' => $point->name,
                'description' => $point->description,
            ];
        }, $points);
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