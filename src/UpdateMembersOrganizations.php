<?php

namespace App;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMembersOrganizations extends Command
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
        $this->setName('update:members')
            ->setDescription('Drop database, create schema and load datasets');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->connection->transactional(function () use ($output, $input) {
            $limit = ($input->getOption('verbose') ? 1 : 10000);
            $this->connection->executeQuery('DELETE FROM member_tag');
            $this->connection->executeQuery('DELETE FROM tag');
            $this->connection->executeQuery('DELETE FROM member');
            $this->connection->executeQuery('DELETE FROM organization');
            $inserts = array_merge(
                $this->prepareInserts('organization', $limit),
                $this->prepareInsertsMembers($limit),
                $this->prepareInserts('tag', $limit),
                $this->prepareInserts('member_tag', $limit),
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

    private function prepareInserts(string $dataset, int $limit) : array
    {
        $inserts    = [];
        $rows       = [];
        $rowCounter = 0;
        $lineNumber = 0;
        echo $this->projectDir . '/datasets/' . $dataset . '.csv' . PHP_EOL;
        $handle = fopen($this->projectDir . '/datasets/' . $dataset . '.csv', "r");
        if (fgets($handle, 4) !== "\xef\xbb\xbf") {
            rewind($handle);
        }
        $header = fgetcsv($handle);
        if ($dataset === 'commission') {
            $header[] = 'applied';
        }
        $fields = implode(', ', $header);
        $sql    = 'INSERT INTO ' . $dataset . ' (' . $fields . ') VALUES ' . PHP_EOL;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            if ($dataset === 'commission') {
                $row[] = 0;
            }
            if (count($header) !== count($row)) {
                $diff = count($header) - count($row);
                if ($diff < 0) {
                    throw new \InvalidArgumentException('Line: ' . $lineNumber);
                }
                $row = array_merge($row, array_fill(0, $diff, ''));
            }
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
        fclose($handle);

        return $inserts;
    }

    private function prepareInsertsMembers(int $limit) : array
    {
        $header     = (function () {
            $handle = fopen($this->projectDir . '/datasets/member-00_parent.csv', "r");
            if (fgets($handle, 4) !== "\xef\xbb\xbf") {
                rewind($handle);
            }
            $header = fgetcsv($handle);
            fclose($handle);
            return $header;
        })();
        $inserts    = [];
        $rows       = [];
        $rowCounter = 0;

        $excludedKeys = [];
        $header       = array_filter(
            $header,
            function ($item, $key) use (&$excludedKeys) {
                if (strpos($item, '_2') !== false) {
                    $excludedKeys[] = $key;
                    return false;
                }
                return true;
            },
            ARRAY_FILTER_USE_BOTH
        );


        $fields = implode(', ', $header);
        $sql    = 'INSERT INTO member (' . $fields . ') VALUES ' . PHP_EOL;

        foreach ([
                     '00_parent',
                     '07_minsk_capital',
                     '01_brest',
                     '02_vitebsk',
                     '03_gomel',
                     '04_grodno',
                     '05_minsk',
                     '06_mogilev',
                     '07_minsk_foreign',
                 ] as $file
        ) {
            $lineNumber = 0;
            echo $this->projectDir . '/datasets/' . $file . '.csv' . PHP_EOL;
            $handle = fopen($this->projectDir . "/datasets/member-{$file}.csv", "r");
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                if ($lineNumber === 1) {
                    continue;
                }
                $row = array_map(
                    function ($s) {
                        return $s === 'NULL' ? 'NULL' : '"' . str_replace('"', '\"', $s) . '"';
                    },
                    $row
                );
                $row = array_filter($row, function ($key) use ($excludedKeys) {
                    return ! in_array($key, $excludedKeys);
                }, ARRAY_FILTER_USE_KEY);
                if (count($header) != count($row)) {
                    throw new \InvalidArgumentException('Line: ' . $lineNumber . "\n" . implode(',', $row));
                }
                $rows[] = '(' . implode(',', $row) . ')';
                if ($rowCounter === $limit) {
                    $inserts[]  = $sql . implode(',' . PHP_EOL, $rows);
                    $rows       = [];
                    $rowCounter = 0;
                }
                $rowCounter++;
            }
        }
        if (count($rows) > 0) {
            $inserts[] = $sql . implode(',' . PHP_EOL, $rows);
        }

        return $inserts;
    }
}
