<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\iterateCSV;
use function GuzzleHttp\json_encode;

class LoadJudges extends Command
{
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
        $this->setName('load:judges')->addOption('force');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->connection->transactional(function () use ($output, $input) {
            $limit = ($input->getOption('verbose') ? 1 : 10000);
            $this->connection->executeQuery('DELETE FROM judge_tag');
            $this->connection->executeQuery('DELETE FROM judge');
            $inserts = array_merge($this->prepareInserts('judge', $limit),
                $this->prepareInsertsTag('judge_tag', $limit));
            foreach ($inserts as $index => $insert) {
                $this->connection->executeQuery($insert);
            }
        });

        return 0;
    }

    private function prepareInserts(string $dataset, int $limit) : array
    {
        $inserts    = [];
        $rows       = [];
        $rowCounter = 0;
        $lineNumber = 0;
        $fields     = implode(
            ', ',
            [
                'id',
                'full_name',
                'photo_origin',
                'comment',
                'tags',
            ]
        );
        $sql        = 'INSERT INTO ' . $dataset . ' (' . $fields . ') VALUES ' . PHP_EOL;

        $tags = [];
        foreach (iterateCSV($this->projectDir . '/datasets/courts/judge_tag.csv') as [$judge, $tagsString]) {
            $tmp = explode('|', $tagsString);
            sort($tmp);
            $tags[$judge] = $tmp;
        }
        foreach (iterateCSV($this->projectDir . '/datasets/courts/judge.csv') as $row) {
            $lineNumber++;
            $id    = (int) ($row[0]);
            $row   = array_map(
                function ($s) {
                    return $s === 'NULL' ? 'NULL' : '"' . str_replace('"', '\"', $s) . '"';
                },
                array_slice($row, 0, 6)
            );
            $row[] = isset($tags[$id]) ? '\'' . json_encode($tags[$id]) . '\'' : '"[]"';
            $temp  = '(' . implode(',', $row) . ')';
            if ($temp === '("")') {
                throw new \LogicException();
            }
            $rows[] = $temp;
            if ($rowCounter === $limit) {
                $inserts[]  = $sql . implode(',' . PHP_EOL, $rows);
                $rows       = [];
                $rowCounter = 0;
            }
            $rowCounter++;
        }
        if (count($rows) > 0) {
            $inserts[] = $sql . implode(',' . PHP_EOL, $rows);
        }

        return $inserts;
    }

    private function prepareInsertsTag(string $dataset, int $limit) : array
    {
        $inserts    = [];
        $rows       = [];
        $rowCounter = 0;
        $lineNumber = 0;
        $fields     = implode(
            ', ',
            [
                'judge_id',
                'tag',
            ]
        );
        $sql        = 'INSERT INTO ' . $dataset . ' (' . $fields . ') VALUES ' . PHP_EOL;
        foreach (iterateCSV($this->projectDir . '/datasets/courts/judge_tag.csv') as $row) {
            $lineNumber++;
            $row  = array_map(
                function ($s) {
                    return $s === 'NULL' ? 'NULL' : '"' . str_replace('"', '\"', $s) . '"';
                },
                $row
            );
            $temp = '(' . implode(',', $row) . ')';
            if ($temp === '("")') {
                throw new \LogicException();
            }
            $rows[] = $temp;
            if ($rowCounter === $limit) {
                $inserts[]  = $sql . implode(',' . PHP_EOL, $rows);
                $rows       = [];
                $rowCounter = 0;
            }
            $rowCounter++;
        }
        if (count($rows) > 0) {
            $inserts[] = $sql . implode(',' . PHP_EOL, $rows);
        }

        return $inserts;
    }
}
