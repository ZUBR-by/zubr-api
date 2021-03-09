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
        $this->dbal->transactional(function () use ($output) : void {
            $count = $this->dbal->executeStatement(
                <<<'TAG'
UPDATE judge 
   SET tags = JSON_REMOVE(tags, REPLACE(JSON_SEARCH(tags, 'one', 'top'), '"', '')) 
 WHERE tags LIKE '%top%'
TAG

            );
            $output->writeln('Удалено: ' . $count);
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
                $result = $this->dbal->executeStatement(
                    'UPDATE judge SET tags = JSON_ARRAY_APPEND(tags,\'$\', \'top\') WHERE id = ?',
                    [$judge]
                );
                $output->writeln('Судья ' . $judge . ' - ' . $result);
            }
        });
        $output->writeln('Успешно сформирован новый топ-10');

        return 0;
    }
}
