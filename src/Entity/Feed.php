<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FeedRepository")
 */
class Feed
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private string $feedUrl;

    /**
     * @var FeedItem[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="FeedItem", mappedBy="feed", cascade={"all"})
     * @ORM\OrderBy({"lastModified" = "DESC"})
     */
    private $feedItems;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $title;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description;

    /**
     * @var DateTimeInterface|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $icon;

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     */
    private ?DateTimeInterface $lastModified;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RssUser", inversedBy="feeds")
     * @ORM\JoinColumn(nullable=false)
     */
    private RssUser $rssUser;

    public function __construct()
    {
        $this->feedItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFeedUrl(): ?string
    {
        return $this->feedUrl;
    }

    public function setFeedUrl(string $feedUrl): self
    {
        $this->feedUrl = $feedUrl;

        return $this;
    }

    /**
     * @param FeedItem $feedItem
     *
     * @return self
     */
    public function addFeedItem(FeedItem $feedItem): self
    {
        $this->feedItems[] = $feedItem;
        $feedItem->setFeed($this);

        return $this;
    }

    /**
     * @param FeedItem $feedItem
     *
     * @return self
     */
    public function removeFeedItem(FeedItem $feedItem): self
    {
        $this->feedItems->removeElement($feedItem);

        return $this;
    }

    /**
     * @return Collection|FeedItem[]
     */
    public function getFeedItems(): Collection
    {
        return $this->feedItems;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getLastModified(): ?DateTimeInterface
    {
        return $this->lastModified;
    }

    public function setLastModified(?DateTimeInterface $lastModified): self
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    public function getRssUser(): ?RssUser
    {
        return $this->rssUser;
    }

    public function setRssUser(?RssUser $rssUser): self
    {
        $this->rssUser = $rssUser;

        return $this;
    }
}
