<?php

namespace App\Entity;

use App\Enum\OfferStatus;
use App\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['offer:read'])]

    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['offer:read', 'offer:write'])]

    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['offer:read', 'offer:write'])]

    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['offer:read'])]

    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'string', enumType: OfferStatus::class)]
    private ?OfferStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'offers')]
    private ?User $user = null;

    /**
     * @var Collection<int, Request>
     */
    #[ORM\OneToMany(targetEntity: Request::class, mappedBy: 'offer', cascade: ['remove'])]
    private Collection $requests;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'offer')]
    private Collection $reviews;

    #[ORM\Column(type: 'boolean')]
    private bool $isNegotiable = false;

    /**
     * @var Collection<int, Report>
     */
    #[ORM\OneToMany(targetEntity: Report::class, mappedBy: 'Offer')]
    private Collection $reports;

    #[ORM\ManyToOne(inversedBy: 'OffersAsWantedSkill')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Skill $wantedSkill = null;

    #[ORM\ManyToOne(inversedBy: 'OffersAsSkillOffered')]
    #[ORM\JoinColumn(nullable: false)]

    private ?Skill $offeredSkill = null;




    public function __construct()
    {
        $this->requests = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->reports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): ?OfferStatus
    {
        return $this->status;
    }

    public function setStatus(OfferStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Request>
     */
    public function getRequests(): Collection
    {
        return $this->requests;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }
    public function addReview(Review $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setOffer($this);
        }

        return $this;
    }

    public function removeReview(Review $review): self
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getOffer() === $this) {
                $review->setOffer(null);
            }
        }

        return $this;
    }

    public function addRequest(Request $request): static
    {
        if (!$this->requests->contains($request)) {
            $this->requests->add($request);
            $request->setOffer($this);
        }

        return $this;
    }

    public function removeRequest(Request $request): static
    {
        if ($this->requests->removeElement($request)) {
            if ($request->getOffer() === $this) {
                $request->setOffer(null);
            }
        }

        return $this;
    }

    public function getSkillOffered(): ?Skill
    {
        return $this->offeredSkill;
    }

    public function setSkillOffered(Skill $skillOffered): self
    {
        $this->offeredSkill = $skillOffered;
        return $this;
    }

    public function getSkillWanted(): ?Skill
    {
        return $this->wantedSkill;
    }

    public function setSkillWanted(Skill $skillWanted): self
    {
        $this->wantedSkill = $skillWanted;
        return $this;
    }

    public function isNegotiable(): bool
    {
        return $this->isNegotiable;
    }

    public function setNegotiable(bool $isNegotiable): self
    {
        $this->isNegotiable = $isNegotiable;
        return $this;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): static
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setOffer($this);
        }

        return $this;
    }

    public function removeReport(Report $report): static
    {
        if ($this->reports->removeElement($report)) {
            if ($report->getOffer() === $this) {
                $report->setOffer(null);
            }
        }

        return $this;
    }

    public function getWantedSkill(): ?Skill
    {
        return $this->wantedSkill;
    }

    public function setWantedSkill(?Skill $wantedSkill): static
    {
        $this->wantedSkill = $wantedSkill;

        return $this;
    }

    public function getOfferedSkill(): ?Skill
    {
        return $this->offeredSkill;
    }

    public function setOfferedSkill(?Skill $offeredSkill): static
    {
        $this->offeredSkill = $offeredSkill;

        return $this;
    }

}
