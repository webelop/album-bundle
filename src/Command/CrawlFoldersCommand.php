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


class CrawlFoldersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('album:crawl_folders')
            ->setDescription('Create resized pictures')
            ->addArgument('root', InputArgument::OPTIONAL, 'A given folder to dive into')
            ->addOption('depth', 'd', InputOption::VALUE_OPTIONAL, 'Depth of folder discovery')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $root = $input->getArgument('root') ?: $container->getParameter('album_root');
        $depth = $input->getOption('depth') ?: $container->getParameter('album_depth');

        $this->crawlFolders($root, $depth);
    }

    /*
     * List modified pictures within last *timeout* minutes and adds them to db
     */
    private function crawlFolders($root, $depth)
    {
        //Get all it's images and create a hashmap for matching (img > img path)
        $container = $this->getContainer();
        $albumRoot = $container->getParameter('album_root');
        /**
         * @var $folderService \Webelop\AlbumBundle\Service\Folder
         */
        $folderService = $container->get('jcc_album.folder_service');

        $paths = array();
        $cmd = sprintf('find %s -maxdepth "%d" -type d', escapeshellarg($root), $depth);

        exec($cmd, $paths);

        foreach ($paths as $path) {
            $folderPath = trim(str_replace($albumRoot, '', $path), '/');
            $folder = $folderService->findOneFolderByPath($folderPath);
            if (!$folder) {
               continue;
            }

            $folderService->listMediaFiles($folder);
        }
    }
}
