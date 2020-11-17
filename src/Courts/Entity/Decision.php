<?php

namespace App\Courts\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;

/**
 * @ORM\Table(name="decisions")
 * @ORM\Entity
 * @ApiResource(
 *    collectionOperations={"get"},
 *    itemOperations={"get"}
 * )
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *         "court.id": "exact",
 *         "judge.id": "exact",
 *     }
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"aftermath_type", "timestamp"}, arguments={"orderParameterName"="sort"}
 * )
 * */
class Decision
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
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var Court|null
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Court")
     * @ORM\JoinColumn(name="court_id", referencedColumnName="id")
     */
    private $court;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=false, options={"default" : "administrative"})
     */
    private $category = 'administrative';

    /**
     * @var Judge|null
     * @ORM\ManyToOne(targetEntity="App\Courts\Entity\Judge")
     * @ORM\JoinColumn(name="judge_id", referencedColumnName="id")
     */
    private $judge;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $middleName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $aftermathType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private $aftermathExtra;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", nullable=true, precision=8, scale=2)
     */
    private $aftermathAmount;

    /**
     * @var array
     *
     * @ORM\Column(type="json", length=1000, nullable=false, options={"default" : ""})
     */
    private $article;

    /**
     * @var array
     *
     * @ORM\Column(type="json", nullable=false, options={"default" : "{}"})
     */
    private $attachments;

    /**
     * @var string
     * @ORM\Column(type="string", length=1000, nullable=false, options={"default" : ""})
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(type="string", length=1000, nullable=false, options={"default" : ""})
     */
    private $comment;

    public function getId() : int
    {
        return $this->id;
    }

    public function getTimestamp() : ?string
    {
        return $this->timestamp->format('d.m.Y');
    }

    public function timestamp() : DateTime
    {
        return $this->timestamp;
    }

    public function getCourt() : ?Court
    {
        return $this->court;
    }

    public function getJudge() : ?Judge
    {
        return $this->judge;
    }

    public function getAftermathType() : string
    {
        return $this->aftermathType;
    }

    public function getAftermathExtra() : string
    {
        return $this->aftermathExtra;
    }

    public function getAftermathAmount() : ?string
    {
        return $this->aftermathAmount;
    }

    public function getArticle() : array
    {
        return $this->article;
    }

    public function getAttachments() : array
    {
        return $this->attachments;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function getFullName() : string
    {
        return '';
    }

    public function getAftermath() : string
    {
        if (! in_array($this->aftermathType, ['arrest', 'fine'])) {
            return '';
        }
        if ($this->aftermathType === 'arrest') {
            return sprintf('%s сут.', (int) $this->aftermathAmount);
        }

        return sprintf('%s б.в.', (int) $this->aftermathAmount);
    }

    public function getCategory() : string
    {
        return $this->category;
    }

    public function getArticles() : string
    {
        $json   = /** @lang JSON */
            <<<JSON
{
    "9.3": "9.3 КаАП – «Абраза»",
    "23.34_3": "23.34 ч.3 КаАП – «арганізацыя і правядзенне несанкцыянаванага масавага мерапрыемства»",
    "23.34_2": "23.34 ч.2КаАП – «арганізатар несанкцыянаванага масавага мерапрыемства»",
    "23.34_1": "23.34 ч.1 КаАП – «парушэньне парадку арганізацыі і правядзеньня масавых мерапрыемстваў»",
    "23.14": "23.14 КаАП - «Незаконнае пранікненне на ахоўваемыя аб'екты»",
    "17.11": "17.11 КаАП - «Выраб, распаўсюд і (альбо) захаваньне экстрэмісцкіх матэрыялаў»",
    "23.30": "23.30. КаАП – «парушэньне памежнага рэжыму»",
    "18.1": "18.1 - «наўмыснае блакаваньне транспартных камунікацый»",
    "24.12": "24.12 КаАП – «непадпарадкаванне умовам прэвентыўнага нагляду»",
    "2.4": "2.4 КаАП – «Саўдзел у адміністрацыйным правапарушэнні»",
    "23.4": "23.4 КаАП – «непадпарадкаваньне законным патрабаваньням службовай асобы»",
    "22.9": "22.9 ч.2 КаАП – «распаўсюд перыядычных друкаваных выданьняў без выходных дадзеных»",
    "24.6": "24.6 КаАП - «ухіленьне ад яўкі ў орган, што вядзе працэс»",
    "17.1": "17.1 КаАП – «дробнае хуліганства»",
    "24.1": "24.1 КаАП – «непавага да суду»",
    "18.14": "18.14 КаАП – «парушэньне правілаў дарожнага руху»",
    "18.23": "18.23 КаАП - «Парушэнне правілаў дарожнага руху пешаходам і іншымі ўдзельнікамі дарожнага руху»",
    "9.4": "9.4 ч.1 КаАП РБ",
    "10.9": "10.9 КаАП – «наўмыснае знішчэньне або пашкоджаньне маёмасьці»",
    "14.5": "14.5 ч.1 КаАП – «недэклараваньне тавараў ці транспартных сродкаў»",
    "23.5": "23.5 КаАП – «абраза службовай асобы пры выканні ім службовых паўнамаоцтваў»",
    "9.10": "9.10 КаАП – «парушэньне заканадаўства аб адкліканьні дэпутата»",
    "21.14_2": "21.14 ч.2 КаАп - «парушэнне правіл добраўпарадкавання і ўтрымання населеных пунктаў»",
    "17.10": "17.10 (нацыская сімволіка)",
    "24.32": "24.32 КоАП РБ",
    "9.2": "9.2 КаАП – «паклёп»",
    "23.3": "23.3 КаАП – «Умяшальніцтва ў вырашэнне справы аб адміністрацыйным правапарушэнні»"
}
JSON;
        $hashes = json_decode($json, true);

        return implode(',', array_map(fn($item) => $hashes[$item], $this->article));
    }
}
