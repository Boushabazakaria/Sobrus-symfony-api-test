<?php

namespace App\Entity;

use App\Repository\BlogArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BlogArticleRepository::class)]
class BlogArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Author ID is required.")]
    #[Assert\Type(type: 'integer', message: "Author ID must be an integer.")]
    private ?int $authorId = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Title is required.")]
    #[Assert\Length(
        max: 100,
        maxMessage: "The title cannot be longer than {{ limit }} characters."
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "Publication date is required.")]
    #[Assert\DateTime(format: 'Y-m-d H:i:s', message: "The publication date must follow the 'Y-m-d H:i:s' format.")]
    private ?\DateTimeInterface $publicationDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "Creation date is required.")]
    #[Assert\DateTime(format: 'Y-m-d H:i:s', message: "The creation date must follow the 'Y-m-d H:i:s' format.")]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "Keywords are required.")]
    #[Assert\Type(type: 'array', message: "Keywords must be a valid JSON array.")]
    private ?array $keywords = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Status is required.")]
    #[Assert\Choice(choices: ['draft', 'published', 'deleted'], message: "The status must be one of: 'draft', 'published', 'deleted'.")]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Slug is required.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "The slug cannot be longer than {{ limit }} characters."
    )]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Cover picture reference is required.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "The cover picture reference cannot be longer than {{ limit }} characters."
    )]
    private ?string $coverPictureRef = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Content is required.")]
    private ?string $content = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthorId(): ?int
    {
        return $this->authorId;
    }

    public function setAuthorId(int $authorId): static
    {
        $this->authorId = $authorId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPublicationDate(): ?\DateTimeInterface
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(\DateTimeInterface $publicationDate): static
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): static
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCoverPictureRef(): ?string
    {
        return $this->coverPictureRef;
    }

    public function setCoverPictureRef(?string $coverPictureRef): static
    {
        $this->coverPictureRef = $coverPictureRef;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }
}
