<?php

namespace App\Courts;

use Symfony\Contracts\EventDispatcher\Event;

class UploadEvent extends Event
{
    public array $data;
    public int $decisionId;

    public function __construct(int $decisionId, array $data)
    {
        $this->data       = $data;
        $this->decisionId = $decisionId;
    }
}
