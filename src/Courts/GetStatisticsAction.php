<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

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

    public function __invoke() : JsonResponse
    {
        [$arrests, $finesRub, $fines] = $this->connection->fetchNumeric(
            <<<'TAG'
SELECT 
    (SELECT SUM(aftermath_amount) 
       FROM decisions 
      WHERE category = 'administrative' AND hidden_at IS NULL AND YEAR(timestamp) IN (2020) AND aftermath_type = 'arrest')  ,
    (SELECT SUM(IF(YEAR(timestamp) = 2020, aftermath_amount * 27, aftermath_amount * 25.5)) 
       FROM decisions 
      WHERE category = 'administrative' AND hidden_at IS NULL AND YEAR(timestamp) IN (2020) AND aftermath_type = 'fine'),
    (SELECT SUM(aftermath_amount)
       FROM decisions 
      WHERE category = 'administrative' AND hidden_at IS NULL AND YEAR(timestamp) IN (2020) AND aftermath_type = 'fine')
TAG
        );

        $tmp       = $this->connection->fetchAllAssociative(
            <<<'TAG'
SELECT COUNT(1) as num, LEFT(court_id, 2) as region, YEAR(timestamp) as year
  FROM decisions
 WHERE YEAR(timestamp) IN (2019, 2020) AND court_id IS NOT NULL AND hidden_at IS NULL 
GROUP BY LEFT(court_id, 2), YEAR(timestamp)
ORDER BY year DESC, region
TAG
        );
        $result    = ['2019' => [], '2020' => []];
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
