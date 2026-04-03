<?php

namespace App\Controller;

use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/storage')]
class StorageController extends AbstractController
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
    ) {
    }

    #[Route('', name: 'storage_index')]
    public function index(): Response
    {
        $files = [];
        foreach ($this->defaultStorage->listContents('/', false) as $item) {
            $files[] = [
                'path' => $item->path(),
                'type' => $item->isFile() ? 'file' : 'dir',
                'size' => $item->isFile() ? $this->defaultStorage->fileSize($item->path()) : null,
            ];
        }

        return $this->render('storage/index.html.twig', ['files' => $files]);
    }

    #[Route('/upload', name: 'storage_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $file = $request->files->get('file');
        if (!$file) {
            $this->addFlash('error', 'No file uploaded');
            return $this->redirectToRoute('storage_index');
        }

        $path = 'uploads/' . uniqid() . '_' . $file->getClientOriginalName();
        $this->defaultStorage->write($path, $file->getContent());

        if ($request->getPreferredFormat() === 'json') {
            return new JsonResponse(['status' => 'uploaded', 'path' => $path]);
        }

        $this->addFlash('success', sprintf('File uploaded: %s', $path));
        return $this->redirectToRoute('storage_index');
    }

    #[Route('/delete/{path}', name: 'storage_delete', methods: ['POST'], requirements: ['path' => '.+'])]
    public function delete(string $path): Response
    {
        if (str_contains($path, '..')) {
            throw $this->createNotFoundException();
        }

        $this->defaultStorage->delete($path);
        $this->addFlash('success', sprintf('Deleted: %s', $path));
        return $this->redirectToRoute('storage_index');
    }
}
