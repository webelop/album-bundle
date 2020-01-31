<?php

namespace Webelop\AlbumBundle\Service;

use UnexpectedValueException;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\Response;
use Webelop\AlbumBundle\Entity\Picture;
use Webelop\AlbumBundle\Repository\PictureRepository;

/**
 * Handle picture resizing and caching
 */
class PictureManager
{
    /** @var PictureRepository */
    private $pictureRepository;

    /** @var array */
    private $parameters = [];

    /**
     * @param array             $parameters
     * @param PictureRepository $pictureRepository
     */
    public function __construct(
        array $parameters,
        PictureRepository $pictureRepository
    )
    {
        $this->pictureRepository = $pictureRepository;
        $this->parameters = $parameters;
    }

    /**
     * - Looks up the Picture by hash
     * - Checks for existance of a preview in the root file system
     * - Then tries to create the preview with "epeg" and "convert"
     * - Falls back to tryimg "intervention/image" and "imagemagick" if they are available
     *
     * @param string $mode
     * @param int    $width
     * @param int    $height
     * @param string $hash
     * @param string $targetRelativePath
     * @param int    $quality
     *
     * @return string
     */
    public function generateResizedFile(
        string $mode,
        int $width,
        int $height,
        string $hash,
        string $targetRelativePath,
        int $quality = 80
    ): string
    {
        /** @var Picture $picture */
        $picture = $this->pictureRepository->findOneByHash($hash);
        if (!$picture) {
            throw new \UnexpectedValueException('Missing picture with hash ' . $hash, Response::HTTP_NOT_FOUND);
        }

        //Create a link
        $root = $this->parameters['album_root'];
        $cache = $this->parameters['cache_path'];
        $source = $root . '/' . $picture->getFolder()->getPath() . '/' . $picture->getPath();
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if (in_array($extension, array('mov', 'mp4'))) {
            # Preview has been generated by bin/photosync-module-webm
            $source = $root . '/' . $picture->getFolder()->getPath() . '/.preview/' . $picture->getPath() . '.jpg';
        }

        if (!file_exists($source)) {
            $this->pictureRepository->remove($picture);
            throw new \UnexpectedValueException('No such image at path ' . $source, Response::HTTP_NOT_FOUND);
        }

        $cachepath = $cache . '/' . $targetRelativePath;
        if (file_exists($cachepath) && filemtime($cachepath) > filemtime($source)) {
            // Give the resizer 3 seconds to work before exiting
            $sleep = 3.0;
            while (filesize($cachepath) === 0 && $sleep -= 1e5 > 0) {
                usleep(1e5);
            }
            return $cachepath;
        }

        // Prepare resize target directory
        $cachedir = dirname($cachepath);
        if (!is_dir($cachedir) && !mkdir($cachedir, 0777, true)) {
            throw new \UnexpectedValueException(sprintf(
                'Cache directory is not writable at path %s!',
                $cachedir
            ));
        }

        // Check whether a preview has been generated on the client
        $previewPath = implode(
            '/',
            [$root, $picture->getFolder()->getPath(), '.preview', $mode, $width, $height, $picture->getPath() . '.jpg']
        );
        if (file_exists($previewPath)) {
            symlink($previewPath, $cachepath);

            return $cachepath;
        } else {
            file_put_contents($cachepath . '.log', 'missing file at ' . $previewPath);
        }

        // Create the file to block concurrent resizes
        touch($cachepath);

        if ($this->parameters['execute_resize']) {
            return $cachepath;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'slideresize') . '.jpg';

        //TODO: Create 2 services (EpegConvertImageManager, ImagineImageManager) to handle the resizing
        // These services should extend an ImageManagerInterface and be configurable / replaceable

        #step 1: Resize with epeg first (much faster)
        $cmd [] = sprintf(
            'epeg -m %d -q %d --inset %s %s',
            max($height, $width), 90, $source, $tmpFile
        );
        if ($mode == "crop") {
            $finalFile = tempnam(sys_get_temp_dir(), 'slideresize') . '.jpg';
            $cmd [] = sprintf(
                'convert %s -quality %d -gravity center -crop %dx%d+0+0 +repage %s',
                escapeshellarg($tmpFile),
                $quality,
                $width,
                $height,
                escapeshellarg($finalFile)
            );
            $cmd[] = sprintf('rm %s', escapeshellarg($tmpFile));
            $cmd[] = sprintf('mv %s %s', escapeshellarg($finalFile), escapeshellarg($cachepath));
        } else {
            $cmd[] = sprintf('mv %s %s', escapeshellarg($tmpFile), escapeshellarg($cachepath));
        }

        exec(implode('&&', $cmd), $output, $error);

        if ($error &&
            class_exists('\Intervention\Image\ImageManager') &&
            class_exists('\Imagick')
        ) {
            // Fallback: use ImageMagick. Massively slower
            $manager = new ImageManager(['driver' => 'imagick']);
            $manager
                ->make($source)
                ->fit($width, $height)
                ->save($cachepath);
        }

        if (!file_exists($cachepath)) {
            throw new \UnexpectedValueException('The cached file was not found', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $cachepath;
    }

    /**
     * @param Picture $picture
     *
     * @return string
     */
    public function downloadFile(Picture $picture): string
    {
        $source = $this->parameters['album_root'] . '/' . $picture->getFolder()->getPath() . '/' . $picture->getPath();
        if (!file_exists($source)) {
            throw new UnexpectedValueException(sprintf('File %s does not exist!', $source), Response::HTTP_NOT_FOUND);
        }

        return $source;
    }

    /**
     * Creates a link from video preview to public folder and redirects to it
     *
     * @param Picture $picture
     * @param string  $extension
     *
     * @return string
     */
    public function prepareStream(Picture $picture, string $extension): string
    {
        $root = $this->parameters['album_root'];
        $source = $root . '/' . $picture->getFolder()->getPath() . '/.preview/' . $picture->getPath() . '.' . $extension;

        if (!file_exists($source)) {
            throw new UnexpectedValueException('Stream not found!', Response::HTTP_NOT_FOUND);
        }

        $cacheDir = $this->parameters['cache_path'];
        $cachePath = $cacheDir . '/stream/' . $picture->getHash() . '.' . $extension;

        if (!is_dir($cacheDir . '/stream/')) {
            mkdir($cacheDir . '/stream/');
        }

        if (!file_exists($cachePath) && !symlink($source, $cachePath)) {
            throw new UnexpectedValueException('Stream link could not be created!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $cachePath;
    }

}
