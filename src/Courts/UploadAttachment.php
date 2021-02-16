<?php

namespace App\Courts;

use Aws\S3\S3Client;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UploadAttachment implements EventSubscriberInterface
{
    private Connection $dbal;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;
    }

    public static function getSubscribedEvents() : array
    {
        return [
            'uploadAttachment' => 'onUpload',
        ];
    }

    public function onUpload(UploadEvent $event) : void
    {
        $event->stopPropagation();
        if (empty($event->data)) {
            return;
        }
        $attachments = $event->data;
        $s3          = new S3Client([
            'region'      => 'eu-north-1',
            'version'     => 'latest',
            'credentials' => [
                'key'    => $_ENV['AWS_KEY'],
                'secret' => $_ENV['AWS_SECRET'],
            ],
        ]);
        $id          = $event->decisionId;
        foreach ($attachments as $file) {

            if (! isset($file['original'])) {
                continue;
            }
            if ($file['original'] === null) {
                continue;
            }
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
    }
}
