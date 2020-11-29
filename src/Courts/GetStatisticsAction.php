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
        $tmp    = $this->connection->fetchAllAssociative(
            <<<'TAG'
SELECT COUNT(1) as num, LEFT(court_id, 2) as region, YEAR(timestamp) as year
  FROM decisions
 WHERE YEAR(timestamp) IN (2019, 2020) AND court_id IS NOT NULL
GROUP BY LEFT(court_id, 2), YEAR(timestamp)
ORDER BY year DESC, region
TAG
        );
        $result = ['2019' => [], '2020' => []];
        foreach ($tmp as $row) {
            $result[$row['year']][] = (int) $row['num'];
        }

        return $this->json([
            'data' => $result,
        ]);
    }
}
