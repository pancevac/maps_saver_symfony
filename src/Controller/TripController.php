<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Form\TripType;
use App\Repository\TripRepository;
use App\Service\FormErrorsSerializer;
use App\Service\TripService;
use App\Service\GpxConverter;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TripController
 * @package App\Controller
 * @Security(name="Bearer")
 */
class TripController extends BaseController
{
    /**
     * Return list of trips resource owned by auth user.
     *
     * @Route("/api/trips", name="trip", methods={"GET"})
     * @SWG\Response(
     *     response="200",
     *     description="Get collection of the trips resources.",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *              property="data",
     *              type="array",
     *              @SWG\Items(ref=@Model(type=Trip::class, groups={"main"}))
     *          )
     *     )
     * )
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
     * @Entity("trip", expr="repository.findOwnedByAuthUser(id)")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unique identifier of the trip."
     * )
     * @SWG\Response(
     *     response="200",
     *     description="The trip resource.",
     *     @Model(type=Trip::class, groups={"main"})
     * )
     *
     * @param Trip $trip
     * @return JsonResponse
     */
    public function show(Trip $trip): JsonResponse
    {
        return $this->json($trip, 200, [], ['groups' => 'main']);
    }

    /**
     * Return generated gxp file owned by the auth user.
     *
     * @Route("/api/trips/gpx/{id}", name="trip_gpx", methods={"GET"})
     * @Entity("trip", expr="repository.findOwnedByAuthUser(id)")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unique identifier of the trip."
     * )
     * @SWG\Response(
     *     response="200",
     *     description="The generated gpx trip file.",
     *     @SWG\Schema(type="object", @SWG\Property(property="response",type="string"))
     * )
     *
     * @param Trip $trip
     * @param GpxConverter $converter
     * @return JsonResponse
     */
    public function getGpx(Trip $trip, GpxConverter $converter): JsonResponse
    {
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
     * @SWG\Parameter(
     *     name="name",
     *     in="formData",
     *     type="string",
     *     description="Name of the new trip."
     * )
     * @SWG\Parameter(
     *     name="trip",
     *     in="formData",
     *     type="file",
     *     description="Uploaded GPX file."
     * )
     * @SWG\Response(
     *     response="201",
     *     description="Message about successful operation.",
     *     @SWG\Schema(type="object", @SWG\Property(property="message", type="string"))
     * )
     *
     * @param Request $request
     * @param GpxConverter $converter
     * @param TripService $tripService
     * @param FormErrorsSerializer $errorsSerializer
     * @return JsonResponse
     */
    public function store(Request $request, GpxConverter $converter, TripService $tripService, FormErrorsSerializer $errorsSerializer): JsonResponse
    {
        // Create form
        $trip = new Trip();
        $form = $this->createForm(TripType::class, $trip);

        // Post the data to the form
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            return new JsonResponse(
                $errorsSerializer->getErrors($form),
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // parse and load coordinates from gpx file
        try {
            $converter->load();
        } catch (\Exception $e) {
            throw new HttpException(JsonResponse::HTTP_UNPROCESSABLE_ENTITY,'Error while loading gpx file!');
        }

        // Set rest of fields for trip
        $trip->setUser($this->getUser());
        $trip->setCreator($converter->getCreator());
        $trip->setMetadata($converter->getMetaData());

        // save trip with relations to db
        $tripService->setTrip($trip)
            ->setTracks($converter->getTracks())
            ->setRoutes($converter->getRoutes())
            ->setWaypoints($converter->getWaypoints())
            ->save();

        return new JsonResponse(['message' => 'Successfully saved trip!'], JsonResponse::HTTP_CREATED);
    }

    /**
     * Update trip info owned by the auth user.
     *
     * @Route("/api/trips/{id}", name="trip_update", methods={"PUT"})
     * @Entity("trip", expr="repository.findOwnedByAuthUser(id)")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unique identifier of the trip."
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     type="json",
     *     description="Form for updating trip.",
     *     @SWG\Schema(
     *         type="object",
     *         required={"name"},
     *         @SWG\Property(property="name", type="string", description="New name of trip.")
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Message about successful operation.",
     *     @SWG\Schema(type="object", @SWG\Property(property="message", type="string"))
     * )
     *
     * @param Trip $trip
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param FormErrorsSerializer $errorsSerializer
     * @return JsonResponse
     */
    public function update(Trip $trip, Request $request, EntityManagerInterface $entityManager, FormErrorsSerializer $errorsSerializer): JsonResponse
    {
        // Create form
        $form = $this->createForm(
            TripType::class,
            $trip,
            ['disable_trip' => true, 'trip_entity' => $trip]
        );

        // Fetch the data to the form
        $body = $request->getContent();
        $data = json_decode($body, true);

        $form->submit($data);

        if (!$form->isValid()) {
            return new JsonResponse(
                $errorsSerializer->getErrors($form),
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Trip successfully updated.']);
    }

    /**
     * Delete trip owned by the auth user.
     *
     * @Route("/api/trips/{id}", name="trip_delete", methods={"DELETE"})
     * @Entity("trip", expr="repository.findOwnedByAuthUser(id)")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unique identifier of the trip."
     * )
     * @SWG\Response(
     *     response="201",
     *     description="Message about successful operation.",
     *     @SWG\Schema(type="object", @SWG\Property(property="message", type="string"))
     * )
     *
     * @param Trip $trip
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function delete(Trip $trip, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($trip);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Successfully deleted trip.']);
    }
}
