<?php

namespace Awaresoft\MenuBundle\Menu;

use Doctrine\ORM\EntityManager;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Awaresoft\MenuBundle\Entity\Menu;
use Awaresoft\MenuBundle\Entity\MenuRepository;
use Awaresoft\MenuBundle\Exception\MenuException;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Class Builder
 * Helps build menu from entity tree
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class Builder extends ContainerAware
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
     * @var MenuRepository
     */
    protected $menuRepository;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Generate menu with options (attr position determine position of menu)
     *
     * @param FactoryInterface $factory
     * @param array $options
     * @return ItemInterface|MenuItem
     * @throws MenuException
     */
    public function menu(FactoryInterface $factory, array $options)
    {
        $site = $this->container->get('sonata.page.site.selector')->retrieve();

        if (!isset($options['position'])) {
            throw new MenuException('Parameter position is empty');
        }

        $this->menu = $factory->createItem('root');
        $this->root = $this->getMenuRepository()->findOneBy(['name' => $options['position'], 'site' => $site]);
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

        $menuItems = $this->getMenuRepository()->getChildren($this->root);
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
     * @param MenuItem $menu
     * @param Menu $item
     * @return MenuItem
     */
    protected function addChild($menu, $item, $child = false)
    {
        $baseUrl = $this->getRequest()->getBaseUrl();
        $baseUrl = $baseUrl . $this->container->get('sonata.page.site.selector')->getRequestContext()->getBaseUrl();

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
        $menu->setCurrent($this->container->get('request')->getPathInfo());
    }

    /**
     * Return request from container
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * @return MenuRepository
     */
    protected function getMenuRepository()
    {
        if ($this->menuRepository) {
            return $this->menuRepository;
        }

        if (!$this->em) {
            $this->em = $this->getEntityManager();
        }

        $this->menuRepository = $this->em->getRepository('ApplicationMenuBundle:Menu');

        return $this->menuRepository;
    }
}