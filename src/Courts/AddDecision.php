<?php

namespace App\Courts;

use App\ErrorHandler;
use Aws\S3\S3Client;
use DateTime;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AddDecision extends AbstractController
{
    private Connection $dbal;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;
    }

    public function __invoke(Request $request, ErrorHandler $errorHandler)
    {
        if (! $this->getUser()) {
            return new Response('', 401);
        }

        $json = json_decode($request->getContent(), true);
        $s3   = new S3Client([
            'region'      => 'eu-north-1',
            'version'     => 'latest',
            'credentials' => [
                'key'    => $_ENV['AWS_KEY'],
                'secret' => $_ENV['AWS_SECRET'],
            ],
        ]);
        $this->dbal->transactional(function () use ($json, $s3) {
            $this->dbal->insert(
                'decisions',
                [
                    'full_name'        => sprintf(
                        '%s %s %s',
                        $json['last_name'],
                        $json['first_name'],
                        $json['middle_name']
                    ),
                    'is_sensitive'     => (int) $json['is_sensitive'],
                    'hidden_at'        => isset($json['is_hidden']) && $json['is_hidden'] === true ? (new DateTime())->format('Y-m-d H:i:s') : null,
                    'timestamp'        => (new DateTime($json['timestamp']))->format('Y-m-d'),
                    'judge_id'         => $json['judge'],
                    'court_id'         => $json['court'],
                    'source'           => 'zubr',
                    'description'      => (string) ($json['description'] ?? ''),
                    'aftermath_type'   => $json['aftermath_type'],
                    'aftermath_amount' => $json['aftermath_amount'],
                    'article'          => json_encode($json['articles']),
                    'extra'            => json_encode(['links' => array_filter(array_column($json['links'], 'url'))]),
                ]
            );
            $id = $this->dbal->lastInsertId();

            foreach ($json['attachments'] as $file) {
                $objects = ['decision_id' => $id, 'original' => null, 'edited' => null];
                [$mimeRaw, $data] = explode(',', $file['original']);
                $mime     = str_replace(['data:', ';base64'], '', $mimeRaw);
                $response = $s3->putObject([
                    'Bucket'      => 'courtsby',
                    'ContentType' => $mime,
                    'Key'         => $id . '_' . sha1(base64_decode($data)),
                    'Body'        => base64_decode($data),
                    'ACL'         => 'private',
                ]);

                $objects['original'] = json_encode([
                    'type' => $mime,
                    'url'  => $response['ObjectURL'],
                ]);

                if ($file['edited']) {
                    [$mimeRaw, $data] = explode(',', $file['edited']);
                    $mime     = str_replace(['data:', ';base64'], '', $mimeRaw);
                    $response = $s3->putObject([
                        'Bucket'      => 'courtsby',
                        'ContentType' => $mime,
                        'Key'         => $id . '_' . sha1(base64_decode($data)),
                        'Body'        => base64_decode($data),
                        'ACL'         => 'public-read',
                    ]);

                    $objects['edited'] = json_encode([
                        'type' => $mime,
                        'url'  => $response['ObjectURL'],
                    ]);
                }
                $this->dbal->insert('attachment', $objects);
            }


        });

        return $this->json([]);
    }
}
