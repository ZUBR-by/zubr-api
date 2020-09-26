<?php

namespace App\Member;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadImagesLocal extends Command
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var string
     */
    private $projectDir;
    private string $bucketMembers;

    public function __construct(Connection $connection, string $projectDir, string $bucketMembers)
    {
        $this->connection    = $connection;
        $this->projectDir    = $projectDir;
        $this->bucketMembers = $bucketMembers;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('load:img:local')->setDescription('loading img');
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

        $files  = scandir($this->projectDir . '/local/photos');
        $photos = [];
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            $fullName = $file;
            [$fullName, $extension] = explode('.', $fullName);
            [$commissionCode, $fullName] = explode(' ', $fullName, 2);
            $extension  = str_replace('e', '', $extension);
            $commission = $this->connection->fetchColumn('SELECT id FROM commission WHERE code = ?', [$commissionCode]);
            $member     = $this->connection->fetchColumn('SELECT id FROM member WHERE commission_id = ? and full_name = ?',
                [$commission, $fullName]);

            $photos[$member] = [$file, ['png' => 'image/png;', 'jpg' => 'image/jpeg;'][$extension]];

        }

        $map = [];
        foreach ([
                     '00_parent',
                     '07_minsk_capital',
                     '01_brest',
                     '02_vitebsk',
                     '03_gomel',
                     '04_grodno',
                     '05_minsk',
                     '06_mogilev',
                     '07_minsk_foreign',
                 ] as $file
        ) {
            $path = "$this->projectDir/datasets/member-{$file}.csv";
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
        foreach ($map as $file => $item) {
            $path = "$this->projectDir/datasets/member-{$file}.csv";
            foreach ($item as $content) {
                $current    = explode(',', exec("sed -n '{$content[2]}p' $path"));
                if ($current[6] !== ''){
                    continue;
                }
                $result     = $s3->putObject([
                    'Bucket'      => $this->bucketMembers,
                    'ContentType' => $content[1],
                    'Key'         => $content[0],
                    'Body'        => file_get_contents($this->projectDir . '/local/photos/' . $content[0]),
                    'ACL'         => 'public-read',
                ]);
                $current[6] = $result['ObjectURL'];
                $changed    = implode(',', $current);
                exec("sed -i '{$content[2]}s|.*|$changed|' $path");
            }
        }

        return 0;
    }
}
