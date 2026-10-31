<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\BookCategory;
use App\Form\BookType;
use App\Grid\BookGrid;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Metadata\BulkDelete;
use Sylius\Resource\Metadata\Create;
use Sylius\Resource\Metadata\Delete;
use Sylius\Resource\Metadata\Index;
use Sylius\Resource\Metadata\Show;
use Sylius\Resource\Metadata\Update;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[AsResource(
    section: 'admin',
    formType: BookType::class,
    templatesDir: '@SyliusAdminUi/crud',
    routePrefix: '/admin',
    operations: [
        new Create(),
        new Update(),
        new Index(grid: BookGrid::class),
        new Delete(),
        new BulkDelete(),
        new Show(),
    ],
)]
class Book implements ResourceInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $title = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $authorName = '';

    #[ORM\ManyToOne(targetEntity: Author::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Author $author = null;

    #[ORM\Column(nullable: true, enumType: BookCategory::class)]
    private ?BookCategory $category = null;

    #[ORM\Column]
    private bool $available = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $summary = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Nullable: an empty form field submits null, the NotBlank constraint then reports it.
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title ?? '';
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    /**
     * Nullable: an empty form field submits null, the NotBlank constraint then reports it.
     */
    public function setAuthorName(?string $authorName): void
    {
        $this->authorName = $authorName ?? '';
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): void
    {
        $this->author = $author;
    }

    public function getCategory(): ?BookCategory
    {
        return $this->category;
    }

    public function setCategory(?BookCategory $category): void
    {
        $this->category = $category;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): void
    {
        $this->available = $available;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): void
    {
        $this->summary = $summary;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
