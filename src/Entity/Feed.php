<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
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
    private ?int $id;

    /**
     * @ORM\Column(type="text")
     */
    private string $feedUrl;

    /**
     * @var FeedItem[]
     *
     * @ORM\OneToMany(targetEntity="FeedItem", mappedBy="feed", cascade={"all"})
     * @ORM\OrderBy({"lastModified" = "DESC"})
     */
    private iterable $feedItems;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description;

    /**
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

    public function addFeedItem(FeedItem $feedItem): self
    {
        $this->feedItems[] = $feedItem;
        $feedItem->setFeed($this);

        return $this;
    }

    public function removeFeedItem(FeedItem $feedItem): self
    {
        $this->feedItems->removeElement($feedItem);

        return $this;
    }

    /**
     * @return FeedItem[]
     */
    public function getFeedItems(): iterable
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
