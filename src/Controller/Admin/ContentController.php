<?php

namespace App\Controller\Admin;

use App\Entity\Content;
use App\Enum\ColorTypeEnum;
use App\Form\ContentType;
use App\Manager\FlashManager;
use App\Manager\MessageManager;
use App\Repository\ContentRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/content')]
final class ContentController extends AbstractController
{
    public function __construct(
        private readonly FlashManager $flashManager,
        private readonly ContentRepository $contentRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    #[Route('/', name: 'admin_content', methods: ['GET', 'POST'])]
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

    #[Route('/new', name: 'admin_content_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $content = new Content();
        $form = $this->createForm(ContentType::class, $content)->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->contentRepository->save($content, true);

                $this->flashManager->flash('success', 'flash.new.success', translationDomain: 'admin');

                return $this->redirectToRoute('admin_content_edit', [
                    'id' => $content->getId(),
                ], Response::HTTP_SEE_OTHER);
            }
            $this->flashManager->flash('error', 'flash.form.error', translationDomain: 'admin');

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('admin/content/_new_content.html.twig', [
                    'content' => $content,
                    'form' => $form,
                ]);
            }
        }

        return $this->render('admin/content/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_content_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Content $content): Response
    {
        $form = $this->createForm(ContentType::class, $content)->handleRequest($request);

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
                    'form' => $form->isValid() ? $this->createForm(ContentType::class, $content) : $form,
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

    #[Route('/{id}', name: 'admin_content_delete', methods: ['POST'])]
    public function delete(Request $request, Content $content): Response
    {
        if ($this->isCsrfTokenValid('delete-'.$content->getId()->toBase32(), $request->request->get('_token'))) {
            $this->contentRepository->remove($content, true);

            $this->flashManager->flash(ColorTypeEnum::Success->value, 'flash.common.deleted', translationDomain: 'admin');
        } else {
            $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_csrf');
        }

        return $this->redirectToRoute('admin_content', status: Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/send-notification', name: 'admin_content_send_notification', methods: ['POST'])]
    public function sendValidationEmail(Request $request, Content $content, MessageManager $messageManager): Response
    {
        if ($this->isCsrfTokenValid('publish-'.$content->getId()->toBase32(), $request->request->get('_token'))) {
            if ($content->isPublished()) {
                $notifCount = $messageManager->createMessagesForContent($content, true);

                $this->flashManager->flash(ColorTypeEnum::Success->value, 'flash.content.notification_email_sent', [
                    'notifCount' => $notifCount,
                ], translationDomain: 'admin');
            } else {
                $this->flashManager->flash(ColorTypeEnum::Warning->value, 'flash.content.not_published', translationDomain: 'admin');
            }
        } else {
            $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_csrf');
        }

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('admin/content/stream/notification_email.stream.html.twig', [
                'content' => $content,
            ]);
        }

        return $this->redirectToRoute('admin_content_edit', ['id' => $content->getId()->toBase32()], Response::HTTP_SEE_OTHER);
    }

    #[Route(
        '/{id}/published',
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
