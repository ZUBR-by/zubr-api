<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class GenerateContentForHugo extends Command
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
        $this->setName('generate:content')->addOption('force');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $judges = $this->connection->fetchAllAssociative('SELECT * FROM judge');
        $zip    = new ZipArchive();
        $zip->open(
            $this->projectDir . '/public/content.zip',
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        );
        $paths = [];
        foreach ($judges as $judge) {
            $path = $judge['id'] . '.md';
            file_put_contents(
                $path,
                json_encode(
                    $judge,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                )
            );
            $zip->addFile($path, 'judge/' . $path);
            $paths[] = $path;
        }
        $courts = $this->connection->fetchAllAssociative('SELECT * FROM court');
        foreach ($courts as $court) {
            $path = $court['id'] . '.md';
            file_put_contents(
                $path,
                json_encode(
                    $court,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                )
            );
            $zip->addFile($path, 'court/' . $path);
            $paths[] = $path;
        }

        $zip->close();


        array_walk($paths, fn(string $path) => unlink($path));

        return 0;
    }

}
