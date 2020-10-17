<?php

namespace App\Courts;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadImagesLocal extends Command
{

    private Connection $connection;

    private string $projectDir;

    public function __construct(Connection $connection, string $projectDir)
    {
        $this->connection    = $connection;
        $this->projectDir    = $projectDir;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('load:judges:local')->setDescription('loading img');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        if (! isset($_ENV['AWS_KEY'])) {
            $output->writeln('NOO');
            return 0;
        }
        $s3 = new \Aws\S3\S3Client([
            'region'      => 'eu-north-1',
            'version'     => 'latest',
            'credentials' => [
                'key'    => $_ENV['AWS_KEY'],
                'secret' => $_ENV['AWS_SECRET'],
            ],
        ]);

        $files  = scandir($this->projectDir . '/local/photos_j');
        $photos = [];
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            $fullName = $file;
            [$fullName, $extension] = explode('.', $fullName);
            [$lastName] = explode(' ', $fullName);
            $extension  = str_replace('e', '', $extension);
            $member     = $this->connection->fetchColumn(
                'SELECT id FROM judge WHERE last_name = ?',
                [$lastName]
            );
            if ($member === false) {
                $output->writeln('not found member:' . $file);
                continue;
            }
            $photos[$member] = [$file, ['png' => 'image/png;', 'jpg' => 'image/jpeg;'][$extension]];
        }
        $map = [];
        foreach ([
                     'judge_new',
                 ] as $file
        ) {
            $path = "$this->projectDir/datasets/courts/{$file}.csv";
            foreach ($photos as $memberId => $content) {
                $grep        = "grep -rn ^{$memberId}, $path | cut -f1 -d:";
                $lineNumber1 = exec($grep);
                if (! is_int($lineNumber1) && $lineNumber1) {
                    if (! isset($map[$file])) {
                        $map[$file] = [];
                    }
                    $map[$file][$memberId] = [...$photos[$memberId], $lineNumber1];
                }
            }
        }
        $uploaded = 0;
        $exists = 0;
        foreach ($map as $file => $item) {
            $path = "$this->projectDir/datasets/courts/{$file}.csv";
            foreach ($item as $content) {
                $current = explode(',', exec("sed -n '{$content[2]}p' $path"));
                if (strpos($current[6], 'courtsby') !== false) {
                    $exists++;
                }
                $result     = $s3->putObject([
                    'Bucket'      => 'courtsby',
                    'ContentType' => $content[1],
                    'Key'         => str_replace(' ', '', $content[0]),
                    'Body'        => file_get_contents($this->projectDir . '/local/photos_j/' . $content[0]),
                    'ACL'         => 'public-read',
                ]);
                $current[4] = $result['ObjectURL'];
                $changed    = implode(',', $current);
                exec("sed -i '{$content[2]}s|.*|$changed|' $path");
                $uploaded++;
            }
        }
        $output->writeln('Added: ' . ($uploaded - $exists));
        $output->writeln('Reuploaded: ' . $exists);
        return 0;
    }
}
