<?php

namespace Webelop\AlbumBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Webelop\AlbumBundle\Entity\{Album, Folder, Picture, Tag};
use Webelop\AlbumBundle\Form;
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

    public function __construct(FolderManager $folderManager)
    {
        $this->folderManager = $folderManager;
    }

    /**
     * @Route("/", name = "admin_index")
     * @Template()
     * Based on a root path, show subfolders as actual folders (image containers)
     */
    public function index()
    {
        return $this->render('@Album/admin/index.html.twig', [
            'title' => 'Picture manager'
        ]);
    }

    /**
     * @Route("/folder/{path}", name = "admin_folder", requirements={"path" = ".*"})
     */
    public function folder(FolderManager $folderManager, $path = '/')
    {
        $tagRepository = $this->getDoctrine()->getRepository(Tag::class);
        $folder = $folderManager->findOneFolderByPath($path);
        if (!$folder) {
            throw $this->createNotFoundException();
        }

        $pictures = $folderManager->listMediaFiles($folder);
        $tags = $tagRepository->findByGlobal(1);

        return $this->render('@Album/admin/folder.html.twig', [
            'folder' => $folder,
            'pictures' => $pictures,
            'tags' => $tags
        ]);
    }

    /**
     * @Route("/sidebar", name = "admin_sidebar", requirements={"path" = ".*"})
     */
    public function sidebar(FolderManager $folderManager, string $path = null, string $type = 'folder')
    {
        $tags = $folders = [];
        if ('tag' === $type) {
            $tags = $this->getDoctrine()->getRepository(Tag::class)
                ->findBy([], ['global' => 'DESC', 'id' => 'DESC']);
        } else {
            $folders = $folderManager->listFolders($path);
        }

        return $this->render('@Album/admin/_sidebar.html.twig', [
            'folders' => $folders,
            'tags' => $tags,
        ]);
    }

    /**
     * @Route("/tags", name = "admin_tags")
     *
     * @return array
     */
    public function tagList()
    {
        $tagRepository = $this->getDoctrine()->getRepository('AlbumBundle:Tag');

        return $this->render('@Album/admin/tag_list.html.twig', [
            'tags' => $tagRepository->findBy([], ['global' => 'DESC', 'id' => 'DESC'])
        ]);
    }

    /**
     * @Route("/tag/{id}", name = "admin_tag_edit", defaults = {"id": ""})
     *
     * @return array
     */
    public function tagEdit(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $tagRepository = $this->getDoctrine()->getRepository(Tag::class);

        if ($id > 0) {
            $picRepo = $this->getDoctrine()->getRepository(Picture::class);
            $tag = $tagRepository->find($id);
            if (empty($tag)) {
                throw $this->createNotFoundException('Tag not found');
            }

            $pictures = $picRepo->findByTag($tag, array('originalDate', 'ASC'));
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

            return $this->redirect($this->generateUrl('admin_tag_edit', array('id' => $tag->getId())));
        }

        $tags = $tagRepository->findByGlobal(true);
        if (!$tag->getGlobal()) {
            $tags[] = $tag;
        }

        return $this->render('@Album/admin/tag_edit.html.twig', [
            'tag' => $tag,
            'pictures' => $pictures,
            'tags' => $tags,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/tagged", name = "admin_tagged_pictures")
     */
    public function taggedPictures(Request $request)
    {
        $tagRepository = $this->getDoctrine()->getRepository(Tag::class);
        $picRepo = $this->getDoctrine()->getRepository(Picture::class);

        $pictures = $picRepo->findTagged(
            $request->query->get('limit', 200),
            $request->query->get('offset', 200),
            $request->query->get('random', true)
        );

        $tags = $tagRepository->findByGlobal(true);

        return $this->render('@Album/admin/folder.html.twig', [
            'pictures' => $pictures,
            'tags' => $tags,
            'title' => 'All tagged pictures',
            'autoPlaySpeed' => 30000,
        ]);
    }

    /**
     * @Route("/tag/{tag}/picture/{pic}/state/{state}", name = "admin_tag_picture")
     * Add a tag to a picture
     */
    public function tagPicture($tag, $pic, $state)
    {
        $em = $this->getDoctrine()->getManager();
        $tagRepository = $this->getDoctrine()->getRepository('AlbumBundle:Tag');
        $pictureRepository = $this->getDoctrine()->getRepository('AlbumBundle:Picture');
        $tagObject = $tagRepository->findOneBy(array('hash' => $tag));
        $picture = $pictureRepository->findOneBy(array('hash' => $pic));

        if ($tagObject && $picture) {
            if ($state && !in_array($tag, $picture->getTagHashes())) {
                $tagObject->getPictures()->add($picture);
            } else if (!$state && in_array($tag, $picture->getTagHashes())) {
                $tagObject->getPictures()->removeElement($picture);
            }

            $em->flush();

            return new Response($state);
        }

        throw $this->createNotFoundException("Could not find tag [$tag] or picture [$pic]");
    }

    /**
     * @Route("/pic/{id}/remove", name = "admin_picture_remove")
     *
     * todo: Adds "removed" to model, hide from web views
     * todo: Make view for removed photos and provide command for source file deletion with confirmation
     */
    public function removePicture($id)
    {
        return new \Symfony\Component\HttpFoundation\Response('OK');
    }

}
