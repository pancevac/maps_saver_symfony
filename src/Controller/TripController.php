<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Repository\TripRepository;
use App\Service\TripService;
use App\Service\GpxConverter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TripController extends BaseController
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
            'data' => $trips
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

        if (!$trip) return $this->abort();

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

        if (!$trip) return $this->abort();

        $gpx = $converter->makeGPXFile($trip)
            ->toXML()
            ->saveXML();

        return new JsonResponse([
            'response' => $gpx
        ]);
    }

    /**
     * Save new trip and handle validation and uploading of gpx file.
     *
     * @Route("/api/trips", name="trip_store", methods={"POST"})
     *
     * @param Request $request
     * @param GpxConverter $converter
     * @param TripService $tripService
     * @return JsonResponse
     */
    public function store(Request $request, GpxConverter $converter, TripService $tripService): JsonResponse
    {
        // parse and load coordinates from gpx file
        try {
            $converter->load();
        } catch (\Exception $e) {
            throw new HttpException(JsonResponse::HTTP_UNPROCESSABLE_ENTITY,'Error while loading gpx file!');
        }

        // create trip
        $trip = new Trip();
        $trip->setName($request->get('name'));
        $trip->setUser($this->getUser());
        $trip->setCreator($converter->getCreator());
        $trip->setMetadata(''); // TODO implement this as json

        // TODO implement and research validation logic
        /*$errors = $validator->validate($trip);

        if (count($errors) > 0) {
            return new JsonResponse((string) $errors, JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }*/

        // save trip with relations to db
        $tripService->setTrip($trip)
            ->setTracks($converter->getTracks())
            ->setRoutes($converter->getRoutes())
            ->setWaypoints($converter->getWaypoints())
            ->save();

        return $this->json([
            'message' => 'Successfully saved trip!'
        ]);
    }

    /**
     * Update trip info owned by the auth user.
     *
     * @Route("/api/trips/{id}", name="trip_update", methods={"PUT"})
     *
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function update(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $body = $request->getContent();
        $data = json_decode($body);

        $repository = $em->getRepository(Trip::class);
        $trip = $repository->findOneBy([
            'user' => $this->getUser(),
            'id' => $id
        ]);

        if (!$trip) return $this->abort();

        $trip->setName($data->name);

        // Validate
        $errors = $validator->validate($trip);

        if (count($errors) > 0) {
            return new JsonResponse([
                'error' => 'Bla bla'
            ]);
        }

        $em->flush();

        return new JsonResponse(['message' => 'Trip successfully updated.']);
    }

    /**
     * Delete trip owned by the auth user.
     *
     * @Route("/api/trips/{id}", name="trip_delete", methods={"DELETE"})
     *
     * @param int $id
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $repository = $entityManager->getRepository(Trip::class);

        $trip = $repository->findOneBy([
            'user' => $this->getUser(),
            'id' => $id
        ]);

        if (!$trip) return $this->abort();

        $entityManager->remove($trip);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Successfully deleted trip.']);
    }

    private function abort(int $status = 404)
    {
        return new JsonResponse(['message' => 'Resource not found!'], 404);
    }
}
