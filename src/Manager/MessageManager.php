<?php

namespace App\Manager;

use App\Entity\Category;
use App\Entity\Content;
use App\Entity\Message;
use App\Entity\Profile;
use App\Manager\Email\ContentEmailManager;
use App\Repository\MessageRepository;

final class MessageManager
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly ContentEmailManager $contentEmailManager
    ) {
    }

    public function createMessagesForContent(Content $content, bool $withEmail = true): void
    {
        /** @var Category $category */
        foreach ($content->getCategories() as $category) {
            /** @var Profile $profile */
            foreach ($category->getProfiles() as $profile) {
                $message = (new Message())
                    ->setContent($content)
                    ->setProfile($profile)
                ;

                $this->messageRepository->save($message, true);

                if ($withEmail) {
                    $this->contentEmailManager->sendNewContentEmail($content, $profile->getUser()->getEmail());
                }
            }
        }
    }
}
