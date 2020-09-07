<?php

namespace App\HonestPeople;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VerifiedObserverCountSync extends Command
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sync:honest-people');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new Client();

        $content = $client->get('https://honestpeople.by/observers-map/public/stations-data.json')->getBody()->getContents();
        $payload = json_decode($content, true);
        if (! isset($payload['stations'])) {
            throw new \LogicException('invalid payload: key stations');
        }
        $counter = 0;
        $this->connection->transactional(
            function () use ($payload, &$counter) {
                foreach ($payload['stations'] as $station) {
                    $this->connection->update(
                        'commission',
                        ['applied' => $station['applied']],
                        ['code' => $station['id']]
                    );
                    $counter++;
                }
            }
        );
        $output->writeln('Обновлено участков:' . $counter);

        return 0;
    }
}
