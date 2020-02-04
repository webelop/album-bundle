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

        if ($expectedCode === Response::HTTP_OK) {
            $this->assertGreaterThan(0, strlen($client->getResponse()->getContent()));
        }
    }

    public function dataProviderPicture()
    {
        return [
            ['fit', '100', '100', 'hashp1', Response::HTTP_OK],
            ['crop', '100', '100', 'hashp1', Response::HTTP_OK],
            ['fit', 'wrong', '100', 'hashp1', Response::HTTP_NOT_FOUND],
            ['crop', '100', 'wrong', 'hashp1', Response::HTTP_NOT_FOUND],
            ['odd', '100', '100', 'hashp1', Response::HTTP_NOT_FOUND],
            ['fit', '100', '100', 'no_such_hash', Response::HTTP_NOT_FOUND],
            ['crop', '100', '100', 'no_such_hash', Response::HTTP_NOT_FOUND],
        ];
    }

    /**
     * @param string $hash
     * @param int    $expectedCode
     *
     * @dataProvider dataProviderDownload
     */
    public function testDownload(string $hash, int $expectedCode)
    {
        $client = static::createClient();

        $client->request('GET', "/pictures/download/${hash}.jpg");

        $this->assertEquals($expectedCode, $client->getResponse()->getStatusCode());

        if ($expectedCode === Response::HTTP_OK) {
            $this->assertGreaterThan(0, strlen($client->getResponse()->getContent()));
        }
    }

    public function dataProviderDownload()
    {
        return [
            ['hashp1', Response::HTTP_OK],
            ['hashp2', Response::HTTP_OK],
            ['hashs1', Response::HTTP_OK],
            ['no_such_hash', Response::HTTP_NOT_FOUND],
            ['no_such_hash', Response::HTTP_NOT_FOUND],
        ];
    }

    /**
     * @param string $hash
     * @param string $format
     * @param int    $expectedCode
     *
     * @dataProvider dataProviderStream
     */
    public function testStream(string $hash, string $format, int $expectedCode)
    {
        $client = static::createClient();

        $client->request('GET', "/pictures/stream/${hash}.${format}");

        $this->assertEquals($expectedCode, $client->getResponse()->getStatusCode());

        if ($expectedCode === Response::HTTP_OK) {
            $this->assertGreaterThan(0, strlen($client->getResponse()->getContent()));
        }
    }

    public function dataProviderStream()
    {
        return [
            ['hashs1', 'mp4', Response::HTTP_OK],
            ['hashs1', 'webm', Response::HTTP_OK],
            ['hashs1', 'avi', Response::HTTP_NOT_FOUND],
            ['no_such_hash', 'mp4', Response::HTTP_NOT_FOUND],
            ['no_such_hash', 'webm', Response::HTTP_NOT_FOUND],
        ];
    }
}

