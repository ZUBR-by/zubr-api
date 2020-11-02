<?php

namespace App\Elections\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="final_report",
 * )
 * @ORM\Entity
 * @ApiResource(
 *     collectionOperations={"get"={"path"="/reports"}},
 *     itemOperations={"get"}
 * )
 */
class FinalReport
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     */
    private $attachments = [];

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $numberVotersFromObserver;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $numberVotersFromProtocol;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $votersReceivedBallotsCount;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $upfrontVotersCount;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $homeVotersCount;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $commissionVotersCount;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $droppedOutVotersCount;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $participatedVotersCount;
    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $ballotsCount;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $filledBallotsCount;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $damagedBallotsCount;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $unusedBallotsCount;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $votesCountDmitriev;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $votesCountKonopatskaya;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $votesCountLukashenko;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $votesCountTihanovskaya;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $votesCountCherechen;

    /**
     * @var ?int
     *
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $votesCountAgainstAll;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $commissionCode;

    /**
     * @return string
     */
    public function getCommissionCode() : ?string
    {
        return $this->commissionCode;
    }

    /**
     * @param string $commission
     */
    public function setCommissionCode(string $commission) : void
    {
        $this->commissionCode = $commission;
    }

    /**
     * @return mixed
     */
    public function getNumberVotersFromProtocol()
    {
        return $this->numberVotersFromProtocol;
    }

    public function setNumberVotersFromProtocol(?int $numberVotersFromProtocol) : void
    {
        $numberVotersFromProtocol       = $numberVotersFromProtocol < 0 ? null : $numberVotersFromProtocol;
        $this->numberVotersFromProtocol = $numberVotersFromProtocol;
    }

    public function getNumberVotersFromObserver()
    {
        return $this->numberVotersFromObserver;
    }

    public function setNumberVotersFromObserver(?int $numberVotersFromObserver) : void
    {
        $numberVotersFromObserver       = $numberVotersFromObserver < 0 ? null : $numberVotersFromObserver;
        $this->numberVotersFromObserver = $numberVotersFromObserver;
    }

    public function getAttachments() : array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments) : void
    {
        if ($attachments === null) {
            $attachments = [];
        }
        $this->attachments = $attachments;
    }

    public function getId() : int
    {
        return $this->id === null ? 0 : $this->id;
    }

    public function getVotersReceivedBallotsCount()
    {
        return $this->votersReceivedBallotsCount;
    }

    public function setVotersReceivedBallotsCount($votersReceivedBallotsCount) : void
    {
        if ($votersReceivedBallotsCount < 0) {
            $votersReceivedBallotsCount = null;
        }
        $this->votersReceivedBallotsCount = $votersReceivedBallotsCount;
    }

    public function getUpfrontVotersCount()
    {
        return $this->upfrontVotersCount;
    }

    public function setUpfrontVotersCount($upfrontVotersCount) : void
    {
        if ($upfrontVotersCount < 0) {
            $upfrontVotersCount = null;
        }
        $this->upfrontVotersCount = $upfrontVotersCount;
    }

    public function getHomeVotersCount()
    {
        return $this->homeVotersCount;
    }

    public function setHomeVotersCount($homeVotersCount) : void
    {
        if ($homeVotersCount < 0) {
            $homeVotersCount = null;
        }
        $this->homeVotersCount = $homeVotersCount;
    }

    public function getCommissionVotersCount()
    {
        return $this->commissionVotersCount;
    }

    public function setCommissionVotersCount($commissionVotersCount) : void
    {
        if ($commissionVotersCount < 0) {
            $commissionVotersCount = null;
        }
        $this->commissionVotersCount = $commissionVotersCount;
    }

    public function getDroppedOutVotersCount()
    {
        return $this->droppedOutVotersCount;
    }

    public function setDroppedOutVotersCount($droppedOutVotersCount) : void
    {
        if ($droppedOutVotersCount < 0) {
            $droppedOutVotersCount = null;
        }
        $this->droppedOutVotersCount = $droppedOutVotersCount;
    }

    public function getBallotsCount()
    {
        return $this->ballotsCount;
    }

    public function setBallotsCount($ballotsCount) : void
    {
        if ($ballotsCount < 0) {
            $ballotsCount = null;
        }
        $this->ballotsCount = $ballotsCount;
    }

    public function getFilledBallotsCount()
    {
        return $this->filledBallotsCount;
    }

    public function setFilledBallotsCount($filledBallotsCount) : void
    {
        if ($filledBallotsCount < 0) {
            $filledBallotsCount = null;
        }
        $this->filledBallotsCount = $filledBallotsCount;
    }

    public function getDamagedBallotsCount()
    {
        return $this->damagedBallotsCount;
    }

    public function setDamagedBallotsCount($damagedBallotsCount) : void
    {
        if ($damagedBallotsCount < 0) {
            $damagedBallotsCount = null;
        }
        $this->damagedBallotsCount = $damagedBallotsCount;
    }

    public function getUnusedBallotsCount()
    {
        return $this->unusedBallotsCount;
    }

    public function setUnusedBallotsCount($unusedBallotsCount) : void
    {
        if ($unusedBallotsCount < 0) {
            $unusedBallotsCount = null;
        }
        $this->unusedBallotsCount = $unusedBallotsCount;
    }

    public function getVotesCountDmitriev()
    {
        return $this->votesCountDmitriev;
    }

    public function setVotesCountDmitriev($votesCountDmitriev) : void
    {
        if ($votesCountDmitriev < 0) {
            $votesCountDmitriev = null;
        }
        $this->votesCountDmitriev = $votesCountDmitriev;
    }

    public function getVotesCountKonopatskaya()
    {
        return $this->votesCountKonopatskaya;
    }

    public function setVotesCountKonopatskaya($votesCountKonopatskaya) : void
    {
        if ($votesCountKonopatskaya < 0) {
            $votesCountKonopatskaya = null;
        }
        $this->votesCountKonopatskaya = $votesCountKonopatskaya;
    }

    public function getVotesCountLukashenko()
    {
        return $this->votesCountLukashenko;
    }

    public function setVotesCountLukashenko($votesCountLukashenko) : void
    {
        if ($votesCountLukashenko < 0) {
            $votesCountLukashenko = null;
        }
        $this->votesCountLukashenko = $votesCountLukashenko;
    }

    public function getVotesCountTihanovskaya()
    {
        return $this->votesCountTihanovskaya;
    }

    public function setVotesCountTihanovskaya($votesCountTihanovskaya) : void
    {
        if ($votesCountTihanovskaya < 0) {
            $votesCountTihanovskaya = null;
        }
        $this->votesCountTihanovskaya = $votesCountTihanovskaya;
    }

    public function getVotesCountCherechen()
    {
        return $this->votesCountCherechen;
    }

    public function setVotesCountCherechen($votesCountCherechen) : void
    {
        if ($votesCountCherechen < 0) {
            $votesCountCherechen = null;
        }
        $this->votesCountCherechen = $votesCountCherechen;
    }

    public function getVotesCountAgainstAll()
    {
        return $this->votesCountAgainstAll;
    }

    public function setVotesCountAgainstAll($votesCountAgainstAll) : void
    {
        if ($votesCountAgainstAll < 0) {
            $votesCountAgainstAll = null;
        }
        $this->votesCountAgainstAll = $votesCountAgainstAll;
    }

    public function getParticipatedVotersCount() : ?int
    {
        return $this->participatedVotersCount;
    }
}
