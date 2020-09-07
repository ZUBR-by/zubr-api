<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\SearchByAllFields;

/**
 * @ORM\Table(
 *     name="organization",
 *     indexes={
 *          @ORM\Index(columns={"name", "description", "location"}, flags={"fulltext"})
 *     }
 * )
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={
 *      "get"={"path"="/organizations"}
 *    },
 *    itemOperations={"get"}
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"id", "type", "name", "description", "location"}, arguments={"orderParameterName"="sort"}
 * )
 * @ApiFilter(SearchByAllFields::class)
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={"type": "exact", "name": "partial", "location": "partial", "description": "partial"}
 * )
 */
class Organization
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"organization"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default" : 0})
     * @Assert\NotBlank
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="text", nullable=false)
     * @Assert\NotBlank
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=false)
     * @Assert\NotBlank
     * @Groups({"organization"})
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(name="postal_code", type="string", length=20, nullable=false, options={"default" : ""})
     */
    private $postalCode;

    /**
     * @var string
     * @ORM\Column(name="phone", type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $phone;

    /**
     * @var string
     * @ORM\Column(name="email", type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $email;

    /**
     * @var float|null
     * @ORM\Column(name="longitude", type="float", nullable=true)
     * @Assert\NotBlank
     */
    private $longitude = 0.0;

    /**
     * @var float|null
     * @ORM\Column(name="latitude", type="float", nullable=true)
     * @Assert\NotBlank
     */
    private $latitude = 0.0;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="text", nullable=false)
     * @Assert\NotBlank
     */
    private $location;

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }

    /**
     * @return float|null
     */
    public function getLongitude() : ?float
    {
        return $this->longitude;
    }

    /**
     * @return float|null
     */
    public function getLatitude() : ?float
    {
        return $this->latitude;
    }

    /**
     * @return string
     */
    public function getLocation() : string
    {
        return $this->location;
    }
}
