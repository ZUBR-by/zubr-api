<?php

namespace App\Courts\Decisions;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetStatisticsAction extends AbstractController
{
    /**
     * @var Connection
     */
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(Request $request) : JsonResponse
    {
        $year = $request->query->getDigits('year', 2020);
        [$arrests, $fines] = $this->connection->fetchNumeric(
            <<<'TAG'
SELECT
    IFNULL((SELECT SUM(JSON_EXTRACT(outcome, '$[0].amount'))
            FROM decisions
            WHERE category = 'administrative'
              AND hidden_at IS NULL
              AND YEAR(timestamp) IN (:year)
              AND JSON_EXTRACT(outcome, '$[0].type') = 'arrest'), 0),
    IFNULL((SELECT SUM(JSON_EXTRACT(outcome, '$[0].amount'))
            FROM decisions
            WHERE category = 'administrative'
              AND hidden_at IS NULL
              AND YEAR(timestamp) IN (:year)
              AND JSON_EXTRACT(outcome, '$[0].type') = 'fine'), 0)
TAG
            ,
            [
                'year' => $year,
            ]
        );
        $rate = 1;
        switch ($year) {
            case '2021':
                $rate = 29;
                break;
            case '2020':
                $rate = 27;
                break;
            case '2019':
                $rate = 25.5;
                break;
            case '2018':
                $rate = 24.5;
                break;
            case '2017':
                $rate = 23;
                break;
        }
        $finesRub = $fines * $rate;

        $tmp       = $this->connection->fetchAllAssociative(
            <<<'TAG'
SELECT COUNT(1) as num, LEFT(court_id, 2) as region, YEAR(timestamp) as year
  FROM decisions
 WHERE YEAR(timestamp) IN (2019, 2020, 2021) AND court_id IS NOT NULL AND hidden_at IS NULL 
GROUP BY LEFT(court_id, 2), YEAR(timestamp)
ORDER BY year DESC, region
TAG
        );
        $result    = ['2019' => [], '2020' => [], '2021' => []];
        $maxYear   = 2019;
        $maxRegion = '07';
        $maxValue  = 0;
        foreach ($tmp as $row) {
            $value = (int) $row['num'];
            if ($value > $maxValue) {
                $maxValue  = $value;
                $maxYear   = $row['year'];
                $maxRegion = $row['region'];
            }
            $result[$row['year']][$row['region']] = $value;
        }

        return $this->json([
            'data' => [
                'max'     => [
                    'year'   => (int) $maxYear,
                    'region' => $maxRegion,
                ],
                'regions' => $result,
                'total'   => [
                    'finesRub' => $finesRub,
                    'fines'    => $fines,
                    'arrests'  => $arrests,
                ],
            ],
        ]);
    }
}
