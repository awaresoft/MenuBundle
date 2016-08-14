<?php

namespace Awaresoft\MenuBundle\Menu;

use Awaresoft\Sonata\PageBundle\Entity\Page;
use Awaresoft\Sonata\PageBundle\Entity\PageRepository;
use Awaresoft\Sonata\PageBundle\Entity\Site;
use Doctrine\ORM\EntityManager;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Awaresoft\MenuBundle\Exception\MenuException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PageBuilder
 * Helps build pages menu from Page entity
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class PageBuilder extends ContainerAware
{

    /**
     * @var Page
     */
    protected $menuItems;

    /**
     * @var MenuItem
     */
    protected $menu;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var Page
     */
    protected $homepage;

    /**
     * Generate page aside menu
     *
     * @param FactoryInterface $factory
     * @param array $options
     * @return ItemInterface|MenuItem
     * @throws MenuException
     */
    public function cmsMenu(FactoryInterface $factory, array $options)
    {
        $this->em = $this->getEntityManager();
        $this->pageRepository = $this->getPageRepository();
        $page = $this->getPage($options);

        if (!$page) {
            throw new MenuException('Selected page is not exists');
        }

        $this->menu = $factory->createItem('root');
        $this->homepage = $this->findHomepage($page->getSite());

        $parents = $this->findParents($page);

        if (count($parents) === 0 && $page->getParent() === $this->homepage) {
            $parents = array($page);
        }

        $parents = $this->prepareParents($parents);
        $parentLeaf = $this->menu;

        foreach ($parents as $i => $parent) {
            $uri = true;

            if ($i === 0) {
                $uri = false;
            }

            $children = $this->findChildrenByPage($parent);
            $parentLeaf = $this->addChild($parentLeaf, $parent, $uri);

            foreach ($children as $child) {
                $childLeaf = $this->addChild($parentLeaf, $child);

                if ($child === $page) {
                    $pageChildren = $this->findChildrenByPage($page);

                    foreach ($pageChildren as $pageChild) {
                        $this->addChild($childLeaf, $pageChild);
                    }
                }
            }
        }

        $this->setCurrentItem($this->menu);

        return $this->menu;
    }

    protected function getPage($options)
    {
        if ($options && isset($options['url'])) {
            return $this->pageRepository->findOneByUrl($options['url']);
        }

        if ($options && isset($options['route'])) {
            return $this->pageRepository->findOneByRouteName($options['route']);
        }

        $page = $this->container->get('request')->attributes->get('page');
        $path = $this->container->get('request')->attributes->get('path');
        $route = $this->container->get('request')->attributes->get('_route');

        if (!$page instanceof Page && $path) {
            $page = $this->pageRepository->findOneByUrl($path);
        }

        if (!$page instanceof Page && $route) {
            $page = $this->pageRepository->findOneByRouteName($route);
        }

        return $page;
    }

    /**
     * Add child to menu object
     *
     * @param MenuItem $menu
     * @param Page $item
     * @param bool $uri
     * @return MenuItem
     */
    protected function addChild(MenuItem $menu, Page $item, $uri = true)
    {
        $baseUrl = $this->getRequest()->getBaseUrl();
        $uri = $uri ? $baseUrl . $item->getUrl() : false;

        $attributes = [];

        if ($item->getRedirectUrl()) {
            $uri = $item->getRedirectUrl();
            $attributes['target'] = '_blank';
        }

        $uri = $this->cleanDynamicRoute($uri, $item);

        return $menu->addChild($item->getName(), array(
            'uri' => $uri,
            'extras' => array(
                'object' => $item
            ),
            'linkAttributes' => $attributes
        ));
    }

    /**
     * @param Page[] $parents
     * @return Page[]
     */
    protected function prepareParents(array $parents)
    {
        $count = count($parents);
        $preparedParents = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $preparedParents[] = $parents[$i];
        }

        return $preparedParents;
    }

    /**
     * Find parents for selected page
     *
     * @param \Awaresoft\Sonata\PageBundle\Entity\Page $page
     * @param \Awaresoft\Sonata\PageBundle\Entity\Page[] $parents
     * @return \Awaresoft\Sonata\PageBundle\Entity\Page[]
     */
    protected function findParents(Page $page, array $parents = [])
    {
        if (!$page->getParent() || $page->getParent() === $this->homepage) {
            return $parents;
        }

        $parents[] = $page->getParent();

        return $this->findParents($page->getParent(), $parents);
    }

    /**
     * @param ItemInterface $menu
     */
    protected function setCurrentItem(ItemInterface $menu)
    {
        $menu->setCurrent($this->container->get('request')->getPathInfo());
    }

    /**
     * Clean dynamic route if page is dynamic
     *
     * @param $uri
     * @param Page $item
     * @return string
     */
    protected function cleanDynamicRoute($uri, Page $item)
    {
        if ($item->isDynamic()) {
            $params = explode('/', $uri);
            $deletedParams = [];

            foreach ($params as $key => $param) {
                if (strpos($param, '{') !== false) {
                    $deletedParams[] = $key;
                }
            }

            if (isset($deletedParams[0])) {
                $uri = '';

                foreach ($params as $key => $param) {
                    if (in_array($key, $deletedParams)) {
                        return $uri;
                    }
                    if ($key !== 0) {
                        $uri .= '/';
                    }

                    $uri .= $param;
                }
            }
        }

        return $uri;
    }

    /**
     * Return request from container
     *
     * @return Request
     */
    protected function getRequest()
    {
        return $this->container->get('request');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * @return PageRepository
     */
    protected function getPageRepository()
    {
        return $this->em->getRepository('AwaresoftSonataPageBundle:Page');
    }

    /**
     * @param Page $page
     * @return Page[]
     */
    protected function findChildrenByPage(Page $page)
    {
        return $this->pageRepository->findVisibleChildrenByPage($page, array('position' => 'ASC'));
    }

    /**
     * @param Site $site
     * @return \Awaresoft\Sonata\PageBundle\Entity\Page[]
     */
    protected function findHomepage(Site $site)
    {
        return $this->pageRepository->findHomepage($site);
    }
}