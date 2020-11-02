<?php

namespace App\Elections\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="datasets_update",
 * )
 * @ORM\Entity
 */
class DatasetsUpdate
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $gitCommit;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $checksum;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $createdAt;

    public function __construct(string $gitCommit, string $checksum)
    {
        $this->gitCommit = $gitCommit;
        $this->checksum  = $checksum;
        $this->createdAt = new DateTime();
    }
}
