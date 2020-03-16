<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AccountControllerTest extends WebTestCase
{
    protected function setUp()
    {
        self::bootKernel();
    }

    /** @test */
    public function testResetPassword()
    {
        /** @var User $user */
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy([]);

        $client = static::createClient();
        $crawler = $client->request(
            'GET',
            '/api/account/reset-password/' . $user->getEmail(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        // assert success and message
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);

        // assert that email with password reset link is send
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage(0);
        $this->assertEmailHeaderSame($email, 'To', $user->getEmail());
        $this->assertEmailHeaderSame($email, 'Subject', 'Maps Saver Reset Password Request.');
    }

    /** @test */
    public function testNewPassword()
    {
        /** @var User $user */
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy([]);
        $user->setConfirmationToken('someToken123');
        $em->flush();

        $client = static::createClient();
        $crawler = $crawler = $client->request(
            'PUT',
            '/api/account/new-password/' . $user->getConfirmationToken(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'password' => 'password123'
            ])
        );

        $updatedUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);

        // assert that response is ok
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);

        // assert that password is indeed changed
        $passwordEncoder = self::$container->get(UserPasswordEncoderInterface::class);
        $this->assertTrue($passwordEncoder->isPasswordValid($updatedUser, 'password123'));
    }

    public function testResetPasswordIfInvalidEmail()
    {
        $this->expectException(NotFoundHttpException::class);

        $client = static::createClient();
        $client->catchExceptions(false);
        $client->request(
            'GET',
            '/api/account/reset-password/' . 'invalid@mail.com',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );
    }

    public function testResetPasswordIfUserNotActivated()
    {
        /** @var User $user */
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy([]);
        $user->setActive(false);
        $em->flush();

        $this->expectException(NotFoundHttpException::class);

        $client = static::createClient();
        $client->catchExceptions(false);
        $client->request(
            'GET',
            '/api/account/reset-password/' . $user->getEmail(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );
    }

    /** @test */
    public function testNewPasswordCannotBeSameAsOld()
    {
        /** @var User $user */
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy([]);
        $user->setConfirmationToken('someToken123');
        $em->flush();

        $client = static::createClient();
        $crawler = $crawler = $client->request(
            'PUT',
            '/api/account/new-password/' . $user->getConfirmationToken(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'password' => 'password' // same as old
            ])
        );

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        // assert error message
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(['password' => ['The new password is same as old password.']], $responseData);
    }

    /** @test */
    public function testNewPasswordIfTokenIsInvalid()
    {
        /** @var User $user */
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy([]);
        $user->setConfirmationToken('someToken123');
        $em->flush();

        $this->expectException(NotFoundHttpException::class);

        $client = static::createClient();
        $client->catchExceptions(false);
        $crawler = $crawler = $client->request(
            'PUT',
            '/api/account/new-password/invalidtoken123',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'password' => 'password123'
            ])
        );
    }
}
