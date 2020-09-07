<?php

namespace App\Staff;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateModerators extends Command
{
    const COUNT = 17;

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
        $this->setName('generate:moderators');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $applied = $this->connection->executeQuery(
            'SELECT code FROM commission WHERE applied ORDER BY code > 0'
        )->fetchAll(\PDO::FETCH_COLUMN);

        $notApplied = $this->connection->executeQuery(
            'SELECT code FROM commission WHERE applied = 0 ORDER BY code'
        )->fetchAll(\PDO::FETCH_COLUMN);

        $appliedChunks    = array_chunk(
            $applied,
            round(count($applied) / self::COUNT) + 1
        );
        $notAppliedChunks = array_chunk(
            $notApplied,
            round(count($notApplied) / self::COUNT)
        );
        $members          = [];
        try {
            $this->connection->beginTransaction();
            $this->connection->executeQuery('DELETE FROM staff WHERE id LIKE \'moderator_%\'');
            foreach (range(0, self::COUNT - 1) as $index) {
                $name        = 'moderator_' . ($index + 1);
                $commissions = array_merge($appliedChunks[$index], $notAppliedChunks[$index]);
                $password    = bin2hex(random_bytes(5));
                $members[]   = $name . ',' . $password;
                $this->connection->insert(
                    'staff',
                    [
                        'password'    => password_hash($password, PASSWORD_ARGON2I),
                        'commissions' => \json_encode($commissions),
                        'id'          => $name,
                        'type'        => 'moderator',
                    ]
                );
            }
            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        file_put_contents(__DIR__ . '/../var/moderators.csv', implode(PHP_EOL, $members) . PHP_EOL);

        return 0;
    }
}
