<?php

namespace App\Elections\HonestPeople;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class VerifyObserver
{
    public function __invoke(Request $request, string $projectDir) : JsonResponse
    {
        $content = \json_decode($request->getContent(), true);
        if (! isset($content['commission']) || ! isset($content['uid']) || ! isset($content['phone'])) {
            return new JsonResponse(['status' => 'invalid_payload']);
        }
        $requestPhone = (int) $content['phone'];
        $codes = json_decode(file_get_contents($projectDir . '/datasets/codes.json'), true);
        $phone = (int) ($codes[$content['commission']][$content['uid']] ?? false);
        if ($phone === 0 || $phone !== $requestPhone) {
            return new JsonResponse(['status' => 'not_found']);
        }

        return new JsonResponse(['status' => 'ok']);
    }
}
