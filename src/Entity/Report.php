<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    private ?User $maker = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    private ?Offer $Offer = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "reports")]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $repportedUser = null;


    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaker(): ?User
    {
        return $this->maker;
    }

    public function setMaker(?User $maker): static
    {
        $this->maker = $maker;

        return $this;
    }

    public function getOffer(): ?Offer
    {
        return $this->Offer;
    }

    public function setOffer(?Offer $Offer): static
    {
        $this->Offer = $Offer;

        return $this;
    }

    public function getRepportedUser(): ?User
    {
        return $this->repportedUser;
    }

    public function setRepportedUser(?User $repportedUser): static
    {
        $this->repportedUser = $repportedUser;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
