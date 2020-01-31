<?php

namespace Webelop\AlbumBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Webelop\AlbumBundle\Tests\Fixtures\AbstractTestCase;

class MediaControllerTest extends AbstractTestCase
{
    /**
     * @param string $mode
     * @param string $width
     * @param string $height
     * @param string $hash
     *
     * @dataProvider dataProviderPicture
     */
    public function testPicture(string $mode, string $width, string $height, string $hash, int $expectedCode)
    {
        $client = static::createClient();

        $client->request('GET', "/pictures/${mode}/${width}/${height}/${hash}.jpg");

        $this->assertEquals($expectedCode, $client->getResponse()->getStatusCode());
    }

    public function dataProviderPicture()
    {
        return [
            ['fit', '100', '100', 'pic1', Response::HTTP_OK],
            ['crop', '100', '100', 'pic1', Response::HTTP_OK],
            ['fit', 'wrong', '100', 'pic1', Response::HTTP_NOT_FOUND],
            ['crop', '100', 'wrong', 'pic1', Response::HTTP_NOT_FOUND],
            ['odd', '100', '100', 'pic1', Response::HTTP_NOT_FOUND],
            ['fit', '100', '100', 'no_such_hash', Response::HTTP_NOT_FOUND],
            ['crop', '100', '100', 'no_such_hash', Response::HTTP_NOT_FOUND],
        ];
    }
}

