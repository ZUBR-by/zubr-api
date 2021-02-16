<?php

namespace App\Courts;

use DateTime;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AddDecision extends AbstractController
{
    private Connection $dbal;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;
    }

    public function __invoke(Request $request, EventDispatcherInterface $eventDispatcher) : Response
    {
        if (! $this->getUser()) {
            return new Response('', 401);
        }

        $json = json_decode($request->getContent(), true);
        $this->dbal->transactional(function () use ($json, $eventDispatcher) {
            usort($json['outcome'], fn($a, $b) => $a['type'] <=> $b['type']);
            $this->dbal->insert(
                'decisions',
                [
                    'full_name'    => sprintf(
                        '%s %s %s',
                        $json['lastName'],
                        $json['firstName'],
                        $json['middleName']
                    ),
                    'is_sensitive' => (int) $json['isSensitive'],
                    'hidden_at'    => isset($json['isHidden']) && $json['isHidden'] === true
                        ? (new DateTime())->format('Y-m-d H:i:s')
                        : null,
                    'timestamp'    => (new DateTime($json['timestamp']))->format('Y-m-d'),
                    'judge_id'     => $json['judge'],
                    'court_id'     => $json['court'],
                    'source'       => $json['source'] ?? 'zubr',
                    'category'     => $json['category'] ?? 'administrative',
                    'description'  => (string) ($json['description'] ?? ''),
                    'outcome'      => json_encode($json['outcome'], JSON_UNESCAPED_UNICODE),
                    'articles'     => json_encode($json['articles']),
                    'extra'        => json_encode(
                        [
                            'links'     => array_filter(array_column($json['links'], 'url')),
                            'witnesses' => $json['witnesses'] ?? [],
                        ],
                        JSON_UNESCAPED_UNICODE
                    ),
                ]
            );
            $id = (int) $this->dbal->lastInsertId();
            $eventDispatcher->dispatch(new UploadEvent($id, $json['attachments'] ?? []), 'uploadAttachment');
        });

        return $this->json([]);
    }
}
