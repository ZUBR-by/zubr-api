<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetTop10JudgesCommand extends Command
{

    private Connection $dbal;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setName('judges:top10');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->dbal->transactional(function () : void {
            $this->dbal->executeStatement('DELETE FROM judge_tag WHERE tag = \'top\'');

            $judges = $this->dbal->fetchFirstColumn(
                <<<'TAG'
  SELECT judge_id, COUNT(1) as num
    FROM decisions 
   WHERE category = 'administrative' AND hidden_at is NULL AND judge_id IS NOT NULL
GROUP BY judge_id
ORDER BY num DESC
   LIMIT 10
TAG

            );
            foreach ($judges as $judge) {
                $this->dbal->insert('judge_tag', ['judge_id' => $judge, 'tag' => 'top']);
            }
        });
        $output->writeln('Успешно сформирован новый топ-10');

        return 0;
    }
}
