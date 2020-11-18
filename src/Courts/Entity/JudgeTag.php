<?php

namespace App\Courts\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="judge_tag")
 * */
class JudgeTag
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
     * @var Judge|null
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Judge")
     * @ORM\JoinColumn(name="judge_id", referencedColumnName="id")
     */
    private $judge;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $tag;

    public function getTag() : string
    {
        return $this->tag;
    }
}
