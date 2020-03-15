<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function test_login()
    {
        /** @var User $user */
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy([]);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $user->getEmail(),
                'password' => 'password'
            ])
        );

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $responseData);
    }

    public function test_login_if_credentials_are_invalid()
    {
        /** @var User $user */
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy([]);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $user->getEmail(),
                'password' => 'password123'
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function test_login_if_user_account_is_not_activated()
    {
        /** @var User $user */
        $em = self::$container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy([]);
        $user->setActive(false);
        $em->flush();

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $user->getEmail(),
                'password' => 'password'
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
