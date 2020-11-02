<?php

namespace App\Elections\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Elections\MapViewFilter;
use App\SearchByAllFields;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="commission",
 *     indexes={
 *          @ORM\Index(columns={"code"}),
 *          @ORM\Index(columns={"name", "description", "location"}, flags={"fulltext"})
 *      }
 * )
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={
 *      "get"={"path"="/commissions", "cache_headers"={"max_age"=360, "shared_max_age"=720, "vary"={"Accept-Language"}}}
 *    },
 *    itemOperations={"get", "get_commission_print"={"route_name"="commission_print"}},
 *    normalizationContext={"groups"={"commissions", "map_view","organization", "public", "private"}},
 * )
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={"name":"partial","description":"partial","location": "partial"}
 * )
 * @ApiFilter(NumericFilter::class, properties={"type", "id"})
 * @ApiFilter(RangeFilter::class, properties={"applied"})
 * @ApiFilter(MapViewFilter::class)
 * @ApiFilter(SearchByAllFields::class)
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"id", "type", "name", "description", "location"}, arguments={"orderParameterName"="sort"}
 * )
 */
class Commission
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @Groups({"member", "commissions", "map_view","organization", "public", "private"})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="code", type="string", length=11, nullable=false)
     * @Assert\NotBlank
     * @Groups({"member","commissions", "map_view","organization", "public", "private"})
     */
    private $code;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\NotBlank
     * @Groups({"member","commissions","organization"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=500, nullable=false)
     * @Assert\NotBlank
     * @Groups({"member","commissions","organization", "public", "private"})
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=1000, nullable=false)
     * @Groups({"member","commissions","organization","map_view", "public", "private"})
     */
    private $description;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=1000, nullable=true)
     * @Groups({"commissions","organization"})
     */
    private $notes;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"commissions","organization"})
     */
    private $area;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=4000, nullable=true)
     * @Groups({"commissions","organization"})
     */
    private $origin;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=500, nullable=false)
     * @Assert\NotBlank
     * @Groups({"member","commissions", "map_view","organization"})
     */
    private $location;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", nullable=true, precision=11, scale=8)
     * @Assert\NotBlank
     * @Groups({"member","commissions", "map_view","organization"})
     */
    private $longitude;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", nullable=true, precision=11, scale=8)
     * @Assert\NotBlank
     * @Groups({"member","commissions", "map_view","organization"})
     */
    private $latitude;

    /**
     * @Groups({"commissions","organization"})
     * @ORM\ManyToOne(targetEntity="App\Elections\Entity\Commission")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default" : 0})
     * @Groups({"member","commissions", "map_view","organization"})
     */
    private $applied = 0;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Elections\Entity\Member", mappedBy="commission")
     */
    private $members;

    /**
     *
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="App\Elections\Entity\Tag", mappedBy="tag")
     */
    private $tags;

    public function __construct()
    {
        $this->members   = new ArrayCollection();
        $this->tags   = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getArea() : ?string
    {
        return $this->area;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getParent(): ?Commission
    {
        return $this->parent;
    }

    public function getApplied(): int
    {
        return $this->applied;
    }

    public function getMembers()
    {
        return $this->members;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setPosterUrl(string $url): self
    {
        $this->posterUrl = $url;

        return $this;
    }
}
