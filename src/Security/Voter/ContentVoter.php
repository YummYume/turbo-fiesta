<?php

namespace App\Security\Voter;

use App\Entity\Content;
use App\Enum\ContentVisibilityEnum;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ContentVoter extends Voter
{
    public const VIEW = 'VIEW_CONTENT';
    public const CREATE = 'CREATE_CONTENT';
    public const EDIT = 'EDIT_CONTENT';
    public const DELETE = 'DELETE_CONTENT';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE], true) && $subject instanceof Content;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$subject instanceof Content) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject),
            self::CREATE => $this->canCreate(),
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            default => throw new \LogicException('ContentVoter - This code should not be reached.'),
        };
    }

    /**
     * @internal Only users with enough visibility privilege can view the content
     */
    private function canView(Content $subject): bool
    {
        return $subject->isPublished() && $this->security->isGranted($subject->getVisibility()->value);
    }

    /**
     * @internal Only employees and above can create content
     */
    private function canCreate(): bool
    {
        return $this->security->isGranted(ContentVisibilityEnum::Employee->value);
    }

    /**
     * @internal Only the user who created the content can edit it
     */
    private function canEdit(Content $subject, UserInterface $currentUser): bool
    {
        return $this->canCreate() && $subject->getCreatedBy() === $currentUser;
    }

    /**
     * @internal Only the user who created the content can delete it
     */
    private function canDelete(Content $subject, UserInterface $currentUser): bool
    {
        return $this->canCreate() && $subject->getCreatedBy() === $currentUser;
    }
}
