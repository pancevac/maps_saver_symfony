<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\FormErrorsSerializer;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationController extends AbstractController
{
    /**
     * Register new user.
     *
     * @Route("/api/register", name="app_register", methods={"POST"})
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     type="json",
     *     description="Body of the request, containing email, username and password fields.",
     *     @SWG\Schema(
     *         type="object",
     *         required={"email", "name", "password"},
     *         @SWG\Property(property="email", type="string", description="Email address of new user.", example="test@mail.com"),
     *         @SWG\Property(property="name", type="string", description="Username of new user.", example="username"),
     *         @SWG\Property(property="password", type="string", description="Raw password of new user.", example="password123"),
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Object with message about successful operation and (option) redirect login route.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(property="message", type="string"),
     *         @SWG\Property(property="redirect", type="string")
     *     )
     * )
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     *
     * @param FormErrorsSerializer $errorsSerializer
     * @return JsonResponse
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, FormErrorsSerializer $errorsSerializer): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        // Fetch the data to the form
        $body = $request->getContent();
        $data = json_decode($body, true);

        // Submit form
        $form->submit($data);

        if ($form->isValid()) {
            // set username from name field
            $user->setUsername($form->get('name')->getData());
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Successfully registered user',
                'redirect' => $this->generateUrl('api_login_check')
            ]);
        }

        return new JsonResponse(
            $errorsSerializer->getErrors($form),
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
