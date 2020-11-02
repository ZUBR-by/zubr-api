<?php

namespace App\Courts\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="judge_career")
 * @ORM\Entity
 * */
class JudgeCareer
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
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Court")
     * @ORM\JoinColumn(name="court_id", referencedColumnName="id")
     */
    private $court;

    /**
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Judge")
     * @ORM\JoinColumn(name="judge_id", referencedColumnName="id")
     */
    private $judge;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $timestamp;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $decreeNumber;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=500, nullable=false, options={"default" : ""})
     */
    private $comment;
}