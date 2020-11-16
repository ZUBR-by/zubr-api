<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\SearchByAllFields;
use Symfony\Component\Serializer\Annotation\Groups;

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
 *    collectionOperations={"get"={"normalization_context"={"groups"="get"}}},
 *    itemOperations={"get"={"normalization_context"={"groups"="get"}}}
 * )
 * @ApiFilter(SearchByAllFields::class)
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

    public function __construct()
    {
        $this->career = new ArrayCollection();
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

    /**
     * @Groups("get")
     */
    public function getCurrentCourt(array $regions = []) : ?array
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
            )
        ) {
            return [
                'id'        => $history[0]->getCourt()->getId(),
                'name'      => $history[0]->getCourt()->getName(),
                'position'  => $history[0]->getPosition(),
                'timestamp' => $history[0]->getTimestamp()->format('d.m.Y'),
                'regions'   => $regions[substr($history[0]->getCourt()->getId(), 0, 2)] ?? '',
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
}
