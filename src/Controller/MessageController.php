<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Enum\ColorTypeEnum;
use App\Manager\FlashManager;
use App\Repository\MessageRepository;
use App\Security\Voter\MessageVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/message')]
final class MessageController extends AbstractController
{
    public function __construct(
        private readonly FlashManager $flashManager,
        private readonly MessageRepository $messageRepository
    ) {
    }

    #[Route('/dismiss/{id}', name: 'app_message_dismiss', methods: ['POST'])]
    #[IsGranted(MessageVoter::DISMISS, 'message')]
    public function dismiss(Request $request, Message $message): Response
    {
        $referer = $request->headers->get('referer', $this->generateUrl('app_homepage'));
        $response = new RedirectResponse($referer);

        if ($this->isCsrfTokenValid('delete-'.$message->getId(), $request->request->get('_token'))) {
            $this->messageRepository->remove($message, true);
        } else {
            $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_csrf');
        }

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('common/stream/alert.stream.html.twig');
        }

        return $response;
    }

    #[Route('/read-all', name: 'app_message_read_all', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function readAll(Request $request): Response
    {
        $referer = $request->headers->get('referer', $this->generateUrl('app_homepage'));
        $response = new RedirectResponse($referer);
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('read-all-'.$user->getId(), $request->request->get('_token'))) {
            /** @var Message $message */
            foreach ($user->getProfile()->getUnreadMessages() as $message) {
                $message->setViewed(true);
                $this->messageRepository->save($message, true);
            }
        } else {
            $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_csrf');
        }

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('common/stream/alert.stream.html.twig');
        }

        return $response;
    }
}
