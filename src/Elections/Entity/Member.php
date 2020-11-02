<?php

namespace App\Elections\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use \App\SearchByAllFields;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;

/**
 * @ORM\Table(
 *     name="member",
 *     indexes={
 *         @ORM\Index(columns={"full_name", "work_title", "description"}, flags={"fulltext"})
 *     }
 * )
 * @ORM\Entity
 * @ApiResource(
 *     collectionOperations={
 *         "get"={"path"="/members"}
 *     },
 *     itemOperations={
 *         "get"={"normalization_context"={"groups"="organization"}}
 *     },
 *     attributes={"pagination_items_per_page"=10},
 *     normalizationContext={"groups"={"member"}}
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"id", "fullName", "workTitle", "description"}, arguments={"orderParameterName"="sort"}
 * )
 * @ApiFilter(NumericFilter::class, properties={"referral.id", "employer.id", "commission.id", "commission.type"})
 * @ApiFilter(SearchByAllFields::class)
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *         "positionType": "exact",
 *         "description": "partial",
 *         "workTitle": "partial"
 *     }
 * )
 */
class Member
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @Groups({"member","organization"})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Commission
     *
     * @ORM\ManyToOne(targetEntity="Commission")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="commission_id", referencedColumnName="id")
     * })
     * @Groups({"member","organization"})
     * @Assert\NotBlank
     */
    private $commission;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="employer_id", referencedColumnName="id")
     * })
     * @Groups({"member","organization"})
     * @Assert\NotBlank
     */
    private $employer;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="referral_id", referencedColumnName="id")
     * })
     * @Groups({"member","organization"})
     * @Assert\NotBlank
     */
    private $referral;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default" : 0})
     * @Assert\NotBlank
     * @Groups({"member","organization"})
     */
    private $positionType;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", length=500, nullable=false)
     * @Assert\NotBlank
     * @Groups({"member","organization"})
     */
    private $fullName;

    /**
     * @var string
     *
     * @ORM\Column(name="photo_url", type="string", length=4000, nullable=false, options={"default" : ""})
     * @Assert\NotBlank
     * @Groups({"member","organization"})
     */
    private $photoUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="photo_origin", type="string", length=4000, nullable=false, options={"default" : ""})
     * @Assert\NotBlank
     * @Groups({"member","organization"})
     */
    private $photoOrigin;

    /**
     * @var string
     *
     * @ORM\Column(name="work_title", type="string", length=1000, nullable=false, options={"default" : ""})
     * @Assert\NotBlank
     * @Groups({"member","organization"})
     */
    private $workTitle;

    /**
     * @var string
     * @ORM\Column(name="description", type="text", nullable=false)
     * @Groups({"member","organization"})
     */
    private $description;

    /**
     * @var string|null
     * @ORM\Column(name="links", type="text", nullable=true)
     * @Groups({"member","organization"})
     */
    private $links;

    /**
     * @var string|null
     * @ORM\Column(name="notes", type="string", length=1000, nullable=true)
     * @Groups({"member","organization"})
     */
    private $notes;

    /**
     *
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="App\Elections\Entity\Tag", mappedBy="members")
     * @Groups({"member","organization"})
     */
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getFullName() : string
    {
        return $this->fullName;
    }

    public function getLastFirstNames() : string
    {
        [$last, $name1] = explode(' ', $this->getFullName());

        return sprintf('%s %s', $name1, $last);
    }

    public function getPositionType() : int
    {
        return $this->positionType;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function getLinks() : ?string
    {
        return $this->links;
    }

    public function getNotes() : ?string
    {
        return $this->notes;
    }

    public function getPhotoUrl() : string
    {
        return $this->photoUrl;
    }

    public function getPhotoOrigin() : string
    {
        return $this->photoOrigin;
    }

    public function getReferral() : ?Organization
    {
        return $this->referral;
    }

    public function getCommission() : ?Commission
    {
        return $this->commission;
    }

    public function getEmployer() : ?Organization
    {
        return $this->employer;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getWorkTitle() : string
    {
        return $this->workTitle;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function hasValidPhotoURL() : bool
    {
        return ! empty($this->photoUrl) && (stristr($this->photoUrl, '.png') || stristr($this->photoUrl,
                    '.jpg') || stristr($this->photoUrl, '.jpeg'));
    }
}
