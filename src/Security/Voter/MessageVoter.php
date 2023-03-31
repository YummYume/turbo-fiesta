<?php

namespace App\Security\Voter;

use App\Entity\Message;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class MessageVoter extends Voter
{
    public const DISMISS = 'DISMISS_MESSAGE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::DISMISS], true) && $subject instanceof Message;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$subject instanceof Message) {
            return false;
        }

        return match ($attribute) {
            self::DISMISS => $this->canDismiss($subject, $user),
            default => throw new \LogicException('MessageVoter - This code should not be reached.'),
        };
    }

    /**
     * @internal Only the message owner can dismiss it
     */
    private function canDismiss(Message $subject, UserInterface $currentUser): bool
    {
        return $subject->getProfile()->getUser() === $currentUser;
    }
}
