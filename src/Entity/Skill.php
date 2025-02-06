<?php

namespace App\Entity;

use App\Enum\SkillStatus;
use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'skills')]
    private Collection $users;

    #[ORM\Column(length: 255, nullable: true)]
    private ?SkillStatus $status = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?User $proposedBy = null;

    /**
     * @var Collection<int, Offer>
     */
    #[ORM\OneToMany(targetEntity: Offer::class, mappedBy: 'wantedSkill', orphanRemoval: true)]
    private Collection $offersAsWantedSkill;

    /**
     * @var Collection<int, Offer>
     */
    #[ORM\OneToMany(targetEntity: Offer::class, mappedBy: 'offeredSkill', orphanRemoval: true)]
    private Collection $offersAsSkillOffered;




    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->offersAsWantedSkill = new ArrayCollection();
        $this->offersAsSkillOffered = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addSkill($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeSkill($this);
        }

        return $this;
    }

    public function getStatus(): ?SkillStatus
    {
        return $this->status;
    }

    public function setStatus(SkillStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getProposedBy(): ?User
    {
        return $this->proposedBy;
    }

    public function setProposedBy(?User $user): self
    {
        $this->proposedBy = $user;
        return $this;
    }

    /**
     * @return Collection<int, Offer>
     */
    public function getOffersAsWantedSkill(): Collection
    {
        return $this->offersAsWantedSkill;
    }

    public function addOffersAsWantedSkill(Offer $offersAsWantedSkill): static
    {
        if (!$this->offersAsWantedSkill->contains($offersAsWantedSkill)) {
            $this->offersAsWantedSkill->add($offersAsWantedSkill);
            $offersAsWantedSkill->setWantedSkill($this);
        }

        return $this;
    }

    public function removeOffersAsWantedSkill(Offer $offersAsWantedSkill): static
    {
        if ($this->offersAsWantedSkill->removeElement($offersAsWantedSkill)) {
            // set the owning side to null (unless already changed)
            if ($offersAsWantedSkill->getWantedSkill() === $this) {
                $offersAsWantedSkill->setWantedSkill(null);
            }
        }

        return $this;
    }


    public function removeOffersAsSkillOffered(Offer $offersAsSkillOffered): static
    {
        if ($this->offersAsSkillOffered->removeElement($offersAsSkillOffered)) {
            // set the owning side to null (unless already changed)
            if ($offersAsSkillOffered->getOfferedSkill() === $this) {
                $offersAsSkillOffered->setOfferedSkill(null);
            }
        }

        return $this;
    }

    public function getOffersAsSkillOffered(): Collection
    {
        return $this->offersAsSkillOffered;
    }




    public function addOffersAsSkillOffered(Offer $offersAsSkillOffered): static
    {
        if (!$this->offersAsSkillOffered->contains($offersAsSkillOffered)) {
            $this->offersAsSkillOffered->add($offersAsSkillOffered);
            $offersAsSkillOffered->setOfferedSkill($this);
        }

        return $this;
    }


}
