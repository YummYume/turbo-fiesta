<?php

namespace App\Controller;

use App\Entity\Content;
use App\Enum\ColorTypeEnum;
use App\Form\ContentType;
use App\Manager\FlashManager;
use App\Manager\MessageManager;
use App\Repository\ContentRepository;
use App\Security\Voter\ContentVoter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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

    #[Route('/', name: 'content', methods: ['GET', 'POST'])]
    #[IsGranted(ContentVoter::CREATE, statusCode: 403)]
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        /** @var User */
        $user = $this->getUser();

        $pagination = $paginator->paginate(
            $this->contentRepository->createQueryBuilder('c')
            ->setParameter('user', $user)
            ->where('c.createdBy = :user'),
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
                      'route' => 'content_edit',
                      'routeParams' => [
                          'id' => static fn (Content $content): string => $content->getId()->toBase32(),
                      ],
                      'icon' => 'pencil',
                  ],
              ],
          ],
          'pagination' => $pagination,
      ];

        return $this->render('content/index.html.twig', ['config' => $config]);
    }

    #[Route('/new', name: 'content_new', methods: ['GET', 'POST'])]
    #[IsGranted(ContentVoter::CREATE, statusCode: 403)]
    public function new(Request $request): Response
    {
        $content = new Content();
        $form = $this->createForm(ContentType::class, $content)->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->contentRepository->save($content, true);

                $this->flashManager->flash('success', 'flash.new.success');

                return $this->redirectToRoute('content_edit', [
                    'id' => $content->getId(),
                ], Response::HTTP_SEE_OTHER);
            }
            $this->flashManager->flash('error', 'flash.form.error');

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('content/_new_content.html.twig', [
                    'content' => $content,
                    'form' => $form,
                ]);
            }
        }

        return $this->render('content/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/show/{slug}', name: 'app_content_show', methods: ['GET', 'POST'])]
    #[IsGranted(ContentVoter::VIEW, 'content')]
    public function show(Content $content): Response
    {
        return $this->render('content/show.html.twig', [
            'content' => $content,
        ]);
    }

    #[Route('/edit/{id}', name: 'content_edit', methods: ['GET', 'POST'])]
    #[IsGranted(ContentVoter::EDIT, subject: 'content', statusCode: 403)]
    public function edit(Request $request, Content $content): Response
    {
        $form = $this->createForm(ContentType::class, $content)->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->contentRepository->save($content, true);

                $this->flashManager->flash(ColorTypeEnum::Success->value, 'flash.common.updated');
            } else {
                $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_form');
            }

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('content/stream/edit.stream.html.twig', [
                    'content' => $content,
                    'form' => $form->isValid() ? $this->createForm(ContentType::class, $content) : $form,
                ]);
            } elseif ($form->isValid()) {
                return $this->redirectToRoute('content_edit', [
                    'id' => $content->getId()->toBase32(),
                ], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('content/edit.html.twig', [
            'content' => $content,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'content_delete', methods: ['POST'])]
    #[IsGranted(ContentVoter::DELETE, subject: 'content', statusCode: 403)]
    public function delete(Request $request, Content $content): Response
    {
        if ($this->isCsrfTokenValid('delete-'.$content->getId()->toBase32(), $request->request->get('_token'))) {
            $this->contentRepository->remove($content, true);

            $this->flashManager->flash(ColorTypeEnum::Success->value, 'flash.common.deleted');
        } else {
            $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_csrf');
        }

        return $this->redirectToRoute('content', status: Response::HTTP_SEE_OTHER);
    }

    #[Route('/send-notification/{id}', name: 'content_send_notification', methods: ['POST'])]
    #[IsGranted(ContentVoter::EDIT, subject: 'content', statusCode: 403)]
    public function sendValidationEmail(Request $request, Content $content, MessageManager $messageManager): Response
    {
        if ($this->isCsrfTokenValid('publish-'.$content->getId()->toBase32(), $request->request->get('_token'))) {
            if ($content->isPublished()) {
                $notifCount = $messageManager->createMessagesForContent($content, true);

                $this->flashManager->flash(ColorTypeEnum::Success->value, 'flash.content.notification_email_sent', [
                    'notifCount' => $notifCount,
                ]);
            } else {
                $this->flashManager->flash(ColorTypeEnum::Warning->value, 'flash.content.not_published');
            }
        } else {
            $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_csrf');
        }

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('content/stream/notification_email.stream.html.twig', [
                'content' => $content,
            ]);
        }

        return $this->redirectToRoute('content_edit', ['id' => $content->getId()->toBase32()], Response::HTTP_SEE_OTHER);
    }

    #[Route(
        '/published/{id}',
        name: 'content_publish',
        methods: ['POST'],
        condition: ('"'.TurboBundle::STREAM_FORMAT.'" === request.getPreferredFormat()')
    )]
    #[IsGranted(ContentVoter::EDIT, subject: 'content', statusCode: 403)]
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
            ]);
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
            'formRoute' => 'content_publish',
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
