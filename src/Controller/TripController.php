<?php

namespace App\Controller;

use App\Repository\TripRepository;
use App\Service\GpxConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TripController extends AbstractController
{
    /**
     * Return list of trips resource owned by auth user.
     *
     * @Route("/api/trips", name="trip", methods={"GET"})
     *
     * @param TripRepository $repository
     * @return JsonResponse
     */
    public function index(TripRepository $repository): JsonResponse
    {
        $user = $this->getUser();

        $trips = $repository->findBy(
            ['user' => $user],
            ['updatedAt' => 'DESC']
        );

        return $this->json([
            'trips' => $trips
        ], 200, [], ['groups' => 'main']);
    }

    /**
     * Return specific trip resource owned by auth user.
     *
     * @Route("/api/trips/{id}", name="trip_show", methods={"GET"})
     *
     * @param int $id
     * @param TripRepository $repository
     * @return JsonResponse
     */
    public function show(int $id, TripRepository $repository): JsonResponse
    {
        $trip = $repository->findOneBy([
            'id' => $id,
            'user' => $this->getUser()
        ]);

        if (!$trip) {
            return $this->json(['message' => 'Resource not found!'], 404);
        }

        return $this->json($trip, 200, [], ['groups' => 'main']);
    }

    /**
     * Return generated gxp file owned by the auth user.
     *
     * @Route("/api/trips/gpx/{id}", name="trip_gpx", methods={"GET"})
     *
     * @param int $id
     * @param TripRepository $repository
     * @param GpxConverter $converter
     * @return JsonResponse
     */
    public function getGpx(int $id, TripRepository $repository, GpxConverter $converter): JsonResponse
    {
        $trip = $repository->findOneBy([
            'user' => $this->getUser(),
            'id' => $id
        ]);

        $gpx = $converter->makeGPXFile($trip)
            ->toXML()
            ->saveXML();

        return new JsonResponse($gpx);
    }

    /**
     * Save new trip and handle validation and uploading of gpx file.
     *
     * @Route("/api/trips", name="trip_store", methods={"POST"})
     *
     * @param Request $request
     * @param GpxConverter $converter
     * @return JsonResponse
     */
    public function store(Request $request, GpxConverter $converter): JsonResponse
    {
        // TODO implement
    }

    /**
     * Update trip info owned by the auth user.
     *
     * @Route("/api/trips/{id}", name="trip_update", methods={"PUT", "PATCH"})
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(int $id, Request $request): JsonResponse
    {
        // TODO implement
    }

    /**
     * Delete trip owned by the auth user.
     *
     * @Route("/api/{id}", name="trip_delete", methods={"DELETE"})
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        // TODO implement
    }
}
