<?php

declare(strict_types = 1);

namespace Webelop\AlbumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response, RedirectResponse};
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Webelop\AlbumBundle\Entity\{Album, Picture, Tag};
use Webelop\AlbumBundle\Form;
use Webelop\AlbumBundle\Repository\PictureRepository;
use Webelop\AlbumBundle\Repository\TagRepository;
use Webelop\AlbumBundle\Service\FolderManager;

/**
 * todo create a folder entity with last update date
 * todo allow from a folder to create an album (name and derived slug _ picture relations)
 *
 * @Route("/manager")
 */
class AdminController extends AbstractController
{
    /** @var FolderManager */
    private $folderManager;
    /** @var TagRepository */
    private $tagRepository;
    /** @var PictureRepository */
    private $pictureRepository;

    /**
     * @param FolderManager     $folderManager
     * @param TagRepository     $tagRepository
     * @param PictureRepository $pictureRepository
     */
    public function __construct(
        FolderManager $folderManager,
        TagRepository $tagRepository,
        PictureRepository $pictureRepository
    )
    {
        $this->folderManager = $folderManager;
        $this->tagRepository = $tagRepository;
        $this->pictureRepository = $pictureRepository;
    }

    /**
     * Based on a root path, show subfolders as actual folders (image containers)
     *
     * @return Response
     */
    public function index()
    {
        return $this->render('@WebelopAlbum/admin/index.html.twig', [
            'title' => 'Picture manager'
        ]);
    }

    /**
     * @param string $path
     *
     * @return Response
     */
    public function folder($path = '/')
    {
        $folder = $this->folderManager->findOneFolderByPath($path);
        if (!$folder) {
            throw $this->createNotFoundException();
        }

        $pictures = $this->folderManager->listMediaFiles($folder);
        $tags = $this->tagRepository->findByGlobal(1);

        return $this->render('@WebelopAlbum/admin/folder.html.twig', [
            'folder' => $folder,
            'pictures' => $pictures,
            'tags' => $tags
        ]);
    }

    /**
     * @param string|null $path
     * @param string      $type
     *
     * @return Response
     */
    public function sidebar(string $path = null, string $type = 'folder')
    {
        $tags = $folders = [];
        if ('tag' === $type) {
            $tags = $this->getDoctrine()->getRepository(Tag::class)
                ->findBy([], ['global' => 'DESC', 'id' => 'DESC']);
        } else {
            $folders = $this->folderManager->listFolders($path);
        }

        return $this->render('@WebelopAlbum/admin/_sidebar.html.twig', [
            'folders' => $folders,
            'tags' => $tags,
        ]);
    }

    /**
     * @return Response
     */
    public function tagList()
    {
        return $this->render('@WebelopAlbum/admin/tag_list.html.twig', [
            'tags' => $this->tagRepository->findBy([], ['global' => 'DESC', 'id' => 'DESC'])
        ]);
    }

    /**
     * @param Request  $request
     * @param int|null $id
     *
     * @return RedirectResponse|Response
     */
    public function tagEdit(Request $request, int $id = null)
    {
        $em = $this->getDoctrine()->getManager();

        if ($id > 0) {
            $tag = $this->tagRepository->find($id);
            if (empty($tag)) {
                throw $this->createNotFoundException('Tag not found');
            }

            $pictures = $this->pictureRepository->findByTag($tag, array('originalDate', 'ASC'));
        } else {
            $tag = new Tag();
            $tag->setHash(substr(uniqid(), 0, 10));
            $pictures = array();
        }

        $form = $this->createForm(Form\TagType::class, $tag);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($tag);
            $em->flush();

            return $this->redirect($this->generateUrl('webelop_album_admin_tag_edit', array('id' => $tag->getId())));
        }

        $tags = $this->tagRepository->findByGlobal(true);
        if (!$tag->getGlobal()) {
            $tags[] = $tag;
        }

        return $this->render('@WebelopAlbum/admin/tag_edit.html.twig', [
            'tag' => $tag,
            'pictures' => $pictures,
            'tags' => $tags,
            'form' => $form->createView()
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function taggedPictures(Request $request)
    {
        $pictures = $this->pictureRepository->findTagged(
            $request->query->get('limit', 200),
            $request->query->get('offset', 200),
            $request->query->get('random', true)
        );

        $tags = $this->tagRepository->findByGlobal(true);

        return $this->render('@WebelopAlbum/admin/folder.html.twig', [
            'pictures' => $pictures,
            'tags' => $tags,
            'title' => 'All tagged pictures',
            'autoPlaySpeed' => 30000,
        ]);
    }

    /**
     * @param string $tag
     * @param string $pic
     * @param bool   $state
     *
     * @return Response
     */
    public function tagPicture(string $tag, string $pic, bool $state)
    {
        $em = $this->getDoctrine()->getManager();
        $tagObject = $this->tagRepository->findOneBy(array('hash' => $tag));
        $picture = $this->pictureRepository->findOneBy(array('hash' => $pic));

        if ($tagObject && $picture) {
            if ($state && !in_array($tag, $picture->getTagHashes())) {
                $tagObject->getPictures()->add($picture);
            } else if (!$state && in_array($tag, $picture->getTagHashes())) {
                $tagObject->getPictures()->removeElement($picture);
            }

            $em->flush();

            return new Response($state ? '1' : '0');
        }

        throw $this->createNotFoundException("Could not find tag [$tag] or picture [$pic]");
    }

    /**
     * @Route("/pic/{hash}/remove", name = "admin_picture_remove")
     *
     * todo: Adds "removed" to model, hide from web views
     * todo: Make view for removed photos and provide command for source file deletion with confirmation
     */
    public function removePicture(string $hash)
    {
        return new \Symfony\Component\HttpFoundation\Response('OK');
    }

}
