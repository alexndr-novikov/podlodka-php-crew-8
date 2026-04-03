<?php

namespace App\Controller;

use App\Entity\User;
use App\Message\ProcessImageUpload;
use App\Message\SendWelcomeEmail;
use App\Service\MeilisearchService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/onboarding')]
class OnboardingController extends AbstractController
{
    #[Route('', name: 'onboarding_index')]
    public function index(): Response
    {
        return $this->render('onboarding/index.html.twig');
    }

    #[Route('/register', name: 'onboarding_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        FilesystemOperator $defaultStorage,
        MeilisearchService $meilisearch,
        MessageBusInterface $bus,
    ): Response {
        $name = $request->request->get('name', '');
        $email = $request->request->get('email', '');
        $avatar = $request->files->get('avatar');

        if (!$name || !$email) {
            $this->addFlash('error', 'Name and email are required');
            return $this->redirectToRoute('onboarding_index');
        }

        // 1. Save user to PostgreSQL
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);

        // 2. Upload avatar to S3
        if ($avatar) {
            $path = 'avatars/' . uniqid() . '_' . $avatar->getClientOriginalName();
            $defaultStorage->write($path, $avatar->getContent());
            $user->setAvatarUrl($path);
        }

        $em->persist($user);
        $em->flush();

        // 3. Index in Meilisearch
        $meilisearch->addDocuments([
            [
                'id' => (string) $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ],
        ]);

        // 4. Dispatch welcome email (async via Messenger)
        $bus->dispatch(new SendWelcomeEmail($user->getId()));

        // 5. Dispatch image processing if avatar uploaded
        if ($avatar) {
            $bus->dispatch(new ProcessImageUpload(
                userId: $user->getId(),
                filePath: $user->getAvatarUrl(),
            ));
        }

        $this->addFlash('success', sprintf('User "%s" registered! Check Mailpit for the welcome email.', $name));
        return $this->redirectToRoute('onboarding_status', ['id' => $user->getId()]);
    }

    #[Route('/{id}/status', name: 'onboarding_status')]
    public function status(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('onboarding/status.html.twig', [
            'user' => $user,
        ]);
    }
}
