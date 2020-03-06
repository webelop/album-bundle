<?php

namespace Webelop\AlbumBundle\Entity;

use DateTime;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Picture
 *
 * @ORM\Entity(repositoryClass="Webelop\AlbumBundle\Repository\PictureRepository")
 * @ORM\Table(name="picture")
 */
class Picture
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Folder
     *
     * @ORM\ManyToOne(targetEntity="Folder", inversedBy="pictures")
     * @ORM\JoinColumn(name="folder_id", referencedColumnName="id")
     */
    private $folder;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=255)
     */
    private $hash;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="original_date", type="datetime", nullable=true)
     */
    private $originalDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="resized", type="boolean", nullable=false)
     */
    private $resized = false;


    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="Tag", mappedBy="pictures")
     */
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set folder
     *
     * @param Folder $folder
     *
     * @return Picture
     */
    public function setFolder(Folder $folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Get folder
     *
     * @return Folder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Picture
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set hash
     *
     * @param string $hash
     * @return Picture
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     *
     * @return DateTime
     */
    public function getOriginalDate()
    {
        return $this->originalDate;
    }

    /**
     * @param DateTime $originalDate
     * @return Picture
     */
    public function setOriginalDate(DateTime $originalDate)
    {
        $this->originalDate = $originalDate;

        return $this;
    }
    /**
     *
     * @return boolean
     */
    public function isResized()
    {
        return $this->resized;
    }

    /**
     * @param boolean $resized
     * @return Picture
     */
    public function setResized($resized)
    {
        $this->resized = $resized;

        return $this;
    }

    /**
     * Check if this picture is a stream (MOV, MP4)
     * @return boolean
     */
    public function isStream()
    {
        return in_array(strtolower(pathinfo($this->path, PATHINFO_EXTENSION)), array('mov', 'mp4', 'mkv', 'avi'));
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    public function addTag(Tag $tag) {
        $this->tags[] = $tag;
    }

    public function removeTag(Tag $tag) {
        $this->tags = array_filter($this->tags ?: array(), function($tag){
            return $tag->getHash() == $tag->getHash();
        });
    }

    public function getTagHashes()
    {
        $result = array();
        foreach($this->tags ?: array() as $tag) {
            $result[] = $tag->getHash();
        }

        return $result;
    }
}
