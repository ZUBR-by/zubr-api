<?php

namespace App\HelpRequest;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CreateRequest
{
    private const CATEGORIES = [
        'telegram',
        'жилье',
        'иное',
        'медицинская помощь',
        'образование',
        'продукты питания',
        'транспорт',
    ];

    public function __invoke(Request $request, Connection $connection)
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return new JsonResponse(['error' => 'Данные некорректны']);
        }
        if (! isset($data['category']) && in_array($data['category'], self::CATEGORIES)) {
            return new JsonResponse(['error' => 'Неправильная категория']);
        }
        if (! isset($data['type']) && in_array($data['type'], ['demand', 'proposal'])) {
            return new JsonResponse(['error' => 'Не указан тип']);
        }

        if (! isset($data['phone'])) {
            return new JsonResponse(['error' => 'Нет телефона']);
        }
        $phone = preg_replace(
            '/[^\d]+/',
            '',
            $data['phone']
        );
        if ($data['category'] !== 'telegram') {
            $firstNumber = substr($phone, 0, 1);
            if (! in_array($firstNumber, ['3', '8'])) {
                return new JsonResponse(['error' => 'Неправильный формат телефона']);
            }
            if (
                ($firstNumber === '3' && strlen($phone) !== 12)
                || ($firstNumber === '8' && strlen($phone) !== 11)
            ) {
                return new JsonResponse(['error' => 'Неправильный формат телефона']);
            }
            if ($firstNumber === '3') {
                $phone = '+' . $phone;
            }
        } else {
            $phone = '';
        }

        $connection->insert(
            'help_requests',
            [
                'created_at'  => date('Y-m-d H:i:s'),
                'category'    => $data['category'],
                'address'     => $data['address'] ?? '',
                'phone'       => $phone,
                'link'        => $data['link'],
                'type'        => $data['type'],
                'description' => $data['description'],
                'contact'     => $data['contact'] ?? '',
                'longitude'   => $data['longitude'] ?? null,
                'latitude'    => $data['latitude'] ?? null,
            ]
        );
        $item           = $connection->fetchAssoc(
            'SELECT * FROM help_requests WHERE id = ?',
            [$connection->lastInsertId()]
        );
        $item['phones'] = [$item['phone']];
        $item['links']  = [$item['link']];

        return new JsonResponse([
            'feature' => [
                'id'          => bin2hex(random_bytes(7)),
                'properties'  => $item,
                'coordinates' => [
                    (float) $item['longitude'],
                    (float) $item['latitude'],
                ],
            ],
        ]);

    }
}
