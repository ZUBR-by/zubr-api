<?php

namespace App\Courts;

use Symfony\Component\HttpFoundation\Response;
use function App\courtsDatasetsContentHash;

class GetCurrentContentHashAction
{
    public function __invoke() : Response
    {
        return new Response(courtsDatasetsContentHash());
    }
}
