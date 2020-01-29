<?php

namespace Webelop\AlbumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Webelop\AlbumBundle\Service\PictureManager;

/**
 * Provide picture resizing, download and video streaming capacities
 */
class MediaController extends AbstractController
{
    /**
     * @Route("/pictures/{mode}/{width}/{height}/{hash}.jpg", name = "picture")
     */
    public function picture(PictureManager $pictureManager, Request $request, $mode, $width, $height, $hash)
    {
        if (1 == $request->query->get('resized')) {
            throw new \UnexpectedValueException(
                'Image has already been resized.'.
                'Please verify that the cache path matches the path for public/pictures'
            );
        }
        $targetPath = str_replace('/pictures/', '', $request->getPathInfo());
        $cachePath = $pictureManager->generateResizedFile($mode, $width, $height, $hash, $targetPath);

        return new BinaryFileResponse($cachePath);
    }

    /**
     * @Route("/download/{hash}.jpg", name="download")
     */
    public function download(PictureManager $pictureManager, $hash)
    {
        $pictureRepo = $this->getDoctrine()->getRepository('WebelopAlbumBundle::Picture');
        $picture = $pictureRepo->findOneByHash($hash);
        $cachePath = $pictureManager->downloadFile($picture);

        $response = new BinaryFileResponse($cachePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($picture->getPath()));

        return $response;
    }

    /**
     * @Route("/pictures/stream/{hash}.{_format}", name="stream", requirements={"_format":"mp4|webm"})
     */
    public function streamAction(PictureManager $pictureManager, Request $request, $hash)
    {
        $pictureRepo = $this->getDoctrine()->getRepository('WebelopAlbumBundle::Picture');
        $picture = $pictureRepo->findOneByHash($hash);
        $cachePath = $pictureManager->prepareStream($picture, $request->getRequestFormat());

        return new BinaryFileResponse($cachePath);;
    }
}
