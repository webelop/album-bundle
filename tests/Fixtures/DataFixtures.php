<?php

namespace Webelop\AlbumBundle\Tests\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Webelop\AlbumBundle\Entity\Folder;
use Webelop\AlbumBundle\Entity\Picture;
use Webelop\AlbumBundle\Entity\Tag;
use Webelop\AlbumBundle\Tests\Fixtures\App\Entity\User;

class DataFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $this->persistEntities($manager, $this->createUsers());
        $this->persistEntities($manager, $this->createTags());
        $this->persistEntities($manager, [$folder = $this->createFolder('TestA')]);
        $this->persistEntities($manager, $this->createPictures($folder));

        $manager->flush();
    }

    private function createUsers(): array
    {
        $users = [];

        $users[] = $user = new User();
        $user->setEmail('admin@test.com');
        $user->setPassword('pa$$word');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        $users[] = $user = new User();
        $user->setEmail("user@test.com");
        $user->setPassword("password");
        $user->setRoles(['ROLE_USER']);

        return  $users;
    }

    private function createTags(): array
    {
        $tags = [];
        for ($i = 0; $i < 10; $i++) {
            $tags[] = $tag = new Tag();
            $tag->setGlobal((bool) $i % 2);
            $tag->setHash('hash' . $i);
            $tag->setSlug('slug' . $i);
            $tag->setName('Tag ' . $i);
            $tag->setClass('plane');
        }

        return $tags;
    }

    /**
     * @param Folder[] $folders
     *
     * @return Picture[]
     */
    private function createPictures(Folder $folder): array
    {
        $pictures = [];
        for ($i = 0; $i < 3; $i++) {
            $pictures[] = $picture = new Picture();
            $picture->setHash("pic${i}");
            $picture->setFolder($folder);
            $picture->setOriginalDate(new \DateTime('2020-02-02 00:02:20'));
            $picture->setPath("pic${i}.jpg");
        }

        return $pictures;
    }

    /**
     * @param ObjectManager $manager
     * @param array         $tags
     */
    private function persistEntities(ObjectManager $manager, array $tags): void
    {
        foreach ($tags as $tag) {
            $manager->persist($tag);
        }
    }

    private function createFolder(string $name): Folder
    {
        $folder = new Folder();
        $folder->setName($name);
        $folder->setPath($name);

        return $folder;
    }

}