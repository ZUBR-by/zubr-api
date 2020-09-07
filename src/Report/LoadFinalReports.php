<?php

namespace App\Report;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function json_encode;

class LoadFinalReports extends Command
{
    private const FIELDS = [
        '1. Агульная колькасць выбаршчыкаў на ўчастку (вызначаецца па спісу грамадзян, якія маюць права ўдзельнічаць у выбарах)'
        => 'number_voters_from_protocol',

        '2. Колькасць выбаршчыкаў, якія атрымалі бюлетэні (вызначаецца шляхам падліку подпісаў выбаршчыкаў у спісе грамадзян, якія маюць права ўдзельнічаць у выбарах'
        => 'voters_received_ballots_count',

        '3. Колькасць выбаршчыкаў, якія прынялі ўдзел у галасаванні (вызначаецца шляхам падліку бюлетэняў, якія знаходзіліся ў скрынках для галасавання)'
        => 'participated_voters_count',

        '3.1. Колькасць выбаршчыкаў, якія прынялі ўдзел у датэрміновым галасаванні'
        => 'upfront_voters_count',

        '3.2. Колькасць выбаршчыкаў, якія прынялі ўдзел у галасаванні па месцы знаходжання'
        => 'home_voters_count',

        '3.3. Колькасць выбаршчыкаў, якія прынялі ўдзел у галасаванні ў дзень выбараў у памяшканні ўчастка для галасавання'
        => 'commission_voters_count',

        'Дмитриев Андрей Владимирович'    => 'votes_count_dmitriev',
        'Канопацкая Анна Анатольевна'     => 'votes_count_konopatskaya',
        'Лукашенко Александр Григорьевич' => 'votes_count_lukashenko',
        'Тихановская Светлана Георгиевна' => 'votes_count_tihanovskaya',
        'Черечень Сергей Владимирович'    => 'votes_count_cherechen',

        '5. Колькасць галасоў, пададзеных за кандыдата (кандыдатаў), які (якiя) выбыў (выбылі) у перыяд датэрміновага галасавання'
                                                                         => 'dropped_out_voters_count',
        '6. Колькасць галасоў, пададзеных супраць усiх кандыдатаў'       => 'votes_count_against_all',
        '8. Колькасць бюлетэняў, прызнаных несапраўднымі'                => 'filled_ballots_count',
        '9. Колькасць бюлетэняў, атрыманых участковай выбарчай камісіяй' => 'ballots_count',
        '10. Колькасць сапсаваных бюлетэняў'                             => 'damaged_ballots_count',
        '11. Колькасць нявыкарыстаных (пагашаных) бюлетэняў'             => 'unused_ballots_count',
    ];
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(Connection $connection, string $projectDir)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('update:reports');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->connection->transactional(function () use ($output, $input) {
            $this->connection->executeQuery('DELETE FROM final_report');
            $handle = fopen($this->projectDir . '/datasets/final.csv', "r");
            $header = fgetcsv($handle);
            $keys   = [];
            foreach ($header as $key => $item) {
                if (isset(self::FIELDS[$item])) {
                    $keys[$key] = self::FIELDS[$item];
                }
            }
            while (($row = fgetcsv($handle)) !== false) {
                $tmp = [
                    'commission_code' => $row[0],
                    'attachments'     => json_encode(array_map(
                        function ($item) {
                            return [
                                'url' => $item,
                            ];
                        },
                        array_filter(array_slice($row, 19, 7, false)),
                    )),
                ];
                foreach ($row as $key => $value) {
                    if (! isset($keys[$key])) {
                        continue;
                    }
                    $tmp[$keys[$key]] = $value === '' ? null : $value;
                }
                $this->connection->insert('final_report', $tmp);
            }
        });

        return 0;
    }
}
