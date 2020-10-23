<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use App\SearchByAllFields;
/**
 * @ORM\Table(
 *     name="court",
 *     indexes={
 *          @ORM\Index(columns={"name", "address", "description"}, flags={"fulltext"})
 *     }
 * )
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={"get"},
 *    itemOperations={"get"}
 * )
 * @ApiFilter(SearchByAllFields::class)
 * */
class Court
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", nullable=false, length=20)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default" : 0})
     */
    private $type = 0;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=500, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=500, nullable=false)
     */
    private $address;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", nullable=true, precision=11, scale=8)
     */
    private $longitude;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", nullable=true, precision=11, scale=8)
     */
    private $latitude;

    /**
     * @var string
     * @ORM\Column(type="string", length=1000, nullable=false, options={"default" : ""})
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(type="string", length=1000, nullable=false, options={"default" : ""})
     */
    private $comment;

    public function getId() : string
    {
        return $this->id;
    }

    public function getType() : int
    {
        return $this->type;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getAddress() : string
    {
        return $this->address;
    }

    public function getLongitude() : ?float
    {
        return $this->longitude ? (float) $this->longitude : null;
    }

    public function getLatitude() : ?float
    {
        return $this->latitude ? (float) $this->latitude : null;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function getComment() : string
    {
        return $this->comment;
    }
}
