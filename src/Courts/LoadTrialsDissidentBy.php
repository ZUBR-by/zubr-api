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
        $mapTrusted = [
            'Гродно, Доватора 2/1'                                      => '04-002-03-01',
            'Гродно, Доватора 2'                                        => '04-002-03-01',
            'Гродно, ул. Доватора 2'                                    => '04-002-03-01',
            'Минск, Дунина-Марцинкевича 1/2'                            => '07-008-03-01',
            'Минск, ул. Дунина-Марцинкевича 1/2'                        => '07-008-03-01',
            'Минск, суд Фрунзенского р-на, ул. Дунина-Марцинкевича 1/2' => '07-008-03-01',
        ];

        $map = [
            "Гродно, ул.Дубко, 9",
            "Брест, Машерова 8",
            "Минск, пр-т Газеты Правда 27",
            "Минск, Кирова 21",
            "Минск, Дунина-Марцинкевича 1/3",
            "г.Мозырь. ул Пролетарская,86, Суд Мозырского района",
            "Гродно, суд Ленинского района, Доватора 2",
            "Брест, Машерова 8, каб.330",
            "г.Гродно, суд Ленинского района, ул. Доватора, 2/1",
            "Минск, суд Центрального района, ул.Кирова, 21",
            "Гродно, Дубко 9",
            "Минск, суд Фрунзенского р-на, ул. Дунина-Марцинкевича 1/2",
            "Сморгонь, Гастелло 31",
            "Минск, Партизанский пр-т 75а",
            "Минск, Логойский тракт 3",
            "Жлобин, Урицкого 59",
            "Минск, Толбухина 9",
            "Минск, пр-т Газеты Звезда 27",
            "Жлобин, суд Жлобинского района",

        ];

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
                            'court_id'  => $mapTrusted[$trial['address']] ?? null
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
