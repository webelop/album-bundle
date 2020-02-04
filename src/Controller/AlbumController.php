<?php

declare(strict_types = 1);

namespace Webelop\AlbumBundle\Controller;

use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Webelop\AlbumBundle\Repository\PictureRepository;
use Webelop\AlbumBundle\Repository\TagRepository;

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
    /** @var TagRepository */
    private $tagRepository;

    /** @var PictureRepository */
    private $pictureRepository;

    /**
     * @param TagRepository     $tagRepository
     * @param PictureRepository $pictureRepository
     */
    public function __construct(TagRepository $tagRepository, PictureRepository $pictureRepository)
    {
        $this->tagRepository = $tagRepository;
        $this->pictureRepository = $pictureRepository;
    }

    /**
     * @return Response
     */
    public function index()
    {
        // Redirect to manager if the user is an authenticated admin
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('webelop_album_admin_index');
        }

        return $this->render('@WebelopAlbum/album/index.html.twig');
    }

    /**
     * @param string $hash
     * @param string $slug
     *
     * @return Response
     */
    public function view(string $hash, string $slug)
    {
        $tag = $this->tagRepository->findOneByHash($hash);

        if (empty($tag)) {
            throw $this->createNotFoundException('Tag not found');
        }

        $pictures = $this->pictureRepository->findByTag($tag);

        return $this->render('@WebelopAlbum/album/view.html.twig', [
            'tag' => $tag,
            'pictures' => $pictures
        ]);
    }

}
