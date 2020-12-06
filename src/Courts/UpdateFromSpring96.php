<?php

namespace App\Courts;

use App\TranslatedFullName;
use Doctrine\DBAL\Connection;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

class UpdateFromSpring96 extends Command
{
    private Connection $connection;
    private string $projectDir;

    public function __construct(Connection $connection, string $projectDir)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('load:spring96');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->connection->transactional(function () use ($output) {
            $client = new Client();

            $response = $client->get('https://spring96.org/persecution?show=all');
            $crawler  = new Crawler($response->getBody()->getContents());
            $crawler  = $crawler->filter(
                'html body div.persecution_content p.pagination:first-of-type a'
            );

            $countOld             = 1;
            $countCurrent         = $crawler->count();
            $translations         = json_decode(
                file_get_contents($this->projectDir . '/datasets/courts/names.json'),
                true
            );
            $translationsArticles = json_decode(
                file_get_contents($this->projectDir . '/datasets/courts/articles.json'),
                true
            );
            $missingJudges        = [];
            $missingCourts        = [];
            $courts               = $this->loadCourts();

            foreach (range(970, ($countCurrent - $countOld) + 1) as $page) {
                $output->writeln('Page: ' . $page);
                $response = $client->get(
                    'https://spring96.org/persecution?show=all&page=' . $page,
                );
                $html     = $response->getBody()->getContents();
                $crawler  = new Crawler($html);
                $crawler  = $crawler->filter(
                    'html body div.persecution_content table.table.table-bordered tbody'
                );
                $crawler  = new Crawler($crawler->html());
                try {
                    $crawler->filter('tr')->each(function (Crawler $crawler) use (
                        $output,
                        $translationsArticles,
                        $translations,
                        $missingJudges,
                        $missingCourts,
                        $courts
                    ) {
                        $map      = [
                            0  => 'id',
                            1  => 'event_date',
                            2  => 'full_name',
                            3  => 'sex',
                            4  => 'article',
                            5  => 'court_date',
                            6  => 'judge',
                            7  => 'court_raw',
                            8  => 'arrest',
                            9  => 'fine',
                            10 => 'extra',
                        ];
                        $decision = [];
                        foreach ($crawler->filter('td') as $key => $node) {
                            $decision[$map[$key]] = preg_replace('|  +|', '', trim($node->textContent));
                        }
                        if (in_array(
                            $decision['article'],
                            [
                                '',
                                '18.23 КаАП - «Парушэнне правілаў дарожнага руху пешаходам і іншымі ўдзельнікамі дарожнага руху», 23.4 КаАП – «непадпарадкаваньне законным патрабаваньням службовай асобы»',
                                '18.23 КаАП - «Парушэнне правілаў дарожнага руху пешаходам і іншымі ўдзельнікамі дарожнага руху», 18.23 КаАП - «Парушэнне правілаў дарожнага руху пешаходам і іншымі ўдзельнікамі дарожнага руху»',
                                '18.23 КаАП - «Парушэнне правілаў дарожнага руху пешаходам і іншымі ўдзельнікамі дарожнага руху», 18.23 КаАП - «Парушэнне правілаў дарожнага руху пешаходам і іншымі ўдзельнікамі дарожнага руху», 18.23 КаАП - «Парушэнне правілаў дарожнага руху пешаходам і іншымі ўдзельнікамі дарожнага руху»',
                                '21.14 ч.2 КаАп - «парушэнне правіл добраўпарадкавання і ўтрымання населеных пунктаў»',
                                '19.3 КаАП (Парушэнне парадку і (або) умоў выканання работ на гісторыка-культурных каштоўнасцях)',
                                '21.14 ч.2 КаАп - «парушэнне правіл добраўпарадкавання і ўтрымання населеных пунктаў», 21.14 ч.2 КаАп - «парушэнне правіл добраўпарадкавання і ўтрымання населеных пунктаў»',
                                '18.1 - «наўмыснае блакаваньне транспартных камунікацый»',
                                '"18.23 КаАП - «Парушэнне правілаў дарожнага руху пешаходам і іншымі ўдзельнікамі дарожнага руху», 18.23 КаАП - «Парушэнне правілаў дарожнага руху пешаходам і іншымі ўдзельнікамі дарожнага руху»"',
                                '18.23 КаАП - «Парушэнне правілаў дарожнага руху пешаходам і іншымі ўдзельнікамі дарожнага руху»',
                            ]
                        )) {
                            return;
                        }
                        if ($decision['arrest'] === '' && $decision['fine'] === '') {
                            return;
                        }
                        $data = [
                            'is_sensitive' => 0,
                            'timestamp'    => $decision['court_date'] ?: $decision['event_date'],
                            'full_name'    => $decision['full_name'],
                            'description'  => $decision['event_date'] ?: null,
                            'extra'        => json_encode(
                                [
                                    'extra'     => $decision['extra'],
                                    'judge'     => $decision['judge'],
                                    'sex'       => $decision['sex'],
                                    'court'     => $decision['court_raw'],
                                    'full_name' => $decision['full_name'],
                                ],
                                JSON_UNESCAPED_UNICODE
                            ),
                            'source'       => 'spring96',
                            'article'      => $decision['article'],
                            'court_id'     => $courts[$decision['court_raw']] ?? null,
                        ];
                        if ($decision['arrest'] === '' && $decision['fine'] === '') {
                            return;
                        }
                        if ($decision['court_raw'] !== '' && $data['court_id'] === null) {
                            $missingCourts[] = $decision['court_raw'];
                        }

                        $judgeId = null;
                        if ($decision['judge']) {
                            $judgeName = new TranslatedFullName($decision['judge'], $translations);
                            $judgeDB   = $this->connection->fetchAllAssociative(
                                <<<'TAG'
 SELECT id, full_name
   FROM judge
  WHERE LEFT(full_name, :ln) = :fn
TAG
                                ,
                                ['fn' => $judgeName->lastName(), 'ln' => mb_strlen($judgeName->lastName())]
                            );
                            if (! $judgeDB) {
                                $missingJudges[] = $judgeName->toString();
                                $judgeId         = null;
                            } else {
                                $judgeId = null;
                                foreach ($judgeDB as $person) {

                                    $var = explode(' ', $person['full_name']);
                                    if (! isset($var[1])) {
                                        throw new Exception($person['full_name']);
                                    }
                                    [$person['last_name'], $person['first_name'], $person['middle_name']] = $var;

                                    if ($judgeName->hasSameFirstNameFirstLetter($person['first_name'])
                                        || $judgeName->firstName() === $person['first_name']
                                        || $judgeName->firstName() === '') {
                                        $judgeId = $person['id'];
                                        break;
                                    }
                                }
                            }
                        }
                        if (! $judgeId && ! $data['court_id']) {
                            return;
                        }
                        $data['judge_id'] = $judgeId;
                        if ($decision['arrest']) {
                            $data['aftermath_amount'] = preg_replace('/[^0-9]/', '', $decision['arrest']);
                            $data['aftermath_type']   = 'arrest';
                        } else {
                            $aftermath = explode('б.в.', trim($decision['fine']));
                            if (count($aftermath) == 2) {
                                $data['aftermath_type']   = 'fine';
                                $data['aftermath_amount'] = $aftermath[0];
                            } else {
                                $data['aftermath_extra'] = $decision['fine'];
                            }
                            $aftermath['extra'] = trim($decision['fine']);
                        }
                        $data['article'] = json_encode(
                            array_unique(
                                array_map(
                                    'trim',
                                    explode(
                                        ',',
                                        strtr($data['article'], $translationsArticles)
                                    )
                                )
                            )
                        );
                        try {
                            $this->connection->insert('decisions', $data);
                        } catch (Throwable $e) {
                            throw $e;
                        }
                    });
                } catch (Throwable $e) {
                    $output->writeln($e);
                    break;
                }

            }
            $missingJudges = array_unique($missingJudges);
            sort($missingJudges);
            $output->write(implode(PHP_EOL, array_unique($missingJudges)) . PHP_EOL);
            $output->writeln(count($missingJudges));
            $output->writeln('Courts');
            $output->write(implode(PHP_EOL, array_unique($missingCourts)) . PHP_EOL);
        });

        return 0;
    }

    public function loadCourts()
    {
        return json_decode(file_get_contents($this->projectDir . '/datasets/courts/courts_translated.json'), true);
    }
}
