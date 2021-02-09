<?php

namespace App\Courts;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\courtsDatasetsContentHash;

class CompareHashesCommand extends Command
{
    protected function configure() : void
    {
        $this->setName('courts:content:compare')
            ->addArgument('actual', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $actual  = trim($input->getArgument('actual'));
        $current = courtsDatasetsContentHash();
        $output->writeln($current, OutputInterface::VERBOSITY_VERBOSE);
        $expected = courtsDatasetsContentHash();
        file_put_contents('test', <<<TEST
$expected
$actual
TEST
);
        if ($expected !== $actual) {
            $output->writeln('diff');
        }

        return 0;
    }
}
