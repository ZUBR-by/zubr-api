<?php
declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;

/**
 * @ORM\Table(name="help_requests")
 * @ORM\Entity
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get", "patch"={"security"="is_granted('ROLE_USER')"}}
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"createdAt", "deletedAt"}, arguments={"orderParameterName"="sort"}
 * )
 * @ApiFilter(ExistsFilter::class, properties={"deletedAt"})
 */
class HelpRequest
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=20, nullable=false)
     */
    private string $phone;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private string $type = 'proposal';

    /**
     * @ORM\Column(type="string", length=300, nullable=false)
     */
    private string $link;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private string $category;

    /**
     * @ORM\Column(type="string", length=500, nullable=false)
     */
    private string $contact;

    /**
     * @ORM\Column(type="decimal", nullable=true, precision=11, scale=8)
     */
    private ?string $longitude;

    /**
     * @ORM\Column(type="decimal", nullable=true, precision=11, scale=8)
     */
    private ?string $latitude;

    /**
     * @ORM\Column(type="string", length=500, nullable=false)
     */
    private string $address;

    /**
     * @ORM\Column(type="string", length=1000, nullable=false)
     */
    private string $description;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private ?DateTime $deletedAt;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private DateTime $createdAt;

    public function getCreatedAt() : DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt) : void
    {
        if ($createdAt == null) {
            $createdAt = new DateTime();
        }
        if ($this->createdAt) {
            return;
        }
        $this->createdAt = $createdAt;
    }

    public function getDeletedAt() : ?DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTime $deletedAt) : void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getPhone() : string
    {
        return $this->phone;
    }

    public function setPhone(string $phone) : void
    {
        $this->phone = $phone;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type) : void
    {
        $this->type = $type;
    }

    public function getLink() : string
    {
        return $this->link;
    }

    public function setLink(string $link) : void
    {
        $this->link = $link;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category) : void
    {
        $this->category = $category;
    }

    public function getContact() : string
    {
        return $this->contact;
    }

    public function setContact(string $contact) : void
    {
        $this->contact = $contact;
    }

    public function getLongitude() : ?float
    {
        return $this->longitude === null ? null : (float) $this->longitude;
    }

    public function setLongitude(?string $longitude) : void
    {
        $this->longitude = $longitude;
    }

    public function getLatitude() : ?float
    {
        return $this->latitude === null ? null : (float) $this->latitude;
    }

    public function setLatitude(?string $latitude) : void
    {
        $this->latitude = $latitude;
    }

    public function getAddress() : string
    {
        return $this->address;
    }

    public function setAddress(string $address) : void
    {
        $this->address = $address;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function setDescription(string $description) : void
    {
        $this->description = $description;
    }

}
