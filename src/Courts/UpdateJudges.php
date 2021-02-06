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
            $tags = [];
            foreach (iterateCSV($this->projectDir . '/datasets/courts/judge_tag.csv') as [$judge, $tagsString]) {
                $tmp = explode('|', $tagsString);
                sort($tmp);
                $tags[$judge] = $tmp;
            }
            foreach (iterateCSV($this->projectDir . '/datasets/courts/judge.csv') as [$csvId, $fullName, , $phone]) {
                $id = $this->connection->fetchOne('SELECT id FROM judge WHERE id = ?', [$csvId]);
                if (! $id) {
                    $output->writeln($csvId);
                    $this->connection->insert(
                        'judge',
                        [
                            'id'        => $csvId,
                            'full_name' => $fullName,
                            'comment'   => $phone,
                            'tags'      => \json_encode($tags[$id] ?? []),
                        ]
                    );
                } else {
                    $this->connection->update(
                        'judge',
                        [
                            'full_name' => $fullName,
                            'comment'   => $phone,
                            'tags'      => \json_encode($tags[$id] ?? []),
                        ],
                        ['id' => $csvId]
                    );
                }
            }
        });

        return 0;
    }
}
