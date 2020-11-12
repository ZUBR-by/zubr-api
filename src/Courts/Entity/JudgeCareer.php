<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="judge_career")
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={"get"},
 *    itemOperations={"get"},
 *    normalizationContext={"groups"={"history"}}
 * )
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *         "court.id": "exact",
 *         "judge.id": "exact",
 *     }
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"timestamp", "type"}, arguments={"orderParameterName"="sort"}
 * )
 * */
class JudgeCareer
{
    /**
     * @var int
     * @Groups({"history"})
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Groups({"history"})
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Court")
     * @ORM\JoinColumn(name="court_id", referencedColumnName="id")
     */
    private $court;

    /**
     * @Groups({"history"})
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Judge")
     * @ORM\JoinColumn(name="judge_id", referencedColumnName="id")
     */
    private $judge;

    /**
     * @var DateTime
     * @Groups({"history"})
     * @ORM\Column(type="date", nullable=true)
     */
    private $timestamp;

    /**
     * @var int
     * @Groups({"history"})
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $decreeNumber;

    /**
     * @var string
     * @Groups({"history"})
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $type;

    /**
     * @var string
     * @Groups({"history"})
     * @ORM\Column(type="string", nullable=true)
     */
    private $position;

    /**
     * @var string
     * @Groups({"history"})
     * @ORM\Column(type="string", length=500, nullable=false, options={"default" : ""})
     */
    private $comment;

    /**
     * @var ?int
     * @Groups({"history"})
     * @ORM\Column(type="integer", nullable=true)
     */
    private $term;

    /**
     * @var string
     * @Groups({"history"})
     * @ORM\Column(type="string", length=100, nullable=false, options={"default" : "indefinetely"})
     */
    private $termType;

    public function getTimestamp() : DateTime
    {
        return $this->timestamp;
    }

    public function getTerm() : ?int
    {
        return $this->term;
    }

    public function getTermType() : ?string
    {
        return $this->termType;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function isReleased() : bool
    {
        return $this->type === 'released';
    }

    public function isIndefinitely() : bool
    {
        return $this->termType === 'indefinitely';
    }

    public function getCourt() : Court
    {
        return $this->court;
    }

    public function getJudge() : Judge
    {
        return $this->judge;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getPosition() : string
    {
        return $this->position;
    }

    public function getComment() : string
    {
        return $this->comment;
    }

    public function getDecreeNumber() : int
    {
        return $this->decreeNumber;
    }
}
