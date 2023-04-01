<?php

namespace App\Entity;

use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use App\Enum\ContentTypeEnum;
use App\Enum\ContentVisibilityEnum;
use App\Repository\ContentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContentRepository::class)]
class Content
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'content.title.not_blank')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'content.title.min_length', maxMessage: 'content.title.max_length')]
    #[Groups('searchable')]
    private ?string $title = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['title'])]
    #[Groups('searchable')]
    private ?string $slug = null;

    #[ORM\Column]
    private array $blocks = [];

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'contents')]
    #[Assert\Count(min: 1, minMessage: 'content.categories.min_count')]
    private Collection $categories;

    #[ORM\Column(length: 255, enumType: ContentTypeEnum::class)]
    #[Groups('searchable')]
    private ContentTypeEnum $type = ContentTypeEnum::Article;

    #[ORM\Column]
    private bool $published = false;

    #[ORM\Column(length: 255, enumType: ContentVisibilityEnum::class)]
    private ContentVisibilityEnum $visibility = ContentVisibilityEnum::Public;

    #[ORM\OneToMany(mappedBy: 'content', targetEntity: Message::class, orphanRemoval: true)]
    private Collection $messages;

    #[ORM\Column]
    private array $notifiedProfiles = [];

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function setBlocks(array $blocks): self
    {
        $this->blocks = $blocks;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        $this->categories->removeElement($category);

        return $this;
    }

    public function getType(): ContentTypeEnum
    {
        return $this->type;
    }

    public function setType(ContentTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function getVisibility(): ContentVisibilityEnum
    {
        return $this->visibility;
    }

    public function setVisibility(ContentVisibilityEnum $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setContent($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getContent() === $this) {
                $message->setContent(null);
            }
        }

        return $this;
    }

    #[Groups('searchable')]
    public function isIndexable(): bool
    {
        return $this->published && ContentVisibilityEnum::Public === $this->visibility;
    }

    public function getNotifiedProfiles(): array
    {
        return $this->notifiedProfiles;
    }

    public function addNotifiedProfile(Profile|Uuid $profile): self
    {
        $uid = $profile;

        if ($profile instanceof Profile) {
            $uid = $profile->getId();
        }

        if (!\in_array($uid, $this->notifiedProfiles, true)) {
            $this->notifiedProfiles[] = $uid->toBase32();
        }

        return $this;
    }

    public function setNotifiedProfiles(array $notifiedProfiles): self
    {
        $this->notifiedProfiles = $notifiedProfiles;

        return $this;
    }
}
