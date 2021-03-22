<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;

/**
 * @ORM\Table(name="trial", schema="courts")
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={"get"},
 *    itemOperations={"get"}
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"timestamp", "person", "id"}, arguments={"orderParameterName"="sort"}
 * )
 * */
class Trial
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private string $person;

    /**
     * @ORM\Column(type="string", length=1000, nullable=false, options={"default" : ""})
     */
    private string $comment;

    /**
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Court")
     * @ORM\JoinColumn(name="court_id", referencedColumnName="id")
     */
    private ?Court $court;

    /**
     * @ORM\Column(type="json", nullable=false, options={"default" : "[]"})
     */
    private array $articles;

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

    public function getTimestamp() : string
    {
        return $this->timestamp->format(DATE_ATOM);
    }

    public function getTimestampFormatted() : ?string
    {
        return $this->timestamp->format('d.m.Y H:i');
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
