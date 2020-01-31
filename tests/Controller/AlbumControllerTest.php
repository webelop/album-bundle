<?php

namespace Webelop\AlbumBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Webelop\AlbumBundle\Tests\Fixtures\AbstractTestCase;

class AlbumControllerTest extends AbstractTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString("Pictures", $crawler->html());
    }

    public function testView()
    {
        $client = static::createClient();
        $i = rand(0, 9);

        $crawler = $client->request('GET', "/albums/hash${i}/slug${i}.html");

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString("Tag ${i}", $crawler->html());
    }
}
