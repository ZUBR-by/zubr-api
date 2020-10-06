<?php

namespace App\Tests;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SimpleTest extends WebTestCase
{
    public function testShowPost()
    {
        $client = static::createClient();

        $client->request('GET', '/commission/1');

        $this->assertTrue(true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
