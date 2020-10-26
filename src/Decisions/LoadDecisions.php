<?php

namespace App\Decisions;

use App\TranslatedFullName;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class LoadDecisions extends Command
{
    private Connection $connection;

    private string $projectDir;

    const COURTS = [
        'Слуцк, раённы'          => '05-018-03-01',
        'Смалявічы, раённы'      => '05-019-03-01',
        'Маладзечна'             => '05-014-03-01',
        'Дзяржынск, раённы'      => '05-008-03-01',
        'Баранавічы, раённы'     => '01-003-03-01',
        'Жлобін'                 => '03-009-03-01',
        'Барысаў'                => '05-005-03-01',
        'Бабруйск'               => '06-003-03-01',
        'Ворша'                  => '02-014-03-01',
        'Сьветлагорск'           => '03-020-03-01',
        'Докшыцы, Докшыцкі раён' => '02-009-03-01',
        'Бярозаўка, раённы'      => '01-005-03-01',
        'Полацак'                => '02-015-03-01',
        'Ліда'                   => '04-010-03-01',
        'Нясьвіж, раённы'        => '05-016-03-01',
        'Салігорск'              => '05-020-03-01',
        'Ваўкавыск, раённы'      => '04-004-03-01',
    ];

    public function __construct(Connection $connection, string $projectDir)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('load:decisions')->addOption('force');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->connection->transactional(function () use ($output, $input) {
            $this->connection->executeQuery('DELETE FROM decisions');
            $app     = $this->getApplication();
            $command = $app->find('update:judges');
            $command->run(new ArrayInput(['command' => 'update:judges']), $output);

            $app     = $this->getApplication();
            $command = $app->find('update:courts');
            $command->run(new ArrayInput(['command' => 'update:courts', '-v' => true]), $output);

            $translations         = json_decode(
                file_get_contents($this->projectDir . '/datasets/courts/names.json'),
                true
            );
            $translationsArticles = json_decode(
                file_get_contents($this->projectDir . '/datasets/courts/articles.json'),
                true
            );
            $decisions            = json_decode(
                file_get_contents($this->projectDir . '/datasets/courts/decisions.json'),
                true
            );
            $map                  = [];
            $courts               = $this->loadCourts();
            $lastId               = ((int) $this->connection->fetchOne('SELECT MAX(id) FROM judge')) + 1;
            foreach ($decisions as $decision) {
                $fullName = new TranslatedFullName($decision['full_name'], $translations);
                $data     = [
                    'timestamp'   => $decision['court_date'] ?: null,
                    'first_name'  => $fullName->firstName(),
                    'last_name'   => $fullName->lastName(),
                    'middle_name' => $fullName->middleName(),
                    'description' => $decision['event_date'] ?: null,
                    'comment'     => json_encode(
                        [
                            'extra'     => $decision['extra'],
                            'judge'     => $decision['judge'],
                            'sex'       => $decision['sex'],
                            'court'     => $decision['court'],
                            'full_name' => $decision['full_name'],
                        ],
                        JSON_UNESCAPED_UNICODE
                    ),
                    'article'     => $decision['article'],
                    'court_id'    => $courts[$decision['court']] ?? self::COURTS[$decision['court']] ?? null,
                ];
                if ($decision['arrest'] === '' && $decision['fine'] === '') {
                    continue;
                }
                if ((! $decision['court_date']) || $decision['court'] === '' || $data['court_id'] === null) {
                    continue;
                }

                $judgeId = null;
                if ($decision['judge']) {
                    $judgeName = new TranslatedFullName($decision['judge'], $translations);
                    $judgeDB   = $this->connection->fetchAssociative(
                        <<<'TAG'
 SELECT id, first_name, last_name
   FROM judge
  WHERE last_name = :ln
TAG
                        ,
                        ['ln' => $judgeName->lastName()]
                    );
                    if (! $judgeDB
                        || (
                            $judgeDB['first_name'] !== '' && $judgeDB['first_name'] !== $judgeName->firstName()
                            && (
                                $judgeDB['first_name'] !== ''
                                && $judgeName->firstName() !== ''
                                && $judgeDB['first_name'][0] !== $judgeName->firstName()[0]
                            )
                        )) {
                        $this->connection->insert(
                            'judge',
                            [
                                'id'          => $lastId,
                                'first_name'  => $judgeName->firstName(),
                                'last_name'   => $judgeName->lastName(),
                                'middle_name' => $judgeName->middleName(),
                            ]
                        );
                        $lastId++;
                        $judgeId = $this->connection->lastInsertId();
                    } else {
                        $judgeId = $judgeDB['id'];
                        if ($judgeName->firstName() !== '' && $judgeDB['first_name'] === '') {
                            $this->connection->update(
                                'judge',
                                ['first_name' => $judgeName->firstName()],
                                ['id' => $judgeId]
                            );
                        }
                    }
                }

                $data['judge_id'] = $judgeId;
                if (! isset($map[$judgeId])) {
                    $map[$judgeId] = [];
                }
                if ($judgeId) {
                    $map[$judgeId][] = $courts[$decision['court']] ?? null;
                }
                if ($decision['arrest']) {
                    $data['aftermath_amount'] = preg_replace('/[^0-9]/', '', $decision['arrest']);
                    $data['aftermath_type']   = 'arrest';
                } else {
                    $aftermath = explode('б.в.', $decision['fine'], 2);
                    if (count($aftermath) > 1) {
                        $data['aftermath_type']   = 'fine';
                        $data['aftermath_amount'] = $aftermath[0];
                    } else {
                        $data['aftermath_extra'] = $decision['fine'];
                    }
                }
                $data['article'] = json_encode(
                    array_unique(
                        array_map(
                            'trim',
                            explode(
                                ',',
                                strtr($data['article'], $translationsArticles)
                            )
                        )
                    )
                );

                try {
                    $this->connection->insert('decisions', $data);
                } catch (Throwable $e) {
                    throw $e;
                }
            }
        });

        return 0;
    }


    public function loadCourts()
    {
        $handle = fopen($this->projectDir . '/datasets/courts/courts_translated.csv', "r");
        if ($handle === false) {
            throw new \LogicException();
        }
        if (fgets($handle, 4) !== "\xef\xbb\xbf") {
            rewind($handle);
        }
        fgetcsv($handle);
        $courts = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (! $row[1]) {
                continue;
            }
            $courts[str_replace('"', '', $row[1])] = $row[0];
        }

        return $courts;
    }
}
