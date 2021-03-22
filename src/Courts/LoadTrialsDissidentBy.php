<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadTrialsDissidentBy extends Command
{
    private Connection $dbal;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;
        parent::__construct('courts:trials:dissident');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $client = new Client(['base_uri' => 'https://dissidentby.com/']);

        $response = $client->get('api/v1/courts');

        $body = json_decode($response->getBody()->getContents(), true);

        $lastPage = $body['meta']['last_page'] ?? 0;
        if (! $lastPage) {
            $output->writeln('Пусто');
            return 0;
        }
        $data    = $body['data'] ?? [];
        $counter = 0;
        foreach (range(1, $lastPage) as $page) {
            if ($page !== 1) {
                $response = $client->get('api/v1/courts', ['query' => ['page' => $page]]);
                $body     = json_decode($response->getBody()->getContents(), true);
                $data     = $body['data'] ?? [];
            }
            foreach ($data as $trial) {
                foreach ($trial['prisoners'] as $person) {
                    $fullName = implode(' ', [$person['surname'], $person['name'], $person['patronymic']]);
                    $isExists = $this->dbal->fetchFirstColumn(
                        'SELECT 1 FROM trial WHERE person = ? AND timestamp = ?',
                        [$fullName, $trial['started_at']]
                    );
                    if ($isExists) {
                        continue;
                    }
                    $this->dbal->insert(
                        'trial',
                        [
                            'person'    => $fullName,
                            'timestamp' => $trial['started_at'],
                            'comment'   => $trial['address'],
                        ]
                    );
                    $counter++;
                }
            }
        }

        $output->writeln('Добавлено ' . $counter);
        return 0;
    }
}
