<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RegistrationControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function test_register()
    {
        $this->registerUser();

        $this->assertResponseIsSuccessful();
        $this->assertEmailCount(1);

        $email = $this->getMailerMessage(0);
        $this->assertEmailHeaderSame($email, 'To', 'test123@mail.com');
        $this->assertEmailHeaderSame($email, 'Subject', 'Maps Saver Email confirmation.');

        // Lastly assert content of email
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'test123@mail.com']);
        $link = $this->generateConfirmationLink($user);

        $this->assertEmailTextBodyContains($email, 'Please confirm your email address on link: ' . $link);
    }

    public function test_register_if_user_already_exist()
    {
        $this->registerUser();
        $this->registerUser();
        $this->assertResponseStatusCodeSame(422);
    }

    public function test_confirm_account()
    {
        // first register user
        $this->registerUser();

        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'test123@mail.com']);

        // assert that created user is not activated
        $this->assertFalse($user->getActive());

        $link = $this->generateConfirmationLink($user);

        // visit activation link
        $newClient = static::createClient();
        $newClient->request('GET', $link);

        // assert that user is activated
        $refreshedUser = $em->getRepository(User::class)->findOneBy(['email' => 'test123@mail.com']);
        $this->assertTrue($refreshedUser->getActive());
    }

    public function test_confirm_account_if_user_is_already_activated()
    {
        $this->registerUser();

        /** @var User $user */
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'test123@mail.com']);
        $user->setActive(true);
        $em->flush();

        $link = $this->generateConfirmationLink($user);
        $client = static::createClient();
        $client->request('GET', $link);

        $this->assertResponseStatusCodeSame(400);
    }

    public function test_confirm_account_if_user_is_have_invalid_token()
    {
        $this->registerUser();

        /** @var User $user */
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'test123@mail.com']);

        $user->setConfirmationToken('invalidToken123');
        $link = $this->generateConfirmationLink($user);

        $client = static::createClient();
        $client->request('GET', $link);

        $this->assertResponseStatusCodeSame(400);
    }

    public function test_resend_email_confirmation_mail()
    {
        $this->registerUser();

        /** @var User $user */
        $route = self::$container->get(RouterInterface::class);
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'test123@mail.com']);

        $link = $route->generate('resend_confirm', ['email' => $user->getEmail()]);
        $client = self::createClient();
        $client->request('GET', $link);

        $this->assertResponseIsSuccessful();
        $this->assertEmailCount(1);

        $email = $this->getMailerMessage(0);
        $this->assertEmailHeaderSame($email, 'To', $user->getEmail());
        $this->assertEmailHeaderSame($email, 'Subject', 'Maps Saver Email confirmation.');
    }

    public function test_resend_email_confirmation_mail_if_already_activated()
    {
        $this->registerUser();

        /** @var User $user */
        $route = self::$container->get(RouterInterface::class);
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'test123@mail.com']);
        $user->setActive(true);
        $em->flush();

        $link = $route->generate('resend_confirm', ['email' => $user->getEmail()]);
        $client = self::createClient();
        $client->request('GET', $link);

        $this->assertResponseStatusCodeSame(400);
    }

    public function test_resend_email_confirmation_mail_if_email_is_invalid()
    {
        $route = self::$container->get(RouterInterface::class);
        $link = $route->generate('resend_confirm', ['email' => 'invalid@mail.com']);

        $client = self::createClient();
        $client->request('GET', $link);

        $this->assertResponseStatusCodeSame(400);
    }

    private function generateConfirmationLink(User $user)
    {
        $router = self::$container->get(RouterInterface::class);

        return $router->generate('confirm_account', [
            'token' => $user->getConfirmationToken(),
            'email' => $user->getUsername()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function registerUser()
    {
        // first register user
        $client = static::createClient();
        return $client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'test',
                'email' => 'test123@mail.com',
                'password' => 'password123'
            ])
        );
    }
}
