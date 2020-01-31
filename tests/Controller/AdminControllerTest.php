<?php

namespace Webelop\AlbumBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Webelop\AlbumBundle\Tests\Fixtures\AbstractTestCase;

class AdminControllerTest extends AbstractTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/manager', [], [], [
            'PHP_AUTH_USER' => 'admin@test.com',
            'PHP_AUTH_PW' => 'pa$$word',
        ]);

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString("Picture manager", $crawler->html());
        $this->assertStringContainsString("All pictures", $crawler->html());
        $this->assertStringContainsString("All tags", $crawler->html());
        $this->assertStringContainsString("New tag", $crawler->html());
    }

    /**
     * @param string|null $user
     * @param string|null $password
     *
     * @dataProvider dataProviderInvalidUsers
     */
    public function testIndexWithInvalidUser(?string $user, ?string $password)
    {
        $client = static::createClient();
        $client->request('GET', '/manager', [], [], [
            'PHP_AUTH_USER' => $user,
            'PHP_AUTH_PW' => $password,
        ]);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testFolder()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/manager/folder/TestA', [], [], [
            'PHP_AUTH_USER' => 'admin@test.com',
            'PHP_AUTH_PW' => 'pa$$word',
        ]);

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString("All pictures", $crawler->html());
        $this->assertStringContainsString("All tags", $crawler->html());
        $this->assertStringContainsString("New tag", $crawler->html());

        $this->assertStringContainsString("Folders:", $crawler->html());
        $this->assertStringContainsString("TestA", $crawler->html());
    }

    public function dataProviderInvalidUsers()
    {
        return [
            ['user@test.com', 'pa$$word'],
            ['admin@test.com', 'invalid password'],
            ['admin@test.com', null],
            [null, null],
            ['unknown@test.com', 'invalid password'],
        ];
    }

}
