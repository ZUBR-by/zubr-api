<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="trial", schema="courts")
 * @ORM\Entity
 * @ApiResource(
 *    normalizationContext={"groups"={"private"}},
 *    collectionOperations={
 *      "get",
 *      "post"={
 *          "method"="POST",
 *          "path"="/trial",
 *          "security"="is_granted('ROLE_USER')"
 *      },
 *    },
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
     * @Groups({"private"})
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     * @Groups({"private"})
     */
    private string $person;

    /**
     * @ORM\Column(type="string", length=1000, nullable=false, options={"default" : ""})
     * @Groups({"private"})
     */
    private string $comment;

    /**
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Court")
     * @ORM\JoinColumn(name="court_id", referencedColumnName="id")
     * @Groups({"private"})
     */
    private ?Court $court;

    /**
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Judge")
     * @ORM\JoinColumn(name="judge_id", referencedColumnName="id")
     * @Groups({"private"})
     */
    private ?Judge $judge;

    /**
     * @ORM\Column(type="json", nullable=false, options={"default" : "[]"})
     * @Groups({"private"})
     */
    private array $articles;

    /**
     * @var DateTime
     * @Groups({"private"})
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Groups({"private"})
     */
    private int $source = 0;

    public function getId() : int
    {
        return $this->id;
    }

    public function getTimestamp() : string
    {
        return $this->timestamp->format(DATE_ATOM);
    }

    /**
     * @Groups({"private"})
     */
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

    public function getJudge() : ?Judge
    {
        return $this->judge;
    }

    public function getArticles() : array
    {
        return $this->articles;
    }

    public function setPerson(string $person) : void
    {
        $this->person = $person;
    }

    public function setCourt(?Court $court) : void
    {
        $this->court = $court;
    }

    public function setJudge(?Judge $court) : void
    {
        $this->judge = $court;
    }

    public function setTimestamp(DateTime $timestamp) : void
    {
        $this->timestamp = $timestamp;
    }

    public function setArticles(array $articles) : void
    {
        $this->articles = $articles;
    }

    public function setComment(string $comment) : void
    {
        $this->comment = $comment;
    }
}
