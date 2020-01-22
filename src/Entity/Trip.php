<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TripRepository")
 */
class Trip
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("main")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("main")
     * @Assert\NotBlank(message="Trip name is required")
     * @Assert\Length(
     *     min="3",
     *     max="255",
     *     minMessage = "Name of trip must be at least {{ limit }} characters long",
     *     maxMessage = "Name of trip cannot be longer than {{ limit }} characters"
     * )
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups("main")
     * @Assert\Length(
     *     max="100",
     *     maxMessage = "Creator name cannot be longer than {{ limit }} characters"
     * )
     */
    private $creator;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Groups("main")
     */
    private $metadata;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="trips")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Track", mappedBy="trip", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $tracks;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Route", mappedBy="trip", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $routes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Point", mappedBy="trip", cascade={"persist", "remove"})
     */
    private $points;

    public function __construct()
    {
        $this->tracks = new ArrayCollection();
        $this->routes = new ArrayCollection();
        $this->points = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function setCreator(?string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection|Track[]
     */
    public function getTracks(): Collection
    {
        return $this->tracks;
    }

    public function addTrack(Track $track): self
    {
        if (!$this->tracks->contains($track)) {
            $this->tracks[] = $track;
            $track->setTrip($this);
        }

        return $this;
    }

    public function removeTrack(Track $track): self
    {
        if ($this->tracks->contains($track)) {
            $this->tracks->removeElement($track);
            // set the owning side to null (unless already changed)
            if ($track->getTrip() === $this) {
                $track->setTrip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Route[]
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    public function addRoute(Route $route): self
    {
        if (!$this->routes->contains($route)) {
            $this->routes[] = $route;
            $route->setTrip($this);
        }

        return $this;
    }

    public function removeRoute(Route $route): self
    {
        if ($this->routes->contains($route)) {
            $this->routes->removeElement($route);
            // set the owning side to null (unless already changed)
            if ($route->getTrip() === $this) {
                $route->setTrip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Point[]
     */
    public function getPoints(): Collection
    {
        return $this->points;
    }

    public function addPoint(Point $point): self
    {
        if (!$this->points->contains($point)) {
            $this->points[] = $point;
            $point->setTrip($this);
        }

        return $this;
    }

    public function removePoint(Point $point): self
    {
        if ($this->points->contains($point)) {
            $this->points->removeElement($point);
            // set the owning side to null (unless already changed)
            if ($point->getTrip() === $this) {
                $point->setTrip(null);
            }
        }

        return $this;
    }
}
