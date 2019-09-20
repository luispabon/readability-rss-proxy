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
    private $url;

    /**
     * @var \App\Entity\FeedItem[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="FeedItem", mappedBy="Feed", cascade={"all"})
     */
    private $feedItems;

    public function __construct()
    {
        $this->feedItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

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
}
