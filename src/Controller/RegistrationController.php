<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\FormErrorsSerializer;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationController extends AbstractController
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

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
            $user->setConfirmationToken($this->generateToken());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $this->sendEmailConfirmationMail($user);

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

    /**
     * Handle activation of user account.
     *
     * @Route("/account/confirm/{token}/{email}", name="confirm_account", methods={"GET"})
     *
     * @param string $token
     * @param string $email
     * @return Response
     */
    public function confirmAccount(string $token, string $email): Response
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findOneBy([
            'email' => $email,
            'confirmationToken' => $token,
        ]);

        if (!$user) {
            return new Response('Account can not be verified!', 400);
        }
        if ($user->getActive()) {
            return new Response('Account has been already activated!', 400);
        }

        $user->setActive(true);
        $em->flush();

        return new Response('Account activated! Visit <a href="https://maps-saver.netlify.com/login">Login Page</a>');
    }

    /**
     * @Route("/api/account/resend/{email}", name="resend_confirm", methods={"GET"})
     * @SWG\Parameter(
     *     name="email",
     *     in="path",
     *     type="string",
     *     description="Email address to resend activation link.",
     * )
     * @SWG\Response(
     *     response="200",
     *     description="The success message",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *              property="message",
     *              type="string"
     *          )
     *     )
     * )
     * @SWG\Response(
     *     response="400",
     *     description="Return error message",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *              property="error",
     *              type="string"
     *          )
     *     )
     * )
     *
     * @param string $email
     * @return JsonResponse
     */
    public function resendEmailConfirmationMail(string $email): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) return new JsonResponse(['error' => 'Unknown user account'], JsonResponse::HTTP_BAD_REQUEST);
        if ($user->getActive()) return new JsonResponse(['error' => 'Account has been already activated!'], JsonResponse::HTTP_BAD_REQUEST);

        $user->setConfirmationToken($this->generateToken());
        $em->flush();

        $this->sendEmailConfirmationMail($user);

        return new JsonResponse([
            'message' => 'Activation link has been resend. Please check specified email address!'
        ]);
    }

    private function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function sendEmailConfirmationMail(User $user)
    {
        $confirmationLink = $this->generateUrl('confirm_account', [
            'token' => $user->getConfirmationToken(),
            'email' => $user->getUsername()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from('hello@mail.com')
            ->to($user->getEmail())
            ->subject('Maps Saver Email confirmation.')
            ->text('Please confirm your email address on link: ' . $confirmationLink);

        $this->mailer->send($email);
    }
}
