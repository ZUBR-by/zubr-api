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

    private const REGIONS = [
        '01' => 'Брестская область',
        '02' => 'Витебская область',
        '03' => 'Гомельская область',
        '04' => 'Гродненская область',
        '05' => 'Минская область',
        '06' => 'Могилевская область',
        '07' => 'Минск',
    ];

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
        /** @var Judge[] $judgesList */
        $judgesList = $this->em->getRepository(Judge::class)->findAll();
        $zip        = new ZipArchive();
        $zip->open(
            $this->projectDir . '/public/content.zip',
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        );
        $paths  = [];
        $judges = [];
        foreach ($judgesList as $judge) {
            $array           = $judge->toMarkdownJson();
            $path            = $array['id'] . '.md';
            $array['career'] = array_map(
                fn(JudgeCareer $item) => [
                    'type'      => $item->getType(),
                    'timestamp' => $item->getTimestamp()->format('d.m.Y'),
                    'term'      => $item->getTerm(),
                    'term_type' => $item->getTermType(),
                    'comment'   => $item->getComment(),
                    'court'     => [
                        'id'   => $item->getCourt()->getId(),
                        'name' => $item->getCourt()->getName(),
                    ],
                ],
                $this->em->getRepository(JudgeCareer::class)->findBy(
                    ['judge' => $judge->getId()],
                    ['timestamp' => 'desc', 'type' => 'asc']
                )
            );
            $judges[]        = $array;
            file_put_contents(
                $path,
                json_encode(
                    $array,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                )
            );
            $zip->addFile($path, 'content/judge/' . $path);
            $paths[] = $path;
        }
        $courts   = $this->connection->fetchAllAssociative('SELECT * FROM court');
        $location = [];
        foreach ($courts as &$court) {
            $region = substr($court['id'], 0, 2);
            if (! isset($location[$region])) {
                $location[$region] = [];
            }
            $location[$region][$court['id']] = [
                'name'      => $court['name'],
                'longitude' => (float) $court['longitude'],
                'latitude'  => (float) $court['latitude'],
            ];
            $court['judges']                 = array_values(
                array_filter(
                    $judges,
                    fn($item) => is_array($item['court']) && $item['court']['id'] === $court['id']
                )
            );
            $court['title']                  = $court['name'];
            $court['region']                 = self::REGIONS[substr($court['id'], 0, 2)];
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
            $zip->addFile($path, 'content/court/' . $path);
            $paths[] = $path;
        }
        $keyed = [];
        foreach ($courts as $courtItem) {
            $keyed[$courtItem['id']] = $courtItem;
        }
        file_put_contents(
            'courts.json',
            json_encode(
                $keyed,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            )
        );
        $zip->addFile('courts.json', 'data/courts.json');
        file_put_contents(
            'courts_location.json',
            json_encode(
                $location,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            )
        );
        $zip->addFile('courts_location.json', 'data/courts_location.json');
        $keyed = [];
        foreach ($judges as $judge) {
            $keyed[$judge['id']] = $judge;
        }

        file_put_contents(
            'judges.json',
            json_encode(
                $keyed,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            )
        );
        $zip->addFile('judges.json', 'data/judges.json');
        $zip->close();
        $paths[] = 'courts.json';
        $paths[] = 'courts_location.json';
        array_walk($paths, fn(string $path) => unlink($path));

        return 0;
    }

}
