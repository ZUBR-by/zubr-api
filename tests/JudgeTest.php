<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JudgeTest extends WebTestCase
{
    public function testCollection() : void
    {
        $client = static::createClient();

        $client->request('GET', '/judge');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testItem() : void
    {
        $client = static::createClient();

        $client->request('GET', '/judge/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testCollectionFilter() : void
    {
        $client = static::createClient();

        $client->request('GET', '/judge?tags[]=top');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
