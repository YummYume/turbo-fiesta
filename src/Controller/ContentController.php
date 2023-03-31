<?php

namespace App\Controller;

use App\Entity\Content;
use App\Security\Voter\ContentVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/content')]
final class ContentController extends AbstractController
{
    #[Route('/{slug}', name: 'app_content_show', methods: ['GET', 'POST'])]
    #[IsGranted(ContentVoter::VIEW, 'content')]
    public function show(Content $content): Response
    {
        return $this->render('content/show.html.twig', [
            'content' => $content,
        ]);
    }
}
