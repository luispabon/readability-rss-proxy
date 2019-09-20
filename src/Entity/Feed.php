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
     * @ORM\Column(type="text")
     */
    private $feedUrl;

    /**
     * @var \App\Entity\FeedItem[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="FeedItem", mappedBy="Feed", cascade={"all"})
     */
    private $feedItems;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

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
        $feedItem->setPost($this);

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
     * @return Collection
     */
    public function getFeedItems(): Collection
    {
        return $this->feedItems;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
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
}
