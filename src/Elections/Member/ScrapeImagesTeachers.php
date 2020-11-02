<?php

namespace App\Elections\Member;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

class ScrapeImagesTeachers extends Command
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(Connection $connection, string $projectDir)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    /**t
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('load:teachers')->setDescription('schools.by');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $handle = fopen($this->projectDir . '/datasets/teacher.csv', "r");
        if (fgets($handle, 4) !== "\xef\xbb\xbf") {
            rewind($handle);
        }
        $rows    = 0;
        $counter = 0;
        $client  = new Client();
        $errors  = 0;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $rows++;
            if ($rows < 3) {
                continue;
            }
            [$addressUik,,$fio,,,,, $photo, $orig] = $row;
            $placeholderLink = 'https://content.schools.by/cache/79/5d/795dfefe52d9c5e515c537a20ca4aa66.jpg';
            if ($photo === $placeholderLink) {
                continue;
            }

            $data = $this->connection->fetchArray(
                <<<'TAG'
SELECT m.id, c.id, full_name
  FROM member m
  JOIN commission c ON m.commission_id = c.id
 WHERE full_name = ? AND c.location = ?
TAG
,
                [$fio, $addressUik]
            );
            if ($data === false) {
                continue;
            }

            [$mid, $cid, $fullName] = $data;
            $pathTmp = $this->projectDir . '/photos/' . $mid . '_' . $cid . '_' . $fullName;
            if (file_exists($pathTmp . '.jpeg')) {
                continue;
            }
            try {
                $html    = $client->get($orig)->getBody()->getContents();
                $skipXhr = false;

                $crawler = new Crawler($html);
                $data    = $crawler->filter('a.profile-photo')->attr('href');
            } catch (\InvalidArgumentException $e) {
                $data    = $photo;
                $skipXhr = true;
            } catch (ClientException $e) {
                $output->writeln($e->getMessage());
                continue;
            } catch (Throwable $e) {
                $output->writeln($orig);
                $errors++;
                continue;
            }
            if (! $skipXhr) {
                $scheme = parse_url($orig, PHP_URL_SCHEME);
                $host   = parse_url($orig, PHP_URL_HOST);
                $url    = $scheme . '://' . $host . $data;
                $html   = $client->get($url,
                    ['headers' => ['X-Requested-With' => 'XMLHttpRequest']])->getBody()->getContents();
                try {
                    $crawler = new Crawler($html);
                    $urlFull = $crawler->filter('img')->attr('src');
                } catch (Throwable $e) {
                    $output->writeln($orig);
                    $errors++;
                    continue;
                }
            } else {
                $urlFull = $data;
            }

            $content = $client->get($urlFull, ['verify' => false])->getBody()->getContents();

            file_put_contents($pathTmp, $content);
            $ext = image_type_to_extension(exif_imagetype($pathTmp));
            rename($pathTmp, $pathTmp . $ext);
        }
        $output->writeln($counter);
        $output->writeln($errors);

        return 0;
    }
}
