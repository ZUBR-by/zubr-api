<?php

namespace App;

use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UploadAttachment
{
    public function __invoke(Request $request, string $bucketReports) : JsonResponse
    {
        $s3      = new S3Client([
            'region'      => 'eu-north-1',
            'version'     => 'latest',
            'credentials' => [
                'key'    => $_ENV['AWS_KEY'],
                'secret' => $_ENV['AWS_SECRET'],
            ],
        ]);
        $objects = [];
        /** @var UploadedFile $file */
        foreach ($request->files->all() as $file) {
            $response = $s3->putObject([
                'Bucket'      => $bucketReports,
                'ContentType' => $file->getMimeType(),
                'Key'         => $request->attributes->get('id') . '_' . bin2hex(random_bytes(10)),
                'Body'        => file_get_contents($file->getRealPath()),
                'ACL'         => 'public-read',
            ]);

            $objects[] = [
                'type' => $file->getMimeType(),
                'url'  => $response['ObjectURL'],
            ];
        }

        return new JsonResponse($objects);
    }
}
