<?php

namespace App\Courts\Decisions;

use App\Courts\UploadEvent;
use DateTime;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

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

        try {
            $timestamp = (new DateTime($json['timestamp']))->format('Y-m-d');
        } catch (Throwable $e) {
            return new JsonResponse(['error' => 'invalid_timestamp']);
        }

        $fullName = trim(sprintf(
            '%s %s %s',
            $json['lastName'],
            $json['firstName'],
            $json['middleName']
        ));

        if (! $fullName) {
            return new JsonResponse(['error' => 'empty_fullname']);
        }

        $this->dbal->transactional(function () use ($json, $eventDispatcher, $fullName, $timestamp) {
            usort($json['outcome'], fn($a, $b) => $a['type'] <=> $b['type']);
            $this->dbal->insert(
                'decisions',
                [
                    'full_name'    => $fullName,
                    'is_sensitive' => (int) $json['isSensitive'],
                    'timestamp'    => $timestamp,
                    'judge_id'     => $json['judge'],
                    'court_id'     => $json['court'],
                    'source'       => $json['source'] ?? 'zubr',
                    'category'     => $json['category'] ?? 'administrative',
                    'description'  => (string) ($json['description'] ?? ''),
                    'outcome'      => json_encode($json['outcome'], JSON_UNESCAPED_UNICODE),
                    'articles'     => json_encode($json['articles']),
                    'extra'        => json_encode(
                        [
                            'links'        => array_filter(array_column($json['links'], 'url')),
                            'participants' => $json['participants'] ?? [],
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
