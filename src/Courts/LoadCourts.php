<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\iterateCSV;

class LoadCourts extends Command
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
        $this->setName('load:courts')->addOption('force');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->connection->transactional(function () use ($output, $input) {
            $limit = ($input->getOption('verbose') ? 1 : 10000);
            $this->connection->executeQuery('DELETE FROM court');
            $inserts = $this->prepareInserts('court', $limit);
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
                'longitude',
                'latitude',
                'name',
                'description',
                'address',
                'comment',
            ]
        );
        $sql        = 'INSERT INTO ' . $dataset . ' (' . $fields . ') VALUES ' . PHP_EOL;
        foreach (iterateCSV($this->projectDir . '/datasets/courts/' . $dataset . '.csv') as $row) {
            if (! $row[4] || ! $row[2]) {
                continue;
            }
            $lineNumber++;
            $row = array_map(
                function ($s) {
                    return $s === 'NULL' ? 'NULL' : '"' . str_replace('"', '\"', $s) . '"';
                },
                $row
            );
            [$lat, $long] = explode(',', $row[4]);
            $row    = [
                $row[0],
                $long,
                $lat,
                $row[1],
                $row[3],
                $row[2],
                $row[6],
            ];
            $row[1] = str_replace('"', '', $row[1]);
            $row[2] = str_replace('"', '', $row[2]);
            $temp   = '(' . implode(',', $row) . ')';
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
