<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NewPasswordType;
use App\Repository\UserRepository;
use App\Service\FormErrorsSerializer;
use App\Service\Mailer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AccountController extends AbstractController
{
    /**
     * Handle sending email with password-reset link.
     *
     * @Route("/api/account/reset-password/{email}", name="reset_password", methods={"GET"})
     * @Entity("user", expr="repository.findUserByEmail(email)")
     *
     * @SWG\Parameter(
     *     name="email",
     *     in="path",
     *     type="string",
     *     description="Email address on which to send reset-pasword link."
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
     *     response="404",
     *     description="User not found or is not activated",
     * )
     *
     * @param User $user
     * @param Mailer $mailer
     * @return JsonResponse
     */
    public function resetPassword(User $user, Mailer $mailer): JsonResponse
    {
        $user->setConfirmationToken($this->generateToken());

        $mailer->sendResetPasswordEmail($user);

        return new JsonResponse([
            'message' => 'Email with reset password link has been send.',
        ]);
    }

    /**
     * Change old password with new.
     *
     * @Route("/api/account/new-password/{token}", name="new_password", methods={"PUT"})
     * @Entity("user", expr="repository.findUserByToken(token)")
     *
     * @SWG\Parameter(
     *     name="token",
     *     in="path",
     *     type="string",
     *     description="Token for reseting password."
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     type="json",
     *     description="Body of the request, containing password field.",
     *     @SWG\Schema(
     *         type="object",
     *         required={"password"},
     *         @SWG\Property(property="password", type="string", description="New plain password for user.", example="password123")
     *     )
     * ),
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
     *     response="422",
     *     description="Return error message if form is invalid",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *              property="email",
     *              type="array",
     *              @SWG\Items(type="string")
     *          )
     *     )
     * )
     * @SWG\Response(
     *     response="404",
     *     description="User not found or is not activated",
     * )
     *
     * @param Request $request
     * @param User $user
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param FormErrorsSerializer $errorsSerializer
     * @return JsonResponse
     */
    public function newPassword(
        Request $request,
        User $user,
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        FormErrorsSerializer $errorsSerializer
    ): JsonResponse
    {
        $form = $this->createForm(NewPasswordType::class, $user);

        // Fetch the data to the form
        $body = $request->getContent();
        $data = json_decode($body, true);

        // Submit form
        $form->submit($data);

        if ($form->isValid()) {
            // save new encoded password
            $newEncodedPass = $passwordEncoder->encodePassword($user, $form->get('password')->getData());
            $userRepository->upgradePassword($user, $newEncodedPass);

            return new JsonResponse([
                'message' => 'Password successfully changed',
                'redirect' => $this->generateUrl('api_login_check')
            ]);
        }

        return new JsonResponse(
            $errorsSerializer->getErrors($form),
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    private function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
