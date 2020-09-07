<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="observer_request",
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ObserverRequest
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
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $timestamp;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $readyToChange;

    /**
     * @var string
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $commissionCode;

    /**
     * @var bool
     * @Assert\NotBlank
     * @ORM\Column(type="boolean")
     */
    private $has18;

    /**
     * @var string
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $observingDates;

    /**
     * @var string
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $lastName;

    /**
     * @var string
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $firstName;

    /**
     * @var string
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $middleName;

    /**
     * @var string
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $phone;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $alreadyParticipated;

    /**
     * @var string
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $helpManagement;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $fromCandidatesGroup;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $observingBefore;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $helpSuggestion;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $sentAt = null;

    /**
     * 1 - PV
     * 2 - HP
     * @ORM\Column(type="integer", nullable=true)
     */
    private $initiative;

    /**
     * @return int
     */
    public function getId() : ?int
    {
        return $this->id;
    }

    public function getTimestamp() : ?\DateTime
    {
        return $this->timestamp;
    }

    /**
     * @return bool
     */
    public function isReadyToChange() : bool
    {
        return $this->readyToChange;
    }

    /**
     * @return string
     */
    public function getEmail() : string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getCommissionCode() : string
    {
        return $this->commissionCode;
    }

    /**
     * @return bool
     */
    public function isHas18() : bool
    {
        return $this->has18;
    }

    /**
     * @return string
     */
    public function getObservingDates() : string
    {
        return $this->observingDates;
    }

    /**
     * @return string
     */
    public function getLastName() : string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getFirstName() : string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getMiddleName() : string
    {
        return $this->middleName;
    }

    public function getPhone() : string
    {
        return $this->phone;
    }

    public function isAlreadyParticipated() : ?bool
    {
        return $this->alreadyParticipated;
    }

    public function getHelpManagement() : ?string
    {
        return $this->helpManagement;
    }

    public function getFromCandidatesGroup() : ?string
    {
        return $this->fromCandidatesGroup;
    }

    public function getObservingBefore() : ?string
    {
        return $this->observingBefore;
    }

    public function getHelpSuggestion() : ?string
    {
        return $this->helpSuggestion;
    }

    public function setTimestamp(\DateTime $timestamp) : void
    {
        $this->timestamp = $timestamp;
    }

    public function setReadyToChange(bool $readyToChange) : void
    {
        $this->readyToChange = $readyToChange;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email) : void
    {
        $this->email = $email;
    }

    /**
     * @param string $commissionCode
     */
    public function setCommissionCode(?string $commissionCode) : void
    {
        if ($commissionCode === null) {
            $commissionCode = '';
        }
        $this->commissionCode = $commissionCode;
    }

    /**
     * @param bool $has18
     */
    public function setHas18(bool $has18) : void
    {
        $this->has18 = $has18;
    }

    /**
     * @param string $observingDates
     */
    public function setObservingDates(string $observingDates) : void
    {
        $this->observingDates = $observingDates;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName) : void
    {
        $this->lastName = $lastName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName) : void
    {
        $this->firstName = $firstName;
    }

    /**
     * @param string $middleName
     */
    public function setMiddleName(string $middleName) : void
    {
        $this->middleName = $middleName;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone) : void
    {
        $this->phone = $phone;
    }

    /**
     * @param bool $alreadyParticipated
     */
    public function setAlreadyParticipated(?bool $alreadyParticipated) : void
    {
        $this->alreadyParticipated = $alreadyParticipated;
    }

    /**
     * @param string $helpManagement
     */
    public function setHelpManagement(?string $helpManagement) : void
    {
        $this->helpManagement = $helpManagement;
    }

    /**
     * @param string $fromCandidatesGroup
     */
    public function setFromCandidatesGroup(?string $fromCandidatesGroup) : void
    {
        $this->fromCandidatesGroup = $fromCandidatesGroup;
    }

    /**
     * @param string $observingBefore
     */
    public function setObservingBefore(?string $observingBefore) : void
    {
        $this->observingBefore = $observingBefore;
    }

    /**
     * @param string $helpSuggestion
     */
    public function setHelpSuggestion(?string $helpSuggestion) : void
    {
        $this->helpSuggestion = $helpSuggestion;
    }

    /**
     * @ORM\PrePersist
     */
    public function updateTimestamps() : void
    {
        if (! $this->getTimestamp()) {
            $this->setTimestamp(new \DateTime());
        }
    }

    /**
     * @return \DateTime|null
     */
    public function getSentAt() : ?\DateTime
    {
        return $this->sentAt;
    }

    /**
     * @param \DateTime|null $sendAt
     */
    public function setSentAt(?\DateTime $sendAt) : void
    {
        $this->sentAt = null;
    }

    public function sent()
    {
        $this->sentAt = new \DateTime();
    }

    public function saveToGoogleSheets(Google_Client $client, string $sheetId)
    {
        $service = new Google_Service_Sheets($client);
        $data = [
            $this->timestamp,
            $this->readyToChange,
            $this->email,
            $this->commissionCode,
            $this->has18,
            $this->observingDates,
            $this->firstName,
            $this->lastName,
            $this->middleName,
            $this->phone
        ];
        $data = array_map(function ($item) {
            if ($item instanceof \DateTime) {
                return $item->format('Y-m-d H:i:s');
            }
            if (is_bool($item)) {
                return (int) $item;
            }
            return $item;
        }, $data);
        $body = new Google_Service_Sheets_ValueRange(['values' => [$data]]);
        $body->setMajorDimension('ROWS');
        $service->spreadsheets_values->append(
            $sheetId,
            'main',
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    public function getInitiative() : ?int
    {
        return $this->initiative;
    }

    public function setInitiative(?int $initiative) : void
    {
        if ($initiative === null) {
            $initiative = 2;
        }
        $this->initiative = $initiative;
    }
}
