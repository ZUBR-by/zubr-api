<?php

namespace App;

use Psr\Log\LoggerInterface;
use Sentry\Client;
use Sentry\ClientInterface;
use Sentry\State\Scope;
use Throwable;

class ErrorHandler
{
    /**
     * @var Client
     */
    private $client;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, ?ClientInterface $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    public function handleException(?Throwable $e, array $context = []) : void
    {
        if ($e === null) {
            return;
        }
        $this->logger->critical($e, $context);
        if (! $this->client) {
            return;
        }
        $this->client->captureException($e, (new Scope())->setUser($context));
    }
}
