<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\iterateCSV;
use function GuzzleHttp\json_encode;

class UpdateJudges extends Command
{
    private Connection $connection;
    private string $projectDir;

    public function __construct(Connection $connection, string $projectDir)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setName('update:judges');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->connection->transactional(function () use ($output, $input) {
            $limit = ($input->getOption('verbose') ? 1 : 10000);
            foreach (iterateCSV($this->projectDir . '/datasets/courts/judge.csv') as [$csvId, $fullName, , $phone]) {
                $id = $this->connection->fetchOne('SELECT id FROM judge WHERE id = ?', [$csvId]);
                if (! $id) {
                    $output->writeln($csvId);
                    $this->connection->insert('judge', ['id' => $csvId, 'full_name' => $fullName, 'comment' => $phone]);
                } else {
                    $this->connection->update(
                        'judge',
                        ['full_name' => $fullName, 'comment' => $phone],
                        ['id' => $csvId]
                    );
                }
            }
            $this->connection->executeQuery('DELETE FROM judge_tag');
            $inserts = $this->prepareInsertsTag('judge_tag', $limit);
            foreach ($inserts as $index => $insert) {
                $this->connection->executeQuery($insert);
            }
        });

        return 0;
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
