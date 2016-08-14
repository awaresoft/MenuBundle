<?php

namespace Awaresoft\MenuBundle\Entity;

use Awaresoft\Sonata\PageBundle\Entity\Page;
use Awaresoft\Sonata\PageBundle\Entity\Site;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Awaresoft\TreeBundle\Entity\AbstractTreeNode;
use Sonata\PageBundle\Model\PageInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass()
 * @Gedmo\Tree(type="nested")
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class Menu extends AbstractTreeNode
{
    const TREE_MAIN_COLUMN = 'name';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", name="tree_left")
     *
     * @Gedmo\TreeLeft
     *
     * @var int
     */
    protected $left;

    /**
     * @ORM\Column(type="integer", name="tree_right")
     *
     * @Gedmo\TreeRight
     *
     * @var int
     */
    protected $right;

    /**
     * @ORM\ManyToOne(targetEntity="Menu", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * @Gedmo\TreeParent
     *
     * @var Menu
     */
    protected $parent;

    /**
     * @ORM\Column(type="integer", nullable=true, name="tree_root")
     *
     * @Gedmo\TreeRoot
     *
     * @var Menu
     */
    protected $root;

    /**
     * @ORM\Column(name="tree_level", type="integer")
     *
     * @Gedmo\TreeLevel
     *
     * @var int
     */
    protected $level;

    /**
     * @ORM\OneToMany(targetEntity="Menu", mappedBy="parent")
     * @ORM\OrderBy({"left" = "ASC"})
     *
     * @var Menu[]
     */
    protected $children;

    /**
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    protected $externalUrl;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Awaresoft\Sonata\PageBundle\Entity\Site")
     *
     * @var Site
     */
    protected $site;

    /**
     * @ORM\ManyToOne(targetEntity="Awaresoft\Sonata\PageBundle\Entity\Page")
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="SET NULL")
     *
     * @var PageInterface
     */
    protected $page;

    /**
     * @var string
     */
    protected $url;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     *
     * @var string
     */
    protected $header;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $class;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $template;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var boolean
     */
    protected $enabled;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $deletable;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Gedmo\Timestampable(on="create")
     *
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Gedmo\Timestampable(on="update")
     *
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * Menu constructor.
     */
    public function __construct()
    {
        $this->enabled = true;
        $this->deletable = true;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Menu
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param $parent
     *
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return $this->parent ? true : false;
    }

    /**
     * @return mixed
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param mixed $left
     *
     * @return $this
     */
    public function setLeft($left)
    {
        $this->left = $left;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @param mixed $right
     *
     * @return $this
     */
    public function setRight($right)
    {
        $this->right = $right;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param mixed $root
     *
     * @return $this
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param mixed $children
     *
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if ($this->page) {
            return $this->page->getUrl();
        }

        return $this->externalUrl;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param Page $page
     *
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return string
     */
    public function getExternalUrl()
    {
        return $this->externalUrl;
    }

    /**
     * @param string $externalUrl
     *
     * @return $this
     */
    public function setExternalUrl($externalUrl)
    {
        $this->externalUrl = $externalUrl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param mixed $level
     *
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param mixed $enabled
     * @return void
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeletable()
    {
        return $this->deletable;
    }

    /**
     * @param bool $deletable
     *
     * @return $this
     */
    public function setDeletable($deletable)
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param string $header
     *
     * @return $this
     */
    public function setHeader($header)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     *
     * @return Menu
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     *
     * @return Menu
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getTitleFieldName()
    {
        return self::TREE_MAIN_COLUMN;
    }

    /**
     * Check if page and externalUrl are set
     */
    public function prepareUrl()
    {
        if ($this->page && $this->externalUrl) {
            $this->externalUrl = null;
        }
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param Site $site
     *
     * @return Menu
     */
    public function setSite($site)
    {
        $this->site = $site;

        return $this;
    }
}