<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

/**
 * @ORM\Table(name="judge")
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={"get"},
 *    itemOperations={"get"}
 * )
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *         "comment": "exact"
 *     }
 * )
 * */
class Judge
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $middleName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1000, nullable=false, options={"default" : ""})
     */
    private $photoUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1000, nullable=false, options={"default" : ""})
     */
    private $photoOrigin;

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

    public function getId() : int
    {
        return $this->id;
    }

    public function getLastName() : string
    {
        return $this->lastName;
    }

    public function getFirstName() : string
    {
        return $this->firstName;
    }

    public function getMiddleName() : string
    {
        return $this->middleName;
    }

    public function getPhotoUrl() : string
    {
        return $this->photoUrl;
    }

    public function getPhotoOrigin() : string
    {
        return $this->photoOrigin;
    }

    public function getComment() : string
    {
        return $this->comment;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function getFullName() : string
    {
        return implode(' ', [$this->lastName, $this->firstName, $this->middleName]);
    }
}
