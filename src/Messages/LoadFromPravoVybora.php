<?php

namespace App\Messages;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFromPravoVybora extends Command
{
    //from pv to zubr
    const MAP = [
        1  => 1 - 1,
        2  => 4 - 1,
        3  => 11 - 1,
        4  => 11 - 1,
        5  => 5 - 1,
        6  => 12 - 1,
        7  => 6 - 1,
        8  => 7 - 1,
        9  => 8 - 1,
        10 => 9 - 1,
        11 => 12 - 1,
        12 => 3 - 1,
    ];
    /**
     * @var string
     */
    private $projectDir;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(string $projectDir, Connection $connection)
    {
        $this->projectDir = $projectDir;
        $this->connection = $connection;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('load:messages:pv');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $violations = \json_decode(
            file_get_contents('https://pvby.azurewebsites.net/api/csvparse/incidentsresult'),
            true
        );

        /*
         *     "polling_station_id": "02-076-0005",
                "date": "2020-08-05T19:15:00",
                "cat": 7,
                "status": 1,
                "description": "Витебская область, генные сроки."
         */
        $counter = 0;
        $this->connection->transactional(function () use ($violations, &$counter) {
            $this->connection->delete('message', ['initiative' => 1]);
            $approvedAt = date('Y-m-d H:i:s');

            foreach ($violations as $violation) {
                if ($violation['status'] !== 1 || $violation['polling_station_id'] === null) {
                    continue;
                }
                $counter++;
                $commission = $this->connection->fetchColumn(
                    'SELECT id FROM commission WHERE code = ?',
                    [$violation['polling_station_id']]
                );
                $this->connection->insert('message', [
                    'description'     => $violation['description'],
                    'commission_code' => $violation['polling_station_id'],
                    'approved_at'     => $approvedAt,
                    'initiative'      => 1,
                    'from_outside'    => 0,
                    'attachments'     => '[]',
                    'comment'         => '',
                    'categories'      => \json_encode([self::MAP[$violation['cat']]]),
                    'created_at'      => $violation['date'],
                    'commission_id'   => $commission ?: null,
                ]);
            }
        });
        $output->writeln('Сохранено ' . $counter . ' сообщений от Право выбора');

        return 0;
    }
}
