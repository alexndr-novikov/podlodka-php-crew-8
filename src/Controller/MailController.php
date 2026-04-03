<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mail')]
class MailController extends AbstractController
{
    #[Route('', name: 'mail_index')]
    public function index(): Response
    {
        return $this->render('mail/index.html.twig');
    }

    #[Route('/send', name: 'mail_send', methods: ['POST'])]
    public function send(Request $request, MailerInterface $mailer): Response
    {
        $to = $request->request->get('to', 'test@example.com');
        $subject = $request->request->get('subject', 'Test from Docker Workshop');
        $body = $request->request->get('body', 'Hello from Symfony + Mailpit!');

        $email = (new Email())
            ->from('workshop@example.com')
            ->to($to)
            ->subject($subject)
            ->html(sprintf('<h1>%s</h1><p>%s</p>', htmlspecialchars($subject), nl2br(htmlspecialchars($body))));

        $mailer->send($email);

        if ($request->getPreferredFormat() === 'json') {
            return new JsonResponse(['status' => 'sent', 'to' => $to, 'subject' => $subject]);
        }

        $this->addFlash('success', sprintf('Email sent to %s', $to));
        return $this->redirectToRoute('mail_index');
    }
}
