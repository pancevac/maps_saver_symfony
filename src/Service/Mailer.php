<?php


namespace App\Service;


use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Mailer
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(MailerInterface $mailer, RouterInterface $router)
    {
        $this->mailer = $mailer;
        $this->router = $router;
    }

    public function sendEmailConfirmationMail(User $user): void
    {
        $confirmationLink = $this->router->generate('confirm_account', [
            'token' => $user->getConfirmationToken(),
            'email' => $user->getUsername()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from('service@ms-mail.sinisab.tk')
            ->to($user->getEmail())
            ->subject('Maps Saver Email confirmation.')
            ->text('Please confirm your email address on link: ' . $confirmationLink);

        $this->mailer->send($email);
    }

    public function sendResetPasswordEmail(User $user)
    {
        $appClient = 'https://maps-saver.netlify.com'; // not great solution for now...
        $token = $user->getConfirmationToken();
        $link = "$appClient/new-password/$token";

        $email = (new Email())
            ->from('service@ms-mail.sinisab.tk')
            ->to($user->getEmail())
            ->subject('Maps Saver Reset Password Request.')
            ->text('Link for changing your account password: ' . $link);

        $this->mailer->send($email);
    }
}