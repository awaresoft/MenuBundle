<?php

namespace Awaresoft\MenuBundle\Menu;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Awaresoft\MenuBundle\Entity\Menu;
use Awaresoft\MenuBundle\Entity\MenuRepository;
use Awaresoft\MenuBundle\Exception\MenuException;
use Sonata\PageBundle\Site\SiteSelectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Builder
 * Helps build menu from entity tree
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class Builder
{
    /**
     * @var MenuItem
     */
    protected $menu;

    /**
     * @var Menu
     */
    protected $root;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var SiteSelectorInterface
     */
    protected $siteSelector;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var MenuRepository|EntityRepository
     */
    protected $menuRepository;

    /**
     * Builder constructor.
     *
     * @param FactoryInterface $factory
     * @param SiteSelectorInterface $siteSelector
     * @param EntityManager $em
     * @param string $menuRepository
     */
    public function __construct(FactoryInterface $factory, SiteSelectorInterface $siteSelector, EntityManager $em, string $menuRepository) {
        $this->factory = $factory;
        $this->siteSelector = $siteSelector;
        $this->em = $em;
        $this->menuRepository = $this->em->getRepository($menuRepository);
    }

    /**
     * Generate menu with options (attr position determine position of menu)
     *
     * @param RequestStack $requestStack
     * @param array $options
     *
     * @return ItemInterface|MenuItem
     *
     * @throws MenuException
     */
    public function menu(RequestStack $requestStack, array $options)
    {
        /**
         * @var $menuItems Menu[]
         */

        if (!isset($options['position'])) {
            throw new MenuException('Parameter position is empty');
        }

        $site = $this->siteSelector->retrieve();

        $this->request = $requestStack->getCurrentRequest();
        $this->menu = $this->factory->createItem('root');
        $this->root = $this->menuRepository->findOneBy(['name' => $options['position'], 'site' => $site]);
        $this->menu->setExtra('object', $this->root);

        if (isset($options['attributes'])) {
            $this->menu->setChildrenAttributes($options['attributes']);
        }

        if (!$this->root) {
            return $this->menu;
        }

        if (!$this->root->isEnabled()) {
            return $this->menu;
        }

        $menuItems = $this->menuRepository->getChildren($this->root);
        $deletedParents = [];

        // remove disabled menu items
        foreach ($menuItems as $key => $item) {
            if (!$item->isEnabled() || ($item->getParent() && in_array($item->getParent()->getId(), $deletedParents))) {
                $deletedParents[] = $item->getId();
                unset($menuItems[$key]);
            }
        }

        // generate menu tree object
        foreach ($menuItems as $key => $item) {
            $this->menu = $this->setItem($item);
        }

        $this->setCurrentItem($this->menu);

        return $this->menu;
    }

    /**
     * Set new menu item to menu object
     *
     * @param Menu $item
     * @return MenuItem
     */
    protected function setItem($item)
    {
        if ($item->getLevel() === 0) {
            return $this->menu;
        }

        if ($item->getLevel() === 1) {
            if (count($item->getChildren()) > 0) {
                $this->addChild($this->menu, $item, true);
            } else {
                $this->addChild($this->menu, $item);
            }
        } else {
            if ($item->getParent() !== null) {
                $this->setItemWithParents($item);
            } else {
                $this->addChild($this->menu, $item);
            }
        }

        return $this->menu;
    }

    /**
     * Add child to menu object
     *
     * @param ItemInterface $menu
     * @param Menu $item
     * @param bool $child
     *
     * @return ItemInterface
     */
    protected function addChild(ItemInterface $menu, $item, $child = false)
    {
        $baseUrl = $this->request->getBaseUrl();
        $baseUrl = sprintf(
            '%s%s',
            $baseUrl,
            $this->siteSelector->getRequestContext()->getBaseUrl()
        );

        $uri = null;
        $attributes = [];

        if ($item->getUrl()) {
            $uri = $baseUrl . $item->getUrl();
        }

        if ($this->isExternalUrl($item->getUrl())) {
            $uri = $item->getUrl();
            $attributes['target'] = '_blank';
            $attributes['rel'] = 'nofollow';
        }

        $addedChild = $menu->addChild($item->getName(), [
            'uri' => $uri,
            'extras' => [
                'object' => $item,
            ],
            'linkAttributes' => $attributes,
        ]);

        if ($child === true) {
            $addedChild->setAttribute('dropdown', true);
        }

        if ($item->getClass()) {
            $addedChild->setAttribute('class', $item->getClass());
        }

        return $addedChild;
    }

    /**
     * Check if url starts with http
     *
     * @param $url
     * @return bool
     */
    protected function isExternalUrl($url)
    {
        if (strpos(strtolower($url), 'http') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Find children of MenuItem by Menu (parent)
     *
     * @param Menu $item
     * @param array $children
     * @param int $i
     * @return MenuItem[]
     */
    protected function findChildrenByItem($item, $children = [], $i = 0)
    {
        $parent = $item->getParent();

        if ($parent->getParent()) {
            $children[] = $parent;

            return $this->findChildrenByItem($parent, $children, ($i + 1));
        } else {
            if ($parent->getId() != $item->getRoot()) {
                $children[] = $parent;
            }
        }

        return $children;
    }

    /**
     * Set MenuItem for Menu object with parents
     *
     * @param Menu $item
     */
    protected function setItemWithParents($item)
    {
        $children = $this->findChildrenByItem($item);
        $count = count($children);
        $menu = $this->menu;

        for ($i = $count - 1; $i >= 0; $i--) {
            $menu = $menu->getChild($children[$i]->getName());
        }

        if (count($item->getChildren()) > 0) {
            $this->addChild($menu, $item, true);
        } else {
            $this->addChild($menu, $item);
        }
    }

    /**
     * @param ItemInterface $menu
     */
    protected function setCurrentItem(ItemInterface $menu)
    {
        $menu->setCurrent($this->request->getPathInfo());
    }
}
