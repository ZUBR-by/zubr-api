<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use App\SearchByAllFields;
use Symfony\Component\Serializer\Annotation\Groups;

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
     * @Groups({"history", "private"})
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
     * @Groups({"history", "private"})
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

    /**
     * @var Decision[]|ArrayCollection|null
     * @ORM\OneToMany (targetEntity="App\Courts\Entity\Decision", mappedBy="court")
     * @ORM\OrderBy({"timestamp" = "DESC"})
     */
    private $decisions;

    public function __construct()
    {
        $this->decisions = new ArrayCollection();
    }

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

    public function getStatistic() : array
    {
        $fines    = 0;
        $finesRub = 0;
        $arrests  = 0;
        $count    = 0;
        /** @var Decision[] $decisions */
        $decisions = $this->decisions->toArray();
        foreach ($decisions as $decision) {
            $count++;
            if ($decision->getHiddenAt() !== null) {
                continue;
            }
            if ($decision->getCategory() === 'criminal') {
                continue;
            }
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
}
