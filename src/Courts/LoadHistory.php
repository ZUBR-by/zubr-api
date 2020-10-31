<?php

namespace App\Courts;

use DateTime;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function App\iterateCSV;

class LoadHistory extends Command
{
    const COURT          = 0;
    const FULL_NAME      = 2;
    const UKAZ           = 4;
    const PERIOD         = 3;
    const UNSET_POSITION = 8;
    const SET_POSITION   = 5;

    private Connection $connection;
    private string $projectDir;

    public function __construct(Connection $connection, string $projectDir)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('load:history')->addOption('force');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->connection->transactional(function () use ($output, $input) {
            $limit = ($input->getOption('verbose') ? 1 : 10000);
            $this->connection->executeQuery('DELETE FROM judge_career');
            $inserts = array_merge(
                $this->prepareInserts('judge_career', $limit, $output),
            );
            foreach ($inserts as $index => $insert) {
                $this->connection->executeQuery($insert);
            }
        });

        return 0;
    }

    private function prepareInserts(string $dataset, int $limit, OutputInterface $output) : array
    {
        $inserts = [];
        $fields  = implode(
            ', ',
            [
                'judge_id',
                'court_id',
                'timestamp',
                'type',
                'decree_number',
                'position',
                'comment',
            ]
        );
        $sql     = 'INSERT INTO ' . $dataset . ' (' . $fields . ') VALUES ' . PHP_EOL;

        $all           = [];
        $missingCourts = [];
        $parse         = function ($dataset) use (&$missingCourts, $limit, $sql) : array {

            $eol        = fn($value) => str_replace("\n", ' ', $value);
            $longSpaces = fn($value) => preg_replace("/\s\s+/", ' ', $value);
            $allSpaces  = fn($value) => str_replace(' ', '', $value);
            $rows       = [];
            $courtName  = null;
            foreach (iterateCSV($this->projectDir . '/datasets/courts/history/' . $dataset . '.csv') as $row) {
                if (! array_filter($row)) {
                    continue;
                }
                if ($courtName === null) {
                    $courtName = $row[self::COURT];
                }

                if ($courtName && $row[0]) {
                    $courtName = $row[self::COURT];
                }

                $courtCode = $this->connection->fetchOne(
                    'SELECT id FROM court WHERE name = ?',
                    [$courtName]
                );
                if (! $courtCode) {
                    $missingCourts[] = $courtName;
                    continue;
                }
                $courtCode = "'$courtCode'";

                $row[self::FULL_NAME] = $eol($row[self::FULL_NAME]);
                if (! $row[self::FULL_NAME]) {
                    continue;
                }
                $chunks = explode(' ', $row[self::FULL_NAME]);
                if (! isset($chunks[2])) {
                    echo $row[self::FULL_NAME] . PHP_EOL;
                    continue;
                }
                [$lname, $fname] = $chunks;
                $judge = $this->connection->fetchAssociative(
                    'SELECT * FROM judge WHERE last_name = :lname AND first_name = :fname',
                    ['lname' => $lname, 'fname' => $fname]
                );
                if (! $judge) {
                    echo $row[self::FULL_NAME] . PHP_EOL;
                    continue;
                }

                $decreeNumber = null;
                $timestamp    = null;
                if (strpos($row[self::UKAZ], '№') !== false) {
                    $row[self::UKAZ] = preg_replace('|[^0-9№.]|', '', $row[self::UKAZ]);
                    $chunks          = explode('№', $row[self::UKAZ]);
                    try {
                        $timestamp    = "'" . (new DateTime($allSpaces($chunks[0])))->format('Y-m-d') . "'";
                        $decreeNumber = $allSpaces($chunks[1]);
                    } catch (Throwable $e) {
                        $timestamp = 'null';
                        echo $e->getMessage() . PHP_EOL;
                    }
                } elseif ($row[self::UKAZ] === '') {
                    $timestamp    = "'2000-01-01'";
                    $decreeNumber = null;
                } else {
                    echo 'parse_error:' . $row[self::UKAZ] . PHP_EOL;
                    $timestamp    = 'null';
                    $decreeNumber = 'parse_error:' . $row[self::UKAZ];
                }
                $row[3] = trim($row[3]);
                $rows[] = [
                    $judge['id'],
                    $courtCode,
                    $timestamp,
                    '"appointed"',
                    is_numeric($decreeNumber) ? "'" . $longSpaces($decreeNumber) . "'" : 0,
                    "'" . $row[self::SET_POSITION] . "'",
                    "'{$row[3]}'",
                ];
                if ($row[7]) {
                    $courtCodeReleased = $this->connection->fetchOne(
                        'SELECT id FROM court WHERE name = ?',
                        [$row[7]]
                    );
                    if (! $courtCodeReleased) {
                        echo $dataset . ':' . $row[7] . PHP_EOL;
                        continue;
                    }
                    $rows[] = [
                        $judge['id'],
                        "'" . $courtCodeReleased . "'",
                        $timestamp,
                        '"released"',
                        is_numeric($decreeNumber) ? "'" . $longSpaces($decreeNumber) . "'" : 0,
                        "'{$row[self::SET_POSITION]}'",
                        "'{$row[9]}'",
                    ];
                }
            }
            $r = array_map(fn($x) => $sql . '(' . implode(',', $x) . ')', $rows);

            return $r;
        };

        foreach (['07-capital', '06-mogilev', '05-minsk', '04-grodno', '03-gomel', '02-vitebsk', '01-brest'] as $file) {
            $all = array_merge($all, $parse($file));
        }
        if (count($all) > 0) {
            $inserts[] = $sql . implode(',' . PHP_EOL, $all);
        }

        $removed = (function () use (&$missingCourts, $limit, $sql) {
            $eol        = fn($value) => str_replace("\n", ' ', $value);
            $allSpaces  = fn($value) => str_replace(' ', '', $value);
            $longSpaces = fn($value) => preg_replace("/\s\s+/", ' ', $value);
            $rows    = [];
            foreach (iterateCSV($this->projectDir . '/datasets/courts/history/removed.csv') as $row) {
                $courtCode = $this->connection->fetchOne(
                    'SELECT id FROM court WHERE name = ?',
                    [$row[3]]
                );
                if (! $courtCode) {
                    $missingCourts[] = $row[3];
                    continue;
                }
                $courtCode = "'$courtCode'";

                $row[0] = $eol($row[0]);
                if (! $row[0]) {
                    continue;
                }
                $chunks = explode(' ', $row[0]);
                if (! isset($chunks[2])) {
                    echo $row[0] . PHP_EOL;
                    continue;
                }
                [$lname, $fname] = $chunks;
                $judge = $this->connection->fetchAssociative(
                    'SELECT * FROM judge WHERE last_name = :lname AND first_name = :fname',
                    ['lname' => $lname, 'fname' => $fname]
                );
                if (! $judge) {
                    echo $row[0] . PHP_EOL;
                    continue;
                }

                $decreeNumber = null;
                $timestamp    = null;
                if (strpos($row[2], '№') !== false) {
                    $row[2] = preg_replace('|[^0-9№.]|', '', $row[2]);
                    $chunks = explode('№', $row[2]);
                    try {
                        $timestamp    = "'" . (new DateTime($allSpaces($chunks[0])))->format('Y-m-d') . "'";
                        $decreeNumber = $allSpaces($chunks[1]);
                    } catch (Throwable $e) {
                        $timestamp = 'null';
                        echo $e->getMessage() . PHP_EOL;
                    }
                } elseif ($row[2] === '') {
                    $timestamp    = "'2000-01-01'";
                    $decreeNumber = null;
                } else {
                    echo 'parse_error:' . $row[2];
                    $timestamp    = 'null';
                    $decreeNumber = 'parse_error:' . $row[2];
                }
                $rows[] = [
                    $judge['id'],
                    $courtCode,
                    $timestamp,
                    '"released"',
                    is_numeric($decreeNumber) ? "'" . $longSpaces($decreeNumber) . "'" : 0,
                    "'" . $row[1] . "'",
                    "'{$row[4]}'",
                ];
            }
            $r = array_map(fn($x) => $sql . '(' . implode(',', $x) . ')', $rows);

            return $r;
        })();
        $all     = array_merge($all, $removed);

        return $all;

        $data       = [];
        $rowCounter = 0;
        $csv        = (function () {
            $result = [];
            foreach (iterateCSV($this->projectDir . '/local/courts/judges_raw.csv') as $row) {
                $result[implode(' ', [$row[2], $row[3], $row[4]])] = [
                    'photo_url'     => $row[5],
                    'photo_origin'  => $row[6],
                    'birthdate'     => $row[7],
                    'phone'         => $row[9],
                    'position'      => $row[8],
                    'comment_1'     => $row[12],
                    'comment_2'     => $row[13],
                    'alt_last_name' => $row[1],
                ];
            }
            return $result;
        })();

        foreach ($all as $fullName => $items) {
            $chunks = explode(' ', $fullName);
            if (! isset($chunks[2])) {
                echo $fullName . PHP_EOL;
            }
            $data[$fullName] = array_merge(
                [
                    'first_name'      => $chunks[1],
                    'middle_name'     => $chunks[2] ?? '',
                    'last_name'       => $chunks[0],
                    'last_court_code' => $items[0]['court_code'] ?? '',
                    'last_court_name' => $items[0]['court'] ?? '',
                    'last_position'   => $items[0]['set'] ?? $items[0]['unset'] ?? '',
                ],
                $csv[$fullName] ?? [
                    'photo_url'     => '',
                    'photo_origin'  => '',
                    'phone'         => '',
                    'birthdate'     => '',
                    'comment_1'     => '',
                    'comment_2'     => '',
                    'alt_last_name' => '',
                ]
            );
        }
        usort($data, fn($a, $b) => $a['last_court_code'] <=> $b['last_court_code']);


        file_put_contents(
            'judges.csv',
            implode(PHP_EOL,
                array_merge(
                    [
                        implode(
                            ';',
                            [
                                'Номер',
                                'Фамилия',
                                'Имя',
                                'Отчество',
                                'Фото',
                                'Источник фото',
                                'Последняя должность',
                                'Последний суд - код',
                                'Последний суд - название',
                                'Дата рождения',
                                'Телефон',
                            ]
                        ),
                    ],
                    array_map(fn($k, $v) => implode(
                        ';',
                        array_map(fn($it) => str_replace([';', ','], '', $it),
                            [
                                $k + 1,
                                $v['last_name'],
                                $v['first_name'],
                                $v['middle_name'],
                                $v['photo_url'],
                                $v['photo_origin'],
                                $v['last_position'],
                                $v['last_court_code'],
                                '"' . $v['last_court_name'] . '"',
                                $v['birthdate'],
                                $v['phone'],
                                $v['comment_1'],
                                $v['comment_2'],
                                $v['alt_last_name'],
                            ])
                    ),
                        array_keys($data),
                        $data
                    )
                ),

            )
        );

        foreach ($all as $fullName => $item) {
            foreach ($item as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $court = $this->connection->fetchOne(
                    'SELECT id FROM court WHERE id = ?',
                    [$row['court_code']]
                );
                if (! $court) {
                    $output->writeln('Court: ' . $row['court']);
                    continue;
                }
                [$lname, $fname, $mname] = explode(' ', $row['full_name']);
                $judge = $this->connection->fetchAssociative(
                    'SELECT * FROM judge WHERE last_name = :lname AND first_name = :fname',
                    ['lname' => $lname, 'fname' => $fname]
                );

                if (! $judge) {
                    $output->writeln('Judge: ' . $row['full_name']);
                    continue;
                }

                $startedAt = 'NULL';
                $endAt     = 'NULL';

                try {
                    if ($row['set']) {
                        $startedAt = '"' . (new DateTime(str_replace('от ', '',
                                $row['ukaz_date'])))->format('Y-m-d') . '"';
                    }
                    if ($row['unset'] && ! $row['set']) {
                        $endAt = '"' . (new DateTime(str_replace('от ', '', $row['ukaz_date'])))->format('Y-m-d') . '"';
                    }

                } catch (Throwable $e) {
                    $output->writeln('Judge: ' . $row['ukaz_date']);
                    continue;
                }
                $temp     = [
                    $judge['id'],
                    '"' . $court . '"',
                    $startedAt,
                    $endAt,
                    $row['ukaz_number'] ?: 'NULL',
                ];
                $result[] = '(' . implode(',', $temp) . ')';
                if ($rowCounter === $limit) {
                    $inserts[]  = $sql . implode(',' . PHP_EOL, $rows);
                    $rows       = [];
                    $rowCounter = 0;
                }
                $rowCounter++;
            }

        }
        if (count($result) > 0) {
            $inserts[] = $sql . implode(',' . PHP_EOL, $result);
        }

        return $inserts;
    }
}
