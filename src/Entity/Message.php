<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use App\MessageFilters;

/**
 * @ORM\Table(
 *     name="message",
 * )
 * @ORM\Entity
 * @ApiResource(
 *    normalizationContext={"groups"={"public"}},
 *    collectionOperations={
 *      "get_private"={
 *          "method"="GET",
 *          "path"="/messages",
 *          "security"="is_granted('ROLE_USER')",
 *          "normalization_context"={"groups"={"private"}}
 *      },
 *      "get_public"={
 *          "method"="GET",
 *          "path"="/newsletter",
 *          "normalization_context"={"groups"={"public"}},
 *      }
 *    },
 *    itemOperations={
 *      "get"={"normalization_context"={"groups"={"public","private"}}}
 *    }
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"createdAt", "approvedAt", "processedAt", "deletedAt"}, arguments={"orderParameterName"="sort"}
 * )
 * @ApiFilter(
 *     MessageFilters::class
 * )
 * @ApiFilter(NumericFilter::class, properties={"initiative"})
 * @ApiFilter(SearchFilter::class, properties={"commissionCode": "exact"})
 * @ApiFilter(ExistsFilter::class, properties={"deletedAt","processedAt", "highlightedAt","approvedAt"})
 */
class Message
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"public", "private"})
     */
    private $id;

    /**
     * @var array
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="json", nullable=true)
     */
    private $attachments;

    /**
     * @var array
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="json", nullable=false)
     */
    private $categories = [];

    /**
     * @var int
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="smallint", nullable=false)
     */
    private $initiative = 2;

    /**
     * @var \DateTime
     *
     * @Groups({"private"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $approvedAt;

    /**
     * @var \DateTime
     *
     * @Groups({"private"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var \DateTime
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $highlightedAt;

    /**
     * @var \DateTime
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $processedAt;

    /**
     * @var string
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="text")
     */
    private $description = '';

    /**
     * @var string
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="string")
     */
    private $commissionCode;

    /**
     * @var string
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="text")
     */
    private $comment = '';

    /**
     * @var \DateTime
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var bool
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $fromOutside;

    /**
     * @var string
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="string", nullable=true)
     */
    private $observerUid;

    /**
     * @var Staff
     *
     * @Groups({"private"})
     * @ORM\ManyToOne(targetEntity="App\Entity\Staff")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="staff_id", referencedColumnName="id")
     * })
     */
    private $staff;

    /**
     * @var string
     *
     * @Groups({"public", "private"})
     * @ORM\Column(type="string", options={"default" : ""})
     */
    private $messageId = '';

    /**
     * @var ?Commission
     *
     * @ORM\ManyToOne(targetEntity="Commission")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="commission_id", referencedColumnName="id")
     * })
     * @Groups({"public","private"})
     */
    private $commission;

    /**
     * @param array $attachments
     */
    public function setAttachments(array $attachments) : void
    {
        $this->attachments = array_map(
            function ($item) {
                if (! is_array($item)) {
                    return $item;
                }
                if (isset($item['hide'])) {
                    return $item;
                }
                $item['hide'] = false;
                return $item;
            },
            $attachments
        );
    }

    /**
     * @param array $categories
     */
    public function setCategories(?array $categories) : void
    {
        if ($categories === null) {
            $categories = [];
        }
        $this->categories = $categories;
    }

    public function setInitiative(?int $initiative) : void
    {
        if ($initiative === null) {
            $initiative = 2;
        }
        $this->initiative = $initiative;
    }

    /**
     * @param \DateTime $approvedAt
     */
    public function setApprovedAt(?\DateTime $approvedAt) : void
    {
        $this->approvedAt = $approvedAt;
    }

    /**
     * @param \DateTime $deletedAt
     */
    public function setDeletedAt(?\DateTime $deletedAt) : void
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @param \DateTime $highlightedAt
     */
    public function setHighlightedAt(?\DateTime $highlightedAt) : void
    {
        $this->highlightedAt = $highlightedAt;
    }

    /**
     * @param \DateTime $processedAt
     */
    public function setProcessedAt(?\DateTime $processedAt) : void
    {
        $this->processedAt = $processedAt;
    }

    /**
     * @param string $description
     */
    public function setDescription(?string $description) : void
    {
        if ($description === null) {
            $description = '';
        }
        $this->description = $description;
    }

    public function setCommission($commission) : void
    {
        $this->commission = $commission;
    }

    /**
     * @param string $comment
     */
    public function setComment(string $comment) : void
    {
        $this->comment = $comment;
    }

    public function setCreatedAt(\DateTime $createdAt) : void
    {
        $now             = new \DateTime();
        $this->createdAt = $createdAt > $now ? $now : $createdAt;
    }

    /**
     * @param bool $fromOutside
     */
    public function setFromOutside(bool $fromOutside) : void
    {
        $this->fromOutside = $fromOutside;
    }

    /**
     * @param Staff $staff
     */
    public function setStaff(?Staff $staff) : void
    {
        $this->staff = $staff;
    }

    public function getId() : ?int
    {
        return $this->id === null ? 0 : $this->id;
    }

    /**
     * @return array
     */
    public function getAttachments() : array
    {
        if ($this->attachments === null) {
            return [];
        }
        return $this->attachments;
    }

    /**
     * @return array
     */
    public function getCategories() : array
    {
        if (! is_array($this->categories)) {
            return [$this->categories];
        }
        return $this->categories;
    }

    /**
     * @return int
     */
    public function getInitiative() : int
    {
        return $this->initiative;
    }

    /**
     * @return \DateTime
     */
    public function getApprovedAt() : ?\DateTime
    {
        return $this->approvedAt;
    }

    public function getDeletedAt() : ?\DateTime
    {
        return $this->deletedAt;
    }

    /**
     * @return \DateTime
     */
    public function getHighlightedAt() : ?\DateTime
    {
        return $this->highlightedAt;
    }

    /**
     * @return \DateTime
     */
    public function getProcessedAt() : ?\DateTime
    {
        return $this->processedAt;
    }

    /**
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }


    public function getComment() : string
    {
        return $this->comment;
    }

    public function getCreatedAt() : ?\DateTime
    {
        if ($this->createdAt === null) {
            return null;
        }
        return $this->createdAt;
    }

    public function isFromOutside() : bool
    {
        return $this->fromOutside;
    }

    public function getObserverUid() : ?string
    {
        return $this->observerUid;
    }

    public function setObserverUid(?string $observerUid) : void
    {
        $this->observerUid = $observerUid;
    }

    public function getCommissionCode() : string
    {
        return $this->commissionCode;
    }

    /**
     * @param string $commissionCode
     */
    public function setCommissionCode(?string $commissionCode) : void
    {
        if ($commissionCode === null) {
            $this->commissionCode = '';
        }

        $this->commissionCode = $commissionCode;
    }

    public function getMessageId() : string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId) : void
    {
        if ($messageId === null) {
            $messageId = '';
        }
        $this->messageId = $messageId;
    }

    public function getCommission() : ?Commission
    {
        return $this->commission;
    }
}
