<?php

namespace App\Courts\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="attachment"
 * )
 **/
class Attachment
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
     * @var Judge|null
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Decision")
     * @ORM\JoinColumn(name="decision_id", referencedColumnName="id")
     */
    private $decision;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=false)
     */
    private $original;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     */
    private $edited;
}
