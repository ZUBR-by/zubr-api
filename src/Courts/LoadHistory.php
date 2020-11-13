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
                'term',
                'term_type',
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
                    'SELECT * FROM judge WHERE LEFT(full_name, :ln) = :fn',
                    ['fn' => $lname, 'ln' => mb_strlen($lname)]
                );
                if (! $judge) {
                    echo 'History_database: ' . $row[self::FULL_NAME] . PHP_EOL;
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
                    continue;
                }
                $row[3] = trim($row[3]);
                $tmp    = [
                    $judge['id'],
                    $courtCode,
                    $timestamp,
                    '"appointed"',
                    is_numeric($decreeNumber) ? "'" . $longSpaces($decreeNumber) . "'" : 0,
                    "'" . $row[self::SET_POSITION] . "'",
                    "'{$row[3]}'",
                ];
                switch ($row[3]) {
                    case '5 лет':
                        $tmp[6] = '""';
                        $tmp[]  = 5;
                        $tmp[]  = '"years"';
                        break;
                    case 'бессрочно':
                        $tmp[6] = '""';
                        $tmp[]  = 'NULL';
                        $tmp[]  = '"indefinitely"';
                        break;
                    default:
                        $tmp[] = 'NULL';
                        $tmp[] = '"period"';
                        break;
                }

                $rows[] = $tmp;

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
                        'NULL',
                        '"unknown"',
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
            $rows       = [];
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
                    'SELECT * FROM judge WHERE LEFT(full_name, :ln) = :fn',
                    ['fn' => $lname, 'ln' => mb_strlen($lname)]
                );
                if (! $judge) {
                    echo $row[0] . PHP_EOL;
                    continue;
                }

                $decreeNumber = null;
                $timestamp    = null;
                $row[2]       = str_replace('N', '№', $row[2]);
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
                    echo 'parse_error:' . $row[2] . PHP_EOL;
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
                    'NULL',
                    '"unknown"',
                ];
            }
            $r = array_map(fn($x) => $sql . '(' . implode(',', $x) . ')', $rows);

            return $r;
        })();
        $all     = array_merge($all, $removed);

        return $all;
    }
}
