<?php

namespace App\Tests;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EndpointTest extends WebTestCase
{
    public function testCommission()
    {
        $client = static::createClient();

        $client->request('GET', 'http://localhost:9010/commission/1');

        $this->assertTrue(true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testCommissions()
    {
        $client = static::createClient();

        $client->request('GET', 'http://localhost:9010/commissions');

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

    public function testMembers()
    {
        $client = static::createClient();

        $client->request('GET', 'http://localhost:9010/members');

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

    public function testOrganizations()
    {
        $client = static::createClient();

        $client->request('GET', 'http://localhost:9010/organizations');

        $this->assertTrue(true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
