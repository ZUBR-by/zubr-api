<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DecisionTest extends WebTestCase
{
    public function testCollection() : void
    {
        $client = static::createClient();

        $client->request('GET', '/decision');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testItem() : void
    {
        $client = static::createClient();

        $client->request('GET', '/decision/1');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
