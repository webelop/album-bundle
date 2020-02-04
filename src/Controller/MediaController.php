<?php

declare(strict_types = 1);

namespace Webelop\AlbumBundle\Controller;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
    /** @var bool */
    private $useBinaryFileResponse;

    /**
     * @param array           $config
     * @param PictureManager  $pictureManager
     * @param LoggerInterface $logger
     */
    public function __construct(array $config, PictureManager $pictureManager, LoggerInterface $logger)
    {
        $this->pictureManager = $pictureManager;
        $this->logger = $logger;
        $this->useBinaryFileResponse = $config['use_binary_file_response'];
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
    public function pictureAction(Request $request, string $mode, int $width, int $height, string $hash): Response
    {
        if (1 == $request->query->get('resized')) {
            return $this->handleException(new RuntimeException(
                'Image has already been resized.' .
                'Please verify that the cache path matches the path for public/pictures',
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }

        try {
            $cachePath = $this->pictureManager->generateResizedFile($mode, $width, $height, $hash);

            return $this->prepareBinaryResponse($cachePath);
        } catch (RuntimeException $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * @param string $hash
     *
     * @return BinaryFileResponse|Response
     */
    public function downloadAction(string $hash): Response
    {
        try {
            $originalPath = $this->pictureManager->downloadFile($hash);

            return $this->prepareBinaryResponse($originalPath, basename($originalPath));
        } catch (RuntimeException $exception) {
            return $this->handleException($exception);
        }
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
            $cachePath = $this->pictureManager->prepareStream($hash, $request->getRequestFormat());

            return $this->prepareBinaryResponse($cachePath);
        } catch (RuntimeException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @param \RuntimeException $exception
     *
     * @return Response
     */
    protected function handleException(\RuntimeException $exception): Response
    {
        if (Response::HTTP_NOT_FOUND === $exception->getCode()) {
            $this->logger->warning($exception->getMessage());
        } else {
            $this->logger->error($exception->getMessage());
        }

        return new RedirectResponse('/bundles/webelopalbum/images/missing-image.png');
    }

    /**
     * @param string      $cachePath
     * @param string|null $filename
     *
     * @return BinaryFileResponse|Response
     */
    private function prepareBinaryResponse(string $cachePath, ?string $filename = null): Response
    {
        // Functional tests cannot use the BinaryFileResponse because it's contents are not parsed by the crawler.
        if (!$this->useBinaryFileResponse) {
            return new Response(trim(file_get_contents($cachePath)));
        }

        $response = new BinaryFileResponse($cachePath);
        if ($filename) {
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        }

        return $response;
    }
}
