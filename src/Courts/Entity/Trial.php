<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="trial")
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={"get"},
 *    itemOperations={"get"}
 * )
 * */
class Trial
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $person;

    /**
     * @var string
     * @ORM\Column(type="string", length=1000, nullable=false, options={"default" : ""})
     */
    private $comment;

    /**
     * @var Court|null
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Court")
     * @ORM\JoinColumn(name="court_id", referencedColumnName="id")
     */
    private $court;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=false, options={"default" : "[]"})
     */
    private $articles;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    public function getId() : int
    {
        return $this->id;
    }

    public function getTimestamp() : DateTime
    {
        return $this->timestamp;
    }

    public function getPerson() : string
    {
        return $this->person;
    }

    public function getComment() : string
    {
        return $this->comment;
    }

    public function getCourt() : ?Court
    {
        return $this->court;
    }

    public function getArticles() : array
    {
        return $this->articles;
    }
}
