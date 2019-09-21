<?php

namespace App\Entity;

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
    private $feedUrl;

    /**
     * @var FeedItem[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="FeedItem", mappedBy="feed", cascade={"all"})
     */
    private $feedItems;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $icon;

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
}
