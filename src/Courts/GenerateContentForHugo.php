<?php

namespace App\Courts;

use App\Courts\Entity\Judge;
use App\Courts\Entity\JudgeCareer;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class GenerateContentForHugo extends Command
{
    private Connection $connection;

    private string $projectDir;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager, string $projectDir)
    {
        $this->connection = $entityManager->getConnection();
        $this->projectDir = $projectDir;
        $this->em         = $entityManager;

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
        foreach ($judges as &$judge) {
            $path            = $judge['id'] . '.md';
            $judge['layout'] = 'judge';
            $judge['court']  = $this->em->getRepository(Judge::class)->find($judge['id'])->getCurrentCourt();
            $judge['career'] = array_map(
                fn(JudgeCareer $item) => [
                    'type'      => $item->getType(),
                    'timestamp' => $item->getTimestamp()->format(DATE_ATOM),
                    'term'      => $item->getTerm(),
                    'term_type' => $item->getTermType(),
                    'comment'   => $item->getComment(),
                    'court'     => [
                        'id'   => $item->getCourt()->getId(),
                        'name' => $item->getCourt()->getName(),
                    ],
                ],
                $this->em->getRepository(JudgeCareer::class)->findBy(
                    ['judge' => $judge['id']],
                    ['timestamp' => 'desc', 'type' => 'asc']
                )
            );
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
            $court['judges']                 = array_values(
                array_filter($judges, fn($item) => is_array($item['court']) && $item['court']['id'] === $court['id'])
            );
            $court['statistic']['arrests']   = (int) $this->connection->fetchOne(
                'SELECT SUM(aftermath_amount) 
                   FROM decisions 
                  WHERE court_id = ? AND aftermath_type = \'arrest\' AND YEAR(timestamp) = 2020',
                [$court['id']]
            );
            $fines                           = (int) $this->connection->fetchOne(
                'SELECT SUM(aftermath_amount) FROM decisions WHERE court_id = ? AND aftermath_type = \'fine\' AND YEAR(timestamp) = 2020',
                [$court['id']]
            );
            $court['statistic']['fines_rub'] = 27 * $fines;
            $court['statistic']['fines']     = $fines;
            $path                            = $court['id'] . '.md';
            unset($court['type']);
            $court['layout'] = 'court';
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

//        array_walk($paths, fn(string $path) => unlink($path));

        return 0;
    }

}
