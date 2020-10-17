<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;

/**
 * @ORM\Table(name="decisions")
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={"get"},
 *    itemOperations={"get"}
 * )
 * @ApiFilter(NumericFilter::class, properties={"judge.id"})
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *         "court.id": "exact"
 *     }
 * )
 * */
class Decision
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
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var Court|null
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Court")
     * @ORM\JoinColumn(name="court_id", referencedColumnName="id")
     */
    private $court;

    /**
     * @var Judge|null
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Judge")
     * @ORM\JoinColumn(name="judge_id", referencedColumnName="id")
     */
    private $judge;

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
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $aftermathType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $aftermathExtra;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", nullable=true, precision=8, scale=2)
     */
    private $aftermathAmount;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1000, nullable=false, options={"default" : ""})
     */
    private $article;

    /**
     * @var array
     *
     * @ORM\Column(type="json", nullable=false, options={"default" : "{}"})
     */
    private $attachments;

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

    public function getTimestamp() : ?DateTime
    {
        return $this->timestamp;
    }

    public function getCourt() : ?Court
    {
        return $this->court;
    }

    public function getJudge() : ?Judge
    {
        return $this->judge;
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

    public function getAftermathType() : string
    {
        return $this->aftermathType;
    }

    public function getAftermathExtra() : string
    {
        return $this->aftermathExtra;
    }

    public function getAftermathAmount() : ?string
    {
        return $this->aftermathAmount;
    }

    public function getArticle() : string
    {
        return $this->article;
    }

    public function getAttachments() : array
    {
        return $this->attachments;
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
