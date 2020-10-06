<?php

namespace App\Tests;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SimpleTest extends WebTestCase
{
    public function testCommission()
    {
        $client = static::createClient();

        $client->request('GET', 'http://localhost:9010/commission/1');

        $this->assertTrue(true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testMember()
    {
        $client = static::createClient();

        $client->request('GET', 'http://localhost:9010/member/1');

        $this->assertTrue(true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testOrganization()
    {
        $client = static::createClient();

        $client->request('GET', 'http://localhost:9010/organization/1');

        $this->assertTrue(true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
