<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetCriminalJudgesCommand extends Command
{

    private Connection $dbal;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setName('judges:criminal');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->dbal->transactional(function () use ($output) : void {
            $count = $this->dbal->executeStatement(
                <<<'TAG'
UPDATE judge 
   SET tags = JSON_REMOVE(tags, REPLACE(JSON_SEARCH(tags, 'one', 'criminal'), '"', '')) 
 WHERE tags LIKE '%criminal%'
TAG

            );
            $output->writeln('Удалено: ' . $count);
            $judges = $this->dbal->fetchFirstColumn(
                <<<'TAG'
  SELECT distinct judge_id
    FROM decisions 
   WHERE category = 'criminal' AND hidden_at is NULL AND judge_id IS NOT NULL
TAG
            );
            foreach ($judges as $judge) {
                $result = $this->dbal->executeStatement(
                    'UPDATE judge SET tags = JSON_ARRAY_APPEND(tags,\'$\', \'criminal\') WHERE id = ?',
                    [$judge]
                );
                $output->writeln('Судья ' . $judge . ' - ' . $result);
            }
        });
        $output->writeln('Успешно обновлены судьи с криминалками');

        return 0;
    }
}
