<?php

namespace App\Controller\Admin;

use App\Entity\Content;
use App\Enum\ColorTypeEnum;
use App\Form\Admin\BlockType;
use App\Manager\FlashManager;
use App\Repository\ContentRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\Turbo\TurboBundle;

final class ContentController extends AbstractController
{
    public function __construct(
        private readonly FlashManager $flashManager,
        private readonly ContentRepository $contentRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    #[Route('/contents', name: 'admin_content', methods: ['GET', 'POST'])]
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        $pagination = $paginator->paginate(
            $this->contentRepository->createQueryBuilder('c'),
            $request->query->getInt('page', 1),
            5
        );

        $config = [
          'cols' => [
              'title' => [
                  'type' => 'text',
                  'label' => 'content.title',
                  'queryKey' => 'c.title',
              ],
              'published' => $this->getContentPublishedRowOptions(),
              'actions' => [
                  'info' => [
                      'route' => 'admin_content_show',
                      'routeParams' => [
                          'id' => static fn (Content $content): string => $content->getId()->toBase32(),
                      ],
                      'icon' => 'eye',
                  ],
                  'accent' => [
                      'route' => 'admin_content_edit',
                      'routeParams' => [
                          'id' => static fn (Content $content): string => $content->getId()->toBase32(),
                      ],
                      'icon' => 'pencil',
                  ],
              ],
          ],
          'pagination' => $pagination,
      ];

      return $this->render('admin/content/index.html.twig', ['config' => $config]);
    }

    #[Route('/contents', name: 'admin_content_show', methods: ['GET'])]
    public function show(): Response
    {
        return  $this->render('admin/content/index.html.twig', ['config' => []]);
    }

    #[Route('/{id}/edit', name: 'admin_content_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Content $content): Response
    {
        $form = $this->createForm(BlockType::class, $content)->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->contentRepository->save($content, true);

                $this->flashManager->flash(ColorTypeEnum::Success->value, 'flash.common.updated', translationDomain: 'admin');
            } else {
                $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_form');
            }

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('admin/content/stream/edit.stream.html.twig', [
                    'content' => $content,
                    'form' => $form->isValid() ? $this->createForm(BlockType::class, $content) : $form,
                ]);
            } elseif ($form->isValid()) {
                return $this->redirectToRoute('admin_content_edit', [
                    'id' => $content->getId()->toBase32(),
                ], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('admin/content/edit.html.twig', [
            'content' => $content,
            'form' => $form,
        ]);
    }

    #[Route(
        '/contents/{id}/published',
        name: 'admin_content_publish',
        methods: ['POST'],
        condition: ('"'.TurboBundle::STREAM_FORMAT.'" === request.getPreferredFormat()')
    )]
    public function setContentPublished(Content $content, Request $request): Response
    {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        $token = $request->get('_token');

        if ($this->isCsrfTokenValid(sprintf('switch-%s-content', $content->getId()->toBase32()), $token)) {
            $published = 'on' === $request->get('_published');

            $content->setPublished($published);
            $this->contentRepository->save($content, true);

            $this->flashManager->flash(ColorTypeEnum::Success->value, $published ? 'flash.content.published' : 'flash.content.unpublished', [
                'title' => $content->getTitle(),
            ], 'admin');
        } else {
            $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_csrf');
        }

        return $this->render('components/table/row/stream/switch.stream.html.twig', [
            'target' => sprintf('content-publish-form_%s', $content->getId()->toBase32()),
            'config' => [
                'value' => $content->isPublished(),
                'item' => $content,
                'col' => $this->getContentPublishedRowOptions(),
            ],
        ]);
    }

    private function getContentPublishedRowOptions(): array
    {
        return [
            'type' => 'switch',
            'label' => 'content.published',
            'queryKey' => 'c.published',
            'formRoute' => 'admin_content_publish',
            'formRouteParams' => [
                'id' => static fn (Content $content): string => $content->getId()->toBase32(),
            ],
            'extras' => [
                'id' => static fn (Content $content): string => sprintf('content-publish-form_%s', $content->getId()->toBase32()),
                'name' => '_published',
                'csrfToken' => fn (Content $content): string => $this->csrfTokenManager->getToken(
                    sprintf('switch-%s-content', $content->getId()->toBase32())
                )->getValue(),
                'submitOnChange' => true,
            ],
        ];
    }
}
