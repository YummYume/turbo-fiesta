<?php

namespace App\Manager\Email;

use App\Entity\Content;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ContentEmailManager
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack
    ) {
    }

    public function sendNewContentEmail(Content $content, string $to): void
    {
        $email = (new TemplatedEmail())
            ->to($to)
            ->subject($this->translator->trans('new_content.subject', domain: 'email'))
            ->htmlTemplate('email/content/new_content_email.html.twig')
            ->context([
                'locale' => $this->requestStack->getCurrentRequest()->getLocale(),
                'content' => $content,
            ])
        ;

        $this->mailer->send($email);
    }
}
