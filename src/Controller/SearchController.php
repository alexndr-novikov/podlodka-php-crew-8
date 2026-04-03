<?php

namespace App\Controller;

use App\Service\MeilisearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/search')]
class SearchController extends AbstractController
{
    public function __construct(
        private readonly MeilisearchService $meilisearch,
    ) {
    }

    #[Route('', name: 'search_index')]
    public function index(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $results = [];

        if ($query !== '') {
            $results = $this->meilisearch->search($query);
        }

        if ($request->getPreferredFormat() === 'json') {
            return new JsonResponse($results);
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results,
        ]);
    }

    #[Route('/add', name: 'search_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $name = $request->request->get('name', '');
        $email = $request->request->get('email', '');

        if ($name && $email) {
            $this->meilisearch->addDocuments([
                ['id' => uniqid(), 'name' => $name, 'email' => $email],
            ]);
            $this->addFlash('success', sprintf('Added: %s', $name));
        }

        return $this->redirectToRoute('search_index');
    }
}
