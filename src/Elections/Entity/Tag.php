<?php

namespace App\Elections\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="tag",
 *     indexes={
 *          @ORM\Index(columns={"name", "description"}, flags={"fulltext"})
 *      }
 * )
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={
 *      "get"={"path"="/tags", "cache_headers"={"max_age"=360, "shared_max_age"=720, "vary"={"Accept-Language"}}}
 *    },
 *    itemOperations={"get"},
 *    normalizationContext={"groups"={"public", "private"}},
 * )
 */
class Tag
{
    /**
     * @var string
     * @ORM\Column(name="code", type="string", length=100, nullable=false)
     * @ORM\Id
     * @Assert\NotBlank
     * @Groups({"public", "private"})
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=500, nullable=false)
     * @Assert\NotBlank
     * @Groups({"public", "private"})
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=1000, nullable=false)
     * @Groups({"public", "private"})
     */
    private $description;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\NotBlank
     */
    private $type;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=1000, nullable=true)
     * @Groups({"private"})
     */
    private $notes;

    /**
     *
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="App\Elections\Entity\Commission")
     * @ORM\JoinTable(name="commission_tag",
     *      inverseJoinColumns={@ORM\JoinColumn(name="commission_id", referencedColumnName="id")},
     *      joinColumns={@ORM\JoinColumn(name="tag_code", referencedColumnName="code")}
     *      )
     */
    private $commissions;

    /**
     *
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="App\Elections\Entity\Member")
     * @ORM\JoinTable(name="member_tag",
     *      inverseJoinColumns={@ORM\JoinColumn(name="member_id", referencedColumnName="id")},
     *      joinColumns={@ORM\JoinColumn(name="tag_code", referencedColumnName="code")}
     *      )
     */
    private $members;

    public function __construct()
    {
        $this->commissions = new ArrayCollection();
        $this->members   = new ArrayCollection();
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

    public function getCommissions()
    {
        return $this->commissions;
    }

}
