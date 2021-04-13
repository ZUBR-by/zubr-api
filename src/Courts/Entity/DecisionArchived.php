<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="decisions_archive")
 * @ORM\Entity
 * @ApiResource(
 *    normalizationContext={"groups"={"private"}},
 *    collectionOperations={
 *      "get_private"={
 *          "method"="GET",
 *          "security"="is_granted('ROLE_USER')",
 *          "normalization_context"={"groups"={"private"}}
 *      },
 *    },
 *    itemOperations={
 *      "get"={"normalization_context"={"groups"={"private"}}}
 *    }
 * )
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *         "court.id": "exact",
 *         "category": "exact",
 *         "judge.id": "exact",
 *         "source": "exact",
 *         "fullName": "partial"
 *     }
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"timestamp", "category", "id", "fullName"}, arguments={"orderParameterName"="sort"}
 * )
 * @ApiFilter(ExistsFilter::class, properties={"hiddenAt", "judge"})
 * */
class DecisionArchived
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"public", "private"})
     */
    private $id;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"public", "private"})
     */
    private $timestamp;

    /**
     * @var Court|null
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Court")
     * @ORM\JoinColumn(name="court_id", referencedColumnName="id")
     * @Groups({"public", "private"})
     */
    private $court;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=false, options={"default" : "administrative"})
     */
    private $category = 'administrative';

    /**
     * @var Judge|null
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Judge")
     * @ORM\JoinColumn(name="judge_id", referencedColumnName="id")
     * @Groups({"public", "private"})
     */
    private $judge;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $fullName;

    /**
     * @var bool
     * @Groups({"private"})
     * @ORM\Column(type="boolean", nullable=false, options={"default" : "1"})
     */
    private $isSensitive = true;

    /**
     * @var array
     *
     * @ORM\Column(type="json", length=1000, nullable=false, options={"default" : ""})
     * @Groups({"public", "private"})
     */
    private $articles;

    /**
     * @var string
     * @ORM\Column(type="string", length=2000, nullable=false, options={"default" : ""})
     * @Groups({"public", "private"})
     */
    private $description;

    /**
     * @var array
     * @ORM\Column(type="json", length=1000, nullable=false, options={"default" : ""})
     * @Groups({"public", "private"})
     */
    private $extra;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : "spring96"})
     */
    private $source = 'spring96';

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $hiddenAt;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=false, options={"default" : "[]"})
     * @Groups({"public", "private"})
     */
    private $outcome;

    /**
     * @var Attachment[]|ArrayCollection
     * @ORM\OneToMany (targetEntity="App\Courts\Entity\Attachment", mappedBy="decision")
     * @Groups({"public", "private"})
     */
    private $attachments;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getTimestamp() : ?string
    {
        return $this->timestamp->format('d.m.Y');
    }

    public function timestamp() : DateTime
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

    public function getAttachments() : array
    {
        $links = [];
        /** @var Attachment[] $tmp */
        $tmp = $this->attachments->toArray();
        foreach ($tmp as $element) {
            if (! $element->isImage()) {
                continue;
            }
            $links[] = $element->url();
        }

        return array_filter($links);
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    /**
     * @Groups({"public", "private"})
     */
    public function getFullName() : string
    {
        if ($this->source === '2334') {
            return $this->fullName;
        }
        if ($this->isSensitive) {
            $chunks = explode(' ', $this->fullName);
            return sprintf('%s %s %s', mb_substr($chunks[0], 0, 1), $chunks[1] ?? '', $chunks[2] ?? '');
        }
        return $this->fullName;
    }

    /**
     * @Groups({"private"})
     */
    public function getCategory() : string
    {
        return $this->category;
    }

    /**
     * @Groups({"public", "private"})
     */
    public function getArticles() : array
    {
        return $this->articles;
    }

    /**
     * @Groups({"public", "private"})
     */
    public function getComment() : array
    {
        return $this->extra;
    }

    public function getExtra() : array
    {
        return $this->extra;
    }

    /**
     * @Groups({"public", "private"})
     */
    public function getTimestampRaw() : string
    {
        return $this->timestamp->format(DATE_ATOM);
    }

    /**
     * @Groups({"private"})
     */
    public function getFullNameRaw() : string
    {
        return $this->fullName;
    }

    public function getIsSensitive() : bool
    {
        return $this->isSensitive;
    }

    /**
     * @Groups({"private"})
     */
    public function getSource() : string
    {
        return $this->source;
    }

    /**
     * @Groups({"private"})
     */
    public function getHiddenAt() : ?DateTime
    {
        return $this->hiddenAt;
    }

    /**
     * @Groups({"public", "private"})
     */
    public function getOutcome() : array
    {
        return $this->outcome;
    }

    /**
     * @Groups({"public", "private"})
     */
    public function getOutcomeFormatted() : string
    {
        $result = [];
        foreach ($this->outcome as $outcome) {
            if (! in_array($outcome['type'], ['arrest', 'fine', 'fines_rub'])) {
                continue;
            }
            if ($outcome['type'] === 'arrest') {
                if ($this->category === 'administrative') {
                    $result[] = sprintf(
                        '%s сут.' . ($outcome['extra'] ? ',' . $outcome['extra'] : ''),
                        (int) $outcome['amount']
                    );
                } else {
                    if ($outcome['amount'] >= 365) {
                        $result[] = sprintf(
                            '%s лет.' . ($outcome['extra'] ? ',' . $outcome['extra'] : ''),
                            round($outcome['amount'] / 365, 1)
                        );
                    } else {
                        $result[] = sprintf(
                            '%s месяцев.' . ($outcome['extra'] ? ',' . $outcome['extra'] : ''),
                            round($outcome['amount'] / 30, 1)
                        );
                    }
                }

                continue;
            }
            if ($outcome['type'] === 'confiscation') {
                $result[] = sprintf('конфискация %s %s', (int) $outcome['amount'], $outcome['extra']);
                continue;
            }
            if ($outcome['type'] === 'fines_rub') {
                $result[] = sprintf('%s руб.', (float) $outcome['amount']);
                continue;
            }
            $result[] = sprintf('%s б.в.', (int) $outcome['amount']);
        }

        return implode(',', $result);
    }
}
