<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindMissingJudgesCommand extends Command
{
    private Connection $dbal;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('decisions:broken');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $data          = $this->dbal->fetchFirstColumn(
            'SELECT extra FROM decisions WHERE judge_id is NULL AND source = \'spring96\''
        );
        $missingJudges = [];
        foreach ($data as $item) {
            $extra = json_decode($item, true);
            if (!isset($extra['judge'])) {
                continue;
            }
            if (!in_array($extra['judge'], $missingJudges)) {
                $missingJudges[] = $extra['judge'];
            }
        }
        $output->writeln(implode(PHP_EOL, $missingJudges));

        return 0;
    }
}
