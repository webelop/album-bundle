<?php

namespace Webelop\AlbumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Webelop\AlbumBundle\Service\PictureManager;

/**
 * Public facing controller: shows albums to non-logged user
 *
 * todo loading.gif
 * todo Fixup view
 * todo reimport albums from existing DB.
 *   - Make hashes path specific and add a salt
 */
class AlbumController extends AbstractController
{
    /**
     * @Route("/", name = "index")
     * TODO: Make the local network rule configurable (turned off by default)
     */
    public function index()
    {
        try {
            // Redirect admin and local users to secure area
            if (true === $this->get('security.context')->isGranted('ROLE_ADMIN') ||
                true === IpUtils::checkIp($this->getRequest()->getClientIp(), '192.168.0.0/16') ||
                $this->getRequest()->getClientIp() === '127.0.0.1'
            ) {
                return $this->redirect($this->generateUrl('admin_index'));
            }
        } catch (\Exception $e) {

        }
        return $this->render('@Album/album/index.html.twig');
    }

    /**
     * @Route("/albums/{hash}/{slug}.html", name="tag_view")
     */
    public function view($hash, $slug)
    {
        $tagRepo = $this->getDoctrine()->getRepository('AlbumBundle:Tag');
        $picRepo = $this->getDoctrine()->getRepository('AlbumBundle:Picture');
        $tag = $tagRepo->findOneByHash($hash);

        if (empty($tag)) {
            throw $this->createNotFoundException('Tag not found');
        }

        $pictures = $picRepo->findByTag($tag);

        return $this->render('@Album\album\view.html.twig', [
            'tag' => $tag,
            'pictures' => $pictures
        ]);
    }

    /**
     * @Route("/download/{hash}.jpg", name="download")
     */
    public function download($hash)
    {
        $em = $this->getDoctrine()->getManager();
        $pictureRepo = $this->getDoctrine()->getRepository('AlbumBundle:Picture');
        $picture = $pictureRepo->findOneByHash($hash);

        $container = $this->get('service_container');
        $root = $container->getParameter('album_root');
        $source = $root . '/' . $picture->getFolder()->getPath() . '/' . $picture->getPath();

        $response = new BinaryFileResponse($source);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($picture->getPath()));

        return $response;
    }

    /**
     * @Route("/pictures/stream/{hash}.{_format}", name="stream", requirements={"_format":"mp4|webm"})
     */
    public function streamAction(Request $request, $hash)
    {
        $em = $this->getDoctrine()->getManager();
        $pictureRepo = $this->getDoctrine()->getRepository('AlbumBundle:Picture');
        $picture = $pictureRepo->findOneByHash($hash);

        $container = $this->get('service_container');
        $root = $container->getParameter('album_root');
        $source = $root . '/' . $picture->getFolder()->getPath() . '/.preview/' . $picture->getPath().'.'.$request->getRequestFormat();

        if (!file_exists($source)) {
            $this->createNotFoundException('Stream not found!');
        }

        $cache = $container->getParameter('cache_path');
        $cachepath = $cache . '/stream/' . $hash . '.'.$request->getRequestFormat();

        if (!is_dir($cache . '/stream/')) {
            mkdir($cache . '/stream/');
        }

        if (!file_exists($cachepath) && !symlink($source, $cachepath)) {
            $this->createNotFoundException('Stream link could not be created!');
        }

        return $this->redirectToRoute('stream', array('hash' => $hash, '_format' => $request->getRequestFormat()));
    }

}
