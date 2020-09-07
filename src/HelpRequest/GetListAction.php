<?php

namespace App\HelpRequest;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;

class GetListAction
{
    public function __invoke(Connection $connection)
    {
        $rows = $connection->fetchAll('SELECT * FROM help_requests WHERE deleted_at IS NULL');

        return new JsonResponse([
            'data' => array_map(
                function (array $item) : array {
                    $item['phones'] = [$item['phone']];
                    $item['links']  = [$item['link']];
                    return [
                        'id'          => bin2hex(random_bytes(7)),
                        'properties'  => $item,
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [
                                (float) $item['longitude'],
                                (float) $item['latitude'],
                            ],
                        ]
                    ];
                },
                $rows
            ),
        ]);
    }
}
