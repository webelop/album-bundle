<?php

namespace Webelop\AlbumBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Album
 *
 * @ORM\Table(name="tag")
 * @ORM\Entity
 */
class Tag
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var $hash
     *
     * @ORM\Column(name="hash", type="string", length=255)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="cover", type="string", length=255, nullable=true)
     */
    private $cover;

    /**
     * @var string
     *
     * @ORM\Column(name="class", type="string", length=255)
     */
    private $class;

    /**
     * @var boolean
     *
     * @ORM\Column(name="global", type="boolean")
     */
    private $global;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sort", type="text")
     */
    private $sort = 'ASC';

    /**
     * @ORM\ManyToMany(targetEntity="Picture", inversedBy="tags")
     * @ORM\JoinTable(name="tag_picture")
     */
    protected $pictures;


    public function __construct()
    {
        $this->hash = substr(md5(uniqid()), 0, 9);
        $this->pictures = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Album
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Album
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return preg_replace('/\s+/', '-', strtolower($this->slug));
    }

    /**
     * Set cover
     *
     * @param string $cover
     * @return Album
     */
    public function setCover($cover)
    {
        $this->cover = $cover;

        return $this;
    }

    /**
     * Get cover
     *
     * @return string
     */
    public function getCover()
    {
        return $this->cover;
    }

    /**
     *
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     *
     * @param string $sort
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Computes a hash for the current album
     *
     * @param type $tag
     */
    public function generateHash()
    {
        $hash = substr(sha1('some hash key/' . $this->getSlug() .'/'. $tag), 0, 6);

        return $hash;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;
    }


    public function getPictures()
    {
        return $this->pictures;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setClass($class)
    {
        $this->class = $class;
    }

    public function getGlobal()
    {
        return $this->global;
    }

    public function setGlobal($global)
    {
        $this->global = $global;
    }

}
