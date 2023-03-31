<?php

namespace App\Controller;

use App\Entity\Content;
use App\Repository\ContentRepository;
use App\Security\Voter\ContentVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage', methods: ['GET', 'POST'])]
    public function index(ContentRepository $contentRepository): Response
    {
        $contents = array_filter(
            $contentRepository->findAll(),
            fn (Content $content): bool => $this->isGranted(ContentVoter::VIEW, $content)
        );

        return $this->render('homepage/index.html.twig', [
            'contents' => $contents,
        ]);
    }
}
