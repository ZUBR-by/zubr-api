<?php

namespace App\Courts;

use App\ErrorHandler;
use DateTime;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class EditDecision extends AbstractController
{
    private Connection $dbal;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;
    }

    public function __invoke(Request $request, ErrorHandler $errorHandler)
    {
        $content = json_decode($request->getContent(), true);
        try {
            usort($content['outcome'], fn($a, $b) => $a['type'] <=> $b['type']);
            $this->dbal->update(
                'decisions',
                [
                    'full_name'    => trim(implode(
                        ' ',
                        [$content['lastName'], $content['firstName'], $content['middleName']]
                    )),
                    'is_sensitive' => (int) $content['isSensitive'],
                    'outcome'      => json_encode($content['outcome'], JSON_UNESCAPED_UNICODE),
                    'description'  => $content['description'],
                    'source'       => $content['source'] ?? 'zubr',
                    'category'     => $content['category'] ?? 'administrative',
                    'hidden_at'    => isset($content['isHidden']) && $content['isHidden'] === true ? (new DateTime())->format('Y-m-d H:i:s') : null,
                    'timestamp'    => (new DateTime($content['timestamp']))->format('Y-m-d'),
                ],
                ['id' => $content['id']]
            );
        } catch (Throwable $e) {
            $errorHandler->handleException($e);
            return $this->json([
                'error' => true,
            ]);
        }
        return $this->json($content);
    }
}
