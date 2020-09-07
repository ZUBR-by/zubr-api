<?php

namespace App\HonestPeople;

use App\Entity\ObserverRequest;
use App\ErrorHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Exception;
use Google_Client;
use Google_Service_Sheets;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SaveObserverRequestToGoogleSheets extends Command
{
    /**
     * @var string
     */
    private $projectDir;
    /**
     * @var ErrorHandler
     */
    private $errorHandler;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    private string $sheetId;

    public function __construct(
        string $projectDir,
        string $sheetId,
        ErrorHandler $errorHandler,
        EntityManagerInterface $em
    ) {
        $this->projectDir   = $projectDir;
        $this->sheetId      = $sheetId;
        $this->errorHandler = $errorHandler;
        $this->em           = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('sync:sheet')->addOption('install', 'i', InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $client = $this->getClient($input->getOption('install'));

        /** @var ObserverRequest[] $ready */
        $ready = $this->em->getRepository(ObserverRequest::class)->findBy(
            ['sentAt' => null],
            ['timestamp' => 'desc']
        );

        $timestamp = date('Y-m-d H:i:s');
        foreach ($ready as $item) {
            try {
                $currentItem = (array) $item;
                $this->em->transactional(function () use ($item, $timestamp, $client) {
                    $item->sent();
                    $this->em->persist($item);
                    $item->saveToGoogleSheets($client, $this->sheetId);
                });
            } catch (ORMException $e) {
                $this->errorHandler->handleException($e->getPrevious() ? $e->getPrevious() : $e, $currentItem);
            } catch (Throwable $e) {
                $this->errorHandler->handleException($e, $currentItem);
            }
        }

        return 0;
    }

    private function getClient(bool $prompt)
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig($this->projectDir . '/config/credentials/google_credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $tokenPath = $this->projectDir . '/config/shared/google_token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if (! $client->isAccessTokenExpired()) {
            return $client;
        }
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            if (! $prompt) {
                throw new \LogicException();
            }
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        if (! file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));

        return $client;
    }
}
