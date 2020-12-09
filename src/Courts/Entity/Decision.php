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
 * @ORM\Table(name="decisions")
 * @ORM\Entity
 * @ApiResource(
 *    normalizationContext={"groups"={"public"}},
 *    collectionOperations={
 *      "get_private"={
 *          "method"="GET",
 *          "path"="/resolutions",
 *          "security"="is_granted('ROLE_USER')",
 *          "normalization_context"={"groups"={"private"}}
 *      },
 *      "get_public"={
 *          "method"="GET",
 *          "path"="/decision",
 *          "normalization_context"={"groups"={"public"}},
 *      }
 *    },
 *    itemOperations={
 *      "get"={"normalization_context"={"groups"={"public","private"}}}
 *    }
 * )
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *         "court.id": "exact",
 *         "judge.id": "exact",
 *         "source": "exact",
 *         "fullName": "partial"
 *     }
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"aftermath_type", "timestamp", "category"}, arguments={"orderParameterName"="sort"}
 * )
 * @ApiFilter(ExistsFilter::class, properties={"hiddenAt"})
 * */
class Decision
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
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     * @Groups({"public", "private"})
     */
    private $aftermathType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     * @Groups({"public", "private"})
     */
    private $aftermathExtra;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", nullable=true, precision=8, scale=2)
     * @Groups({"public", "private"})
     */
    private $aftermathAmount;

    /**
     * @var array
     *
     * @ORM\Column(type="json", length=1000, nullable=false, options={"default" : ""})
     * @Groups({"public", "private"})
     */
    private $article;

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
        if ($this->isSensitive) {
            $chunks = explode(' ', $this->fullName);
            return sprintf('%s %s %s', mb_substr($chunks[0], 0, 1), $chunks[1] ?? '', $chunks[2] ?? '');
        }
        return $this->fullName;
    }

    /**
     * @Groups({"public", "private"})
     */
    public function getAftermath() : string
    {
        if (! in_array($this->aftermathType, ['arrest', 'fine'])) {
            return '';
        }
        if ($this->aftermathType === 'arrest') {
            return sprintf('%s сут.', (int) $this->aftermathAmount);
        }

        return sprintf('%s б.в.', (int) $this->aftermathAmount);
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
        return $this->article;
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
}
