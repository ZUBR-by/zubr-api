<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\SearchByAllFields;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Courts\JudgeSortFilter;
use App\Courts\JudgeTagsFilter;

/**
 * @ORM\Table(
 *     name="judge",
 *     indexes={
 *          @ORM\Index(
 *              columns={"full_name", "description"},
 *              flags={"fulltext"}
 *          )
 *     }
 * )
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={
 *     "get"={
 *          "normalization_context"={"groups"="get"}
 *      }
 *    },
 *    itemOperations={"get"={"normalization_context"={"groups"="get"}}}
 * )
 *
 * @ApiFilter(SearchByAllFields::class)
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *         "comment": "exact",
 *         "tags.tag": "exact"
 *     }
 * )
 * @ApiFilter(
 *     JudgeSortFilter::class
 * )
 * @ApiFilter(
 *     JudgeTagsFilter::class
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
     * @Groups({"private"})
     */
    private $id;

    /**
     * @var string
     * @Groups({"private"})
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $fullName;

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

    /**
     * @var Court|null
     * @ORM\OneToMany (targetEntity="App\Courts\Entity\JudgeCareer", mappedBy="judge")
     * @ORM\OrderBy({"timestamp" = "DESC", "type" = "asc"})
     */
    private $career;

    /**
     * @var Decision|null
     * @ORM\OneToMany (targetEntity="App\Courts\Entity\Decision", mappedBy="judge")
     * @ORM\OrderBy({"timestamp" = "DESC"})
     */
    private $decisions;

    /**
     * @var array
     * @Groups({"private"})
     * @ORM\Column(type="json", nullable=false, options={"default" : "[]"})
     */
    private $tags;

    public function __construct()
    {
        $this->career    = new ArrayCollection();
        $this->decisions = new ArrayCollection();
    }

    /**
     * @Groups("get")
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @Groups("get")
     */
    public function getPhotoUrl() : string
    {
        return $this->photoUrl;
    }

    /**
     * @Groups("get")
     */
    public function getPhotoOrigin() : string
    {
        return $this->photoOrigin;
    }

    /**
     * @Groups("get")
     */
    public function getComment() : string
    {
        return $this->comment;
    }

    /**
     * @Groups("get")
     */
    public function getDescription() : string
    {
        return $this->description;
    }

    /**
     * @Groups("get")
     */
    public function getFullName() : string
    {
        return $this->fullName;
    }

    public function toMarkdownJson() : array
    {
        return [
            'id'            => $this->id,
            'tags'          => $this->getTags(),
            'fullName'      => $this->fullName,
            'comment'       => $this->comment,
            'career'        => $this->career,
            'layout'        => 'judge',
            'title'         => 'Судья ' . $this->fullName,
            'court'         => $this->getCurrentCourt(),
            'previousCourt' => $this->getPreviousCourt(),
        ];
    }

    /**
     * @Groups("get")
     */
    public function getCurrentCourt() : ?array
    {
        /** @var JudgeCareer[] $history */
        $history = $this->career->toArray();
        if (! $history) {
            return null;
        }
        $copy = clone $history[0]->getTimestamp();
        if (! $history[0]->isReleased()
            && (
                $history[0]->isIndefinitely()
                || ($history[0]->getTermType() === 'years'
                    && (new \DateTime()) < $copy->add(new \DateInterval('P5Y'))
                )
                || $history[0]->getTermType() === 'period'
            )
        ) {
            $line = 'c ' . $history[0]->getTimestamp()->format('d.m.Y');
            if ($history[0]->getTermType() === 'indefinitely') {
                $line .= ', бессрочно';
            } elseif ($history[0]->getTermType() === 'years') {
                $line .= ', на срок ' . $history[0]->getTerm() . ' лет';
            } else {
                $line .= ', ' . $history[0]->getComment();
            }
            $line .= ', по указу ' . $history[0]->getDecreeNumber();

            return [
                'id'          => $history[0]->getCourt()->getId(),
                'name'        => $history[0]->getCourt()->getName(),
                'position'    => $history[0]->getPosition(),
                'termType'    => $history[0]->getTermType(),
                'term'        => $history[0]->getTerm(),
                'description' => $line,
                'timestamp'   => $history[0]->getTimestamp()->format('d.m.Y'),
            ];
        }
        return null;
    }

    /**
     * @Groups("get")
     */
    public function getPreviousCourt() : ?array
    {
        /** @var JudgeCareer[] $history */
        $history = $this->career->toArray();
        if (! $history) {
            return null;
        }
        if (count($history) === 1 && ! $history[0]->isReleased()) {
            return null;
        }
        if (count($history) === 1 && $history[0]->isReleased()) {
            return [
                'id'   => $history[0]->getCourt()->getId(),
                'name' => $history[0]->getCourt()->getName(),
            ];
        }
        if (count($history) > 1) {
            return [
                'id'   => $history[1]->getCourt()->getId(),
                'name' => $history[1]->getCourt()->getName(),
            ];
        }
        return null;
    }

    /**
     * @Groups("get")
     */
    public function getStatistic() : array
    {
        $fines    = 0;
        $finesRub = 0;
        $arrests  = 0;
        $count    = 0;
        /** @var Decision[] $decisions */
        $decisions = $this->decisions->toArray();
        foreach ($decisions as $decision) {
            if ($decision->getHiddenAt() !== null) {
                continue;
            }
            if ($decision->getCategory() === 'criminal') {
                continue;
            }
            $count++;
            $outcomes = $decision->getOutcome();
            foreach ($outcomes as $outcome) {
                if ($outcome['type'] === 'arrest') {
                    $arrests += $outcome['amount'];
                    continue;
                }
                $rate = 0;
                switch ($decision->timestamp()->format('Y')) {
                    case '2021':
                        $rate = 29;
                        break;
                    case '2020':
                        $rate = 27;
                        break;
                    case '2019':
                        $rate = 25.5;
                        break;
                    case '2018':
                        $rate = 24.5;
                        break;
                    case '2017':
                        $rate = 23;
                        break;
                }
                $finesRub += $rate * $outcome['amount'];
                $fines    += $outcome['amount'];
            }
        }
        return [
            'fines'     => $fines,
            'fines_rub' => $finesRub,
            'arrests'   => $arrests,
            'count'     => $count,
        ];
    }

    /**
     * @Groups("get")
     */
    public function getTags() : array
    {
        return $this->tags;
    }
}
