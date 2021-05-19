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
    const FULL_NAME = 0;
    const COURT     = 1;

    private Connection $connection;
    private string $projectDir;

    public function __construct(Connection $connection, string $projectDir)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('load:history');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection->transactional(function () use ($output, $input) {
            $limit = ($input->getOption('verbose') ? 1 : 10000);
//            $this->connection->executeQuery('DELETE FROM judge_career');
            $inserts = array_merge(
                $this->prepareInserts('judge_career', $limit, $output),
            );
//            foreach ($inserts as $index => $insert) {
//                $this->connection->executeQuery($insert);
//            }
        });

        return 0;
    }

    private function prepareInserts(string $dataset, int $limit, OutputInterface $output): array
    {

        $fields = implode(
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
        $sql    = 'INSERT INTO ' . $dataset . ' (' . $fields . ') VALUES ' . PHP_EOL;

        $all           = [];
        $missingCourts = [];
        $missingJudges = [];
        $parse         = function ($path) use (&$missingCourts, &$missingJudges, $limit, $sql, $output): array {
            $rows      = [];
            $courtName = null;
            $counter   = 0;
            foreach (iterateCSV($path) as $row) {
                $counter++;
                if (! array_filter($row)) {
                    continue;
                }
                if (! isset($row[1])) {
                    $output->writeln('line:' . implode(',', $row) . ' . file' . $path);
                    continue;
                }
                $courtCode = $this->connection->fetchOne(
                    'SELECT id FROM court WHERE id = ?',
                    [$row[self::COURT]]
                );
                if (! $courtCode) {
                    $missingCourts[] = $row[self::COURT];
                    continue;
                }
                $rows = [
                    $courtCode,
                ];
                if (empty($row[self::FULL_NAME])) {
                    $output->writeln('empty judge line:' . implode(',', $row) . ' . file' . $path);
                    continue;
                }
                $judgeId = $this->connection->fetchAssociative(
                    'SELECT * FROM judge WHERE full_name = :fn',
                    ['fn' => $row[self::FULL_NAME]]
                );
                if (! $judgeId) {
                    $missingJudges[] = $row[self::FULL_NAME];
                    continue;
                }
                $rows[] = $judgeId;

            }
//            array_map(fn($x) => $sql . '(' . implode(',', $x) . ')', $rows);
            return $rows;
        };
        foreach (glob($this->projectDir . '/datasets/courts/history/*/*.csv') as $file) {
            $all = array_merge($all, $parse($file));
        }
        $missingJudges = array_unique($missingJudges);
        $string        = '';
        $last          = 1504;
        foreach ($missingJudges as $judge) {
            $string .= ++$last . ',' . $judge . ',,' . PHP_EOL;
        }
        file_put_contents($this->projectDir . '/var/judges.csv', $string . PHP_EOL);

        $data    = 1;
        $removed = (function () use (&$missingCourts, $limit, $sql, $output) {
            $eol        = fn ($value) => str_replace("\n", ' ', $value);
            $allSpaces  = fn ($value) => str_replace(' ', '', $value);
            $longSpaces = fn ($value) => preg_replace("/\s\s+/", ' ', $value);
            $rows       = [];
            foreach (iterateCSV($this->projectDir . '/datasets/courts/history_regions/removed.csv') as $row) {
                $courtCode = $this->connection->fetchOne(
                    'SELECT id FROM court WHERE name = ?',
                    [$row[3]]
                );
                if (! $courtCode) {
                    $output->writeln('Missing_court: ' . $row[3]);
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
                    $output->writeln('FullName: ' . $row[0]);
                    continue;
                }
                [$lname, $fname] = $chunks;
                $judge = $this->connection->fetchAssociative(
                    'SELECT * FROM judge WHERE LEFT(full_name, :ln) = :fn',
                    ['fn' => $lname . ' ' . $fname, 'ln' => mb_strlen($lname . ' ' . $fname)]
                );
                if (! $judge) {
                    $output->writeln('not_found_judge: ' . $row[0]);
                    continue;
                }

                $decreeNumber = null;
                $row[2]       = str_replace('N', '№', $row[2]);
                if (strpos($row[2], '№') !== false) {
                    $row[2] = preg_replace('|[^0-9№.]|', '', $row[2]);
                    $chunks = explode('№', $row[2]);
                    try {
                        $timestamp    = "'" . (new DateTime($allSpaces($chunks[0])))->format('Y-m-d') . "'";
                        $decreeNumber = $allSpaces($chunks[1]);
                    } catch (Throwable $e) {
                        $timestamp = 'null';
                        $output->writeln('Error' . $e->getMessage());
                    }
                } elseif ($row[2] === '') {
                    $timestamp    = "'2000-01-01'";
                    $decreeNumber = null;
                } else {
                    $output->writeln('parse_error:' . $row[2]);
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
                    '""',
                ];
            }

            return array_map(fn ($x) => $sql . '(' . implode(',', $x) . ')', $rows);
        })();
        $all     = array_merge($all, $removed);

        return $all;
    }
}
