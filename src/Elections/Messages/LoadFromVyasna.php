<?php

namespace App\Elections\Messages;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFromVyasna extends Command
{
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
        $this->setName('load:messages:vyasna');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->connection->transactional(function () {
            $this->connection->delete('message', ['initiative' => 0]);
            foreach (['vyasna_violations.json', 'vyasna_1.json', 'vyasna_2.json'] as $file) {
                $violations = \json_decode(
                    file_get_contents($this->projectDir . '/datasets/messages/' . $file),
                    true
                );
                $approvedAt = date('Y-m-d H:i:s');
                foreach ($violations as $violation) {
                    $violation['message_id']      = $violation['uuid'];
                    $violation['categories']      = \json_encode($violation['categories']);
                    $violation['approved_at']     = $approvedAt;
                    $violation['created_at']      = (new \DateTime($violation['createdAt']))->format('Y-m-d H:i:s');
                    $violation['commission_code'] = $violation['commissionCode'];
                    $violation['comment']         = '';
                    $violation['from_outside']    = 0;
                    $violation['attachments']     = '[]';
                    unset($violation['commissionCode'], $violation['uuid'], $violation['createdAt']);
                    $this->connection->insert('message', $violation);
                }
            }
        });


        return 0;
    }
}
