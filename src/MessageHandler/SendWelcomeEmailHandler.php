<?php

namespace App\MessageHandler;

use App\Entity\User;
use App\Message\SendWelcomeEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final readonly class SendWelcomeEmailHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendWelcomeEmail $message): void
    {
        $user = $this->em->getRepository(User::class)->find($message->userId);

        if (!$user) {
            return;
        }

        $email = (new Email())
            ->from('workshop@example.com')
            ->to($user->getEmail())
            ->subject(sprintf('Welcome, %s!', $user->getName()))
            ->html(sprintf(
                '<h1>Welcome to Docker Workshop!</h1><p>Hi %s, your account has been created.</p><p>Email: %s</p>',
                htmlspecialchars($user->getName()),
                htmlspecialchars($user->getEmail()),
            ));

        $this->mailer->send($email);
    }
}
