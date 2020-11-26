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
 *         "source": "exact",
 *     }
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"aftermath_type", "timestamp", "category"}, arguments={"orderParameterName"="sort"}
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
    private $fullName;

    /**
     * @var string
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default" : "1"})
     */
    private $isSensitive = true;

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
     * @var array
     * @ORM\Column(type="json", length=1000, nullable=false, options={"default" : ""})
     */
    private $extra;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : "spring96"})
     */
    private $source = 'spring96';

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
        return $this->fullName;
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

    public function getArticles() : array
    {
        $json   = /** @lang JSON */
            <<<JSON
{
    "9.3": "9.3 КоАП - \" Оскорбление, то есть умышленное унижение чести и достоинства личности, выраженное в неприличной форме\"",
    "23.34_3": "23.34 ч. 3 КоАП - \"организация и проведение несанкционированных массовых мероприятий\"",
    "23.34_2": "23.34 ч.2 КоАП - \" организатор несанкционированного массового мероприятия\"",
    "23.34_1": "23.34 ч. 1 КоАП - \" нарушение порядка организации и проведения массовых мероприятий\"",
    "23.14": "23.14 КоАП - \" незаконное проникновение на охраняемые объекты\"",
    "17.11": "17.11 КоАП - \" Распространение, изготовление, хранение, перевозка информационной продукции, содержащей призывы к экстремистской деятельности или пропагандирующей такую деятельность\"",
    "23.3": "23.3 КоАП - \" Вмешательство в разрешение дела об административном правонарушении\"",
    "18.1": "18.1 - \" Умышленное блокирование транспортных коммуникаций\"",
    "24.12": "24.12 КоАП - \" Несоблюдение требований превентивного надзора или профилактического наблюдения\"",
    "2.4": "2.4 КоАП - \" Соучастие в административном правонарушении\"",
    "23.4": "23.4 КоАП - \" Неповиновение законному распоряжению или требованию должностного лица при исполнении им служебных полномочий\"",
    "22.9": "22.9 ч. 2 КоАП - \" Нарушение установленного порядка рассылки обязательных бесплатных экземпляров периодических печатных изданий, распространения эротических изданий, опубликования средством массовой информации опровержения, а равно незаконное изготовление и (или) распространение продукции средств массовой информации\"",
    "24.6": "24.6 КоАП - \"  Уклонение от явки в орган, ведущий административный или уголовный процесс, либо к судебному исполнителю\"",
    "17.1": "17.10 - \"Пропаганда и (или) публичное демонстрирование, изготовление и (или) распространение нацистской символики или атрибутики\"",
    "24.1": "24.1 КоАП - \" неуважение к суду\"",
    "18.14": "18.14 КоАП - \" Невыполнение требований сигналов регулирования дорожного движения, нарушение правил перевозки пассажиров или других правил дорожного движения\"",
    "18.23": "18.23 КоАП - \" Hарушение правил дорожного движения пешеходом и иными участниками дорожного движения либо отказ от прохождения проверки (освидетельствования)\"",
    "9.4": "9.4 ч.1 КоАП - \" Невыполнение родителями или лицами, их заменяющими, обязанностей по воспитанию детей, повлекшее совершение несовершеннолетним деяния, содержащего признаки административного правонарушения либо преступления, но не достигшим ко времени совершения такого деяния возраста, с которого наступает административная или уголовная ответственность за совершенное деяние\"",
    "10.9": "10.9 КоАП - \" Умышленные уничтожение либо повреждение имущества, повлекшие причинение ущерба в незначительном размере, если в этих действиях нет состава преступления\"",
    "14.5": "14.5 ч.1 КоАП - \" недекларирование товаров или транспортных средств\"",
    "23.5": "23.5 КоАП - \" Оскорбление должностного лица при исполнении им служебных полномочий\"",
    "9.10": "9.10 КоАП - \" Нарушение законодательства о выборах, референдуме, об отзыве депутата и о реализации права законодательной инициативы граждан\"",
    "21.14_2": "21.14 ч. 2 КоАП - \" Нарушение других правил благоустройства и содержания населенных пунктов\"",
    "24.32": "24.32 КоАП - \" Уклонение от реализации огнестрельного оружия или боеприпасов, совершенное лицом, у которого аннулировано разрешение на их хранение\"",
    "9.2": "9.2 КоАП - \" ответственность за клевету, то есть распространение заведомо ложных, позорящих другое лицо измышлений\""
}
JSON;
        $hashes = json_decode($json, true);

        return array_map(fn($item) => $hashes[$item] ?? $item, $this->article);
    }

    public function getComment() : array
    {
        return $this->extra;
    }

    public function getExtra() : array
    {
        return $this->extra;
    }
}
