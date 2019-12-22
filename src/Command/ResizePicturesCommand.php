<?php
namespace Webelop\AlbumBundle\Command;

use DateTime;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Webelop\AlbumBundle\Entity\Picture;
use Webelop\AlbumBundle\Entity\Folder;


class ResizePicturesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('album:resize')
            ->setDescription('Create resized pictures')
            ->addArgument('timeout', InputArgument::OPTIONAL, 'How long should the command run?  (minutes)', '3')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timeout = $input->getArgument('timeout');

        //Check for modified pictures in the folder tree
        $this->checkModifiedPictures($timeout);

        //Loop through pictures to resize
        $stop = time() + $timeout * 60;
        while (time() < $stop) {
            try {
                $resized = $this->resizeOnePicture();
            } catch (\Exception $e) {
                $resized = true;
                $output->writeln($e->getMessage());
            }

            if (!$resized) {
                $output->writeln('Not resized any pictures');
                sleep(5);
            }
        }
    }

    /*
     * List modified pictures within last *timeout* minutes and adds them to db
     */
    private function checkModifiedPictures($timeout)
    {
        //Get all it's images and create a hashmap for matching (img > img path)
        $container = $this->getContainer();
        $root = $container->getParameter('album_root');

        $paths = array();
        $cmd = sprintf('find %s -iname "*.jpg" -mmin "-%d"', escapeshellarg($root), $timeout);

        exec($cmd, $paths);

        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $folderRepo = $doctrine->getRepository('AlbumBundle:Folder');
        $pictureRepo = $doctrine->getRepository('AlbumBundle:Picture');

        $folders = array();


        foreach ($paths as $path) {
            //Check folder
            $folderPath = trim(str_replace('root', '', dirname($path)), '/');
            $folder = null;

            if (isset($folders[$folderPath])) {
               $folder = $folders[$folderPath];
            } else {
                $folder = $folderRepo->findOneByPath(dirname($folderPath));
            }

            if (!$folder) {
                $class = $folderRepo->getClassName();
                $folder = new $class;
                $folder->setName(basename(dirname($path)));
                $folder->setPath(trim(str_replace($root, '', dirname($path)), '/'));

                $em->persist($folder);
            }

            $folders[$folderPath] = $folder;

            //Check image existence
            $picture = $pictureRepo->findOneBy(array('folder' => $folder,'path' => dirname($path)));

            if (!$picture) {
                $picture = new Picture();
                $picture->setPath(basename($path));
                $picture->setHash(substr(md5($path), 0, 12));
                $picture->setFolder($folder);

                $exif = @exif_read_data($path);
                if (!empty($exif['FileDateTime'])) {
                    $picture->setOriginalDate(new DateTime("@" . $exif['FileDateTime']));
                }

                $em->persist($picture);
            }
        }

        $em->flush();
    }

    private function resizeOnePicture()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $pictureRepo = $doctrine->getRepository('AlbumBundle:Picture');

        $picture = $pictureRepo->findOneByResized(0);
        if ($picture) {
            $picture->setResized(true);
            $doctrine->getManager()->flush();

            $sizes = array_filter(explode('|', $this->getContainer()->getParameter('album_sizes')));
            foreach ($sizes as $size) {
                $url = str_replace('{hash}', $picture->getHash(), $size);
                if ($url == $size) {
                    throw new \RuntimeException('URLs should be different than template!!!');
                }

                file_get_contents($url);
            }

            return true;
        }

        return false;
    }
}
