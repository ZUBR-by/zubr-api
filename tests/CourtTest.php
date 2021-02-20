<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourtTest extends WebTestCase
{
    public function testCollection() : void
    {
        $client = static::createClient();

        $client->request('GET', '/court');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testItem() : void
    {
        $client = static::createClient();

        $client->request('GET', '/court/01-000-03-01');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
