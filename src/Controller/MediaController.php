<?php

declare(strict_types = 1);

namespace Webelop\AlbumBundle\Controller;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Webelop\AlbumBundle\Entity\Picture;
use Webelop\AlbumBundle\Service\PictureManager;

/**
 * Provide picture resizing, download and video streaming capacities
 */
class MediaController extends AbstractController
{
    /** @var PictureManager */
    private $pictureManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param PictureManager  $pictureManager
     * @param LoggerInterface $logger
     */
    public function __construct(PictureManager $pictureManager, LoggerInterface $logger)
    {
        $this->pictureManager = $pictureManager;
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @param string  $mode
     * @param int     $width
     * @param int     $height
     * @param string  $hash
     *
     * @return BinaryFileResponse
     */
    public function picture(Request $request, string $mode, int $width, int $height, string $hash): Response
    {
        if (1 == $request->query->get('resized')) {
            return $this->handleException(new RuntimeException(
                'Image has already been resized.' .
                'Please verify that the cache path matches the path for public/pictures',
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }

        try {
            $targetPath = str_replace('/pictures/', '', $request->getPathInfo());
            $cachePath = $this->pictureManager->generateResizedFile($mode, $width, $height, $hash, $targetPath);
        } catch (RuntimeException $exception) {
            return $this->handleException($exception);
        }

        return new BinaryFileResponse($cachePath);
    }

    /**
     * @param string $hash
     *
     * @return BinaryFileResponse|Response
     */
    public function download(string $hash): Response
    {
        try {
            $pictureRepo = $this->getDoctrine()->getRepository(Picture::class);
            $picture = $pictureRepo->findOneByHash($hash);
            $cachePath = $this->pictureManager->downloadFile($picture);

            $response = new BinaryFileResponse($cachePath);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($picture->getPath()));
        } catch (RuntimeException $exception) {
            return $this->handleException($exception);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param string  $hash
     *
     * @return BinaryFileResponse|Response
     */
    public function streamAction(Request $request, string $hash): Response
    {
        try {
            $pictureRepo = $this->getDoctrine()->getRepository(Picture::class);
            $picture = $pictureRepo->findOneByHash($hash);
            $cachePath = $this->pictureManager->prepareStream($picture, $request->getRequestFormat());
        } catch (RuntimeException $e) {
            return $this->handleException($e);
        }

        return new BinaryFileResponse($cachePath);;
    }

    /**
     * @param \RuntimeException $exception
     *
     * @return Response
     */
    protected function handleException(\RuntimeException $exception): Response
    {
        var_dump($exception->getMessage());
        if (Response::HTTP_NOT_FOUND === $exception->getCode()) {
            $this->logger->warning($exception->getMessage());
        } else {
            $this->logger->error($exception->getMessage());
        }

        return new Response(
            'An error occurred during the request',
            $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
