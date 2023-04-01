<?php

namespace App\Manager;

use App\Entity\Category;
use App\Entity\Content;
use App\Entity\Message;
use App\Entity\Profile;
use App\Manager\Email\ContentEmailManager;
use App\Repository\ContentRepository;
use App\Repository\MessageRepository;

final class MessageManager
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly ContentRepository $contentRepository,
        private readonly ContentEmailManager $contentEmailManager
    ) {
    }

    public function createMessagesForContent(Content $content, bool $withEmail = true): int
    {
        $notified = 0;

        /** @var Category $category */
        foreach ($content->getCategories() as $category) {
            /** @var Profile $profile */
            foreach ($category->getProfiles() as $profile) {
                if (\in_array($profile->getId()->toBase32(), $content->getNotifiedProfiles(), true)) {
                    continue;
                }

                $content->addNotifiedProfile($profile->getId());
                $message = (new Message())
                    ->setContent($content)
                    ->setProfile($profile)
                ;

                $this->messageRepository->save($message, true);

                if ($withEmail) {
                    $this->contentEmailManager->sendNewContentEmail($content, $profile->getUser()->getEmail());
                }

                ++$notified;
            }
        }

        $this->contentRepository->save($content, true);

        return $notified;
    }
}
