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
     * @var Judge|null
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\DecisionArchived")
     * @ORM\JoinColumn(name="decision_archived_id", referencedColumnName="id")
     */
    private $decisionArchived;

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

    public function isImage() : bool
    {
        if (! is_array($this->edited)) {
            return false;
        }
        return strpos($this->edited['type'], 'image') === 0;
    }

    public function url() : ?string
    {
        return $this->edited['url'] ?? null;
    }
}
