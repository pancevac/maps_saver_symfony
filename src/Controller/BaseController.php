<?php


namespace App\Controller;


use App\Entity\User;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseController extends AbstractController
{
    /**
     * @return object|UserInterface|User|null
     */
    protected function getUser()
    {
        return parent::getUser();
    }

    /**
     * Return currently auth user.
     *
     * @Route("/api/user", name="user", methods={"GET"})
     * @Security(name="Bearer")
     * @SWG\Response(
     *     response="200",
     *     description="Return currently auth user.",
     *     @Model(type=User::class, groups={"main"})
     * )
     *
     * @return JsonResponse
     */
    public function userJson(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json($user, 200, [], ['groups' => 'main']);
    }

}