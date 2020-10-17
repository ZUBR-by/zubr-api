<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use morphos\Russian\LastNamesInflection;
use morphos\Russian\MiddleNamesInflection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function morphos\Russian\inflectName;

class LoadHistoryData extends Command
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var string
     */
    private $projectDir;

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
        $this->setName('update:history')->addOption('force');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->connection->transactional(function () use ($output, $input) {
            $limit = ($input->getOption('verbose') ? 1 : 10000);
            $this->connection->executeQuery('DELETE FROM judge_career');
            $inserts = array_merge(
                $this->prepareInserts('judge', $limit, $output),
            );
            foreach ($inserts as $index => $insert) {
                try {
                    $this->connection->executeQuery($insert);
                } catch (\Throwable $e) {
                    throw $e;
                }
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
                'judge',
                'court',
                'started_at',
                'ended_at',
                'number',
            ]
        );
        $var     = <<<JSON
{
    "region": "02",
    "full_name": "Борисову Веронику Сергеевну",
    "court": "суд Первомайского района г. Витебска",
    "unset": "судья",
    "set": "судья",
    "period": "5",
    "ukaz_date": "от 06.02.2020",
    "ukaz_number": "41"
}
JSON;

        $rowCounter = 0;
        $result     = [];
        $sql        = 'INSERT INTO ' . $dataset . ' (' . $fields . ') VALUES ' . PHP_EOL;
        $rows       = json_decode(file_get_contents($this->projectDir . '/local/courts/history/history.json'), true);
        foreach ($rows as $row) {
//            $row   = array_map(
//                function ($s) {
//                    return $s === 'NULL' ? 'NULL' : '"' . str_replace('"', '\"', $s) . '"';
//                },
//                array_slice($row, 0, 8)
//            );
            $court = $this->connection->fetchOne(
                'SELECT id FROM court_1 WHERE name = ?',
                [$row['court']]
            );
            if (! $court) {
                $output->writeln('Court: ' . $row['court']);
                continue;
            } else {
                $var = 1;
            }
            continue;
            [$lname, $fname, $mname] = explode(' ',$row['full_name']);
            $judge = $this->connection->fetchOne(
                <<<'TAG'
SELECT id FROM judge_1 
  WHERE last_name = CONCAT('%', LEFT(:lname, LENGTH(:lname) -1), '%')
TAG
,
                [
                    'lname' => $lname,

                ]
            );
            if (! $judge) {
                $output->writeln('Judge: ' . $row['full_name']);
                continue;
            } else {
                $var = 1;
            }

            try {
                $startedAt = (new \DateTime(str_replace('от ', '', $row['ukaz_date'])))->format('Y-m-d');
            } catch (\Throwable $e) {
                $output->writeln('Judge: ' . $row['ukaz_date']);
                continue;
            }
            $temp     = [
                $judge,
                $court,
                $startedAt,
                null,
                $row['ukaz_number'],
            ];
            $result[] = '(' . implode(',', $temp) . ')';
            if ($rowCounter === $limit) {
                $inserts[]  = $sql . implode(',' . PHP_EOL, $rows);
                $rows       = [];
                $rowCounter = 0;
            }
            $rowCounter++;
        }
        if (count($result) > 0) {
            $inserts[] = $sql . implode(',' . PHP_EOL, $result);
        }

        return $inserts;
    }
}
