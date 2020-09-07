<?php

namespace App\Member;

use Aws\S3\S3Client;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class LoadImages extends Command
{
    private Connection $connection;

    private string $projectDir;

    private string $bucketMembers;

    public function __construct(Connection $connection, string $projectDir, string $bucketMembers)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;
        $this->bucketMembers = $bucketMembers;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('load:img')->setDescription('loading img');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $members = $this->connection->fetchAll(<<<'TAG'
SELECT m.id as member_id, photo_url, c.code, m.full_name
  FROM member m
  JOIN commission c ON c.id = m.commission_id
 WHERE photo_url != '' AND photo_url != ''
TAG
        );
        if (! isset($_ENV['AWS_KEY'])) {
            throw new \LogicException('Missing AWS key');
        }
        $s3             = new S3Client([
            'region'      => 'eu-north-1',
            'version'     => 'latest',
            'credentials' => [
                'key'    => $_ENV['AWS_KEY'],
                'secret' => $_ENV['AWS_SECRET'],
            ],
        ]);
        $placeholderURL = 'https://zubr.in/assets/images/user.svg';
        $guzzle         = new Client();
        $path           = __DIR__ . '/../datasets/member-*.csv';
        foreach ($members as $member) {
            if (strpos($member['photo_url'], 'members2020by.s3.eu-north-1.amazonaws.com') !== false
                || strpos($member['photo_url'], 'images/user.svg') !== false) {
                continue;
            }
            try {
                $url     = $member['photo_url'];
                $content = $guzzle->get($url, ['verify' => false])->getBody()->getContents();
                file_put_contents('/tmp/' . $member['member_id'], $content);
                $mimeType = exif_imagetype('/tmp/' . $member['member_id']);
                $ext      = image_type_to_extension(exif_imagetype('/tmp/' . $member['member_id']));
                if (! $ext) {
                    sprintf(
                        '%s - %s - %s' . PHP_EOL,
                        $member['member_id'],
                        $member['photo_url'],
                        $member['code'] . ' ' . $member['full_name']
                    );
                    continue;
                }
                $result = $s3->putObject([
                    'Bucket'      => $this->bucketMembers,
                    'ContentType' => $mimeType,
                    'Key'         => $member['code'] . ' ' . $member['full_name'] . $ext,
                    'Body'        => file_get_contents('/tmp/' . $member['member_id']),
                    'ACL'         => 'public-read',
                ]);
                exec("sed -i 's+{$member['photo_url']}+{$result['ObjectURL']}+g' $path");
            } catch (Throwable $e) {
                if ($e instanceof ClientException) {
                    $response = $e->getResponse();
                    if ($response && in_array($response->getStatusCode(), [404, 403, 400])) {
                        $output->writeln($member['photo_url'] . ':' . $response->getStatusCode());
                        exec("sed -i 's+{$member['photo_url']}+{$placeholderURL}+g' $path");
                        continue;
                    }
                }
                $output->writeln($e->getLine());
                $output->writeln($member['photo_url']);
                $output->writeln($e->getMessage());
                $output->writeln($member['member_id']);
                $output->writeln('=====');
                continue;
            }
        }


        return 0;
    }
}
