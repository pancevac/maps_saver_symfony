<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\Mailer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;

class MailerTest extends TestCase
{
    /** @test */
    public function testSendEmailConfirmationMail()
    {
        $symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects($this->once())
            ->method('send');

        $router = $this->createMock(RouterInterface::class);

        $user = new User();
        $user->setEmail('test@mail.com');
        $user->setUsername('sile');
        $user->setConfirmationToken('thisIsRandomToken');

        $mailerService = new Mailer($symfonyMailer, $router);
        $mailerService->sendEmailConfirmationMail($user);
    }

    /** @test */
    public function testSendResetPasswordEmail()
    {
        $symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects($this->once())
            ->method('send');

        $router = $this->createMock(RouterInterface::class);

        $user = new User();
        $user->setEmail('test@mail.com');
        $user->setConfirmationToken('testToken123');

        $mailerService = new Mailer($symfonyMailer, $router);
        $mailerService->sendResetPasswordEmail($user);
    }
}
