<?php

namespace App\Elections\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="staff",
 * )
 * @ORM\Entity
 * @ApiResource(
 *  collectionOperations={
 *      "get"={"security"="is_granted('ROLE_USER')"}
 *  },
 *  itemOperations={
 *     "get"={"security"="is_granted('ROLE_USER')"}
 *  }
 * )
 */
class Staff implements UserInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="string", length=255)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank
     */
    private $password = '';

    /**
     * @var array
     * @ORM\Column(type="json", nullable=false)
     */
    private $commissions = [];

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank
     */
    private $type;

    public function getId() : string
    {
        return $this->id;
    }

    public function getPassword() : string
    {
        return $this->password;
    }

    public function setPassword(string $password) : self
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles()
    {
        return ['ROLE_USER', 'ROLE_' . strtoupper($this->type)];
    }

    public function getSalt()
    {
        return null;
    }

    public function eraseCredentials()
    {
    }

    public function getUsername() : string
    {
        return $this->id;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function isModerator() : bool
    {
        return $this->type === 'moderator';
    }

    public function getCommissions() : array
    {
        return $this->commissions;
    }
}
