<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixture extends BaseFixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(5, 'users', function ($i) {
            $user = new User();
            $user->setEmail("user$i@mail.com");
            $user->setUsername($this->faker->firstName);
            $user->setPassword($this->passwordEncoder->encodePassword($user, 'password'));
            $user->setActive(true);
            $user->setConfirmationToken($this->generateToken());

            return $user;
        });

        $manager->flush();
    }

    private function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
