<?php

namespace Awaresoft\MenuBundle\Block\Service;

use Knp\Menu\Provider\MenuProviderInterface;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\MenuBlockService as BaseMenuBlockService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

/**
 * Class MenuBlockService
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class MenuBlock extends BaseMenuBlockService
{
    /**
     * Constructor
     *
     * @param string $name
     * @param EngineInterface $templating
     * @param MenuProviderInterface $menuProvider
     * @param array $menus
     * @param ContainerInterface $container
     */
    public function __construct($name, EngineInterface $templating, MenuProviderInterface $menuProvider, array $menus = [], ContainerInterface $container)
    {
        parent::__construct($name, $templating, $menuProvider, $menus);

        if (!$this->menus || count($this->menus) === 0) {
            $this->menus = $container->getParameter('awaresoft.menu')['menus'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $path = explode(':', $blockContext->getSetting('menu_name'));
        $preparedPath = $path[0] . ':Menu:' . Container::underscore($path[2]) . '.html.twig';
        $blockContext->setSetting('menu_template', $preparedPath);

        return parent::execute($blockContext, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'title' => $this->getName(),
            'cache_policy' => 'public',
            'template' => 'AwaresoftMenuBundle:Block:block_menu.html.twig',
            'menu_name' => "",
            'safe_labels' => false,
            'current_class' => 'active',
            'first_class' => 'first',
            'last_class' => 'last',
            'current_uri' => null,
            'menu_class' => "list-group",
            'children_class' => "list-group-item",
            'menu_template' => null,
            'route' => null,
            'url' => null,
            'extra' => null,
        ]);
    }

    /**
     * @return array
     */
    protected function getFormSettingsKeys()
    {
        return [
            ['menu_name', ChoiceType::class, ['choices' => $this->menus, 'required' => false]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Menu';
    }

    /**
     * Replaces setting keys with knp menu item options keys
     *
     * @param array $settings
     *
     * @return array
     */
    protected function getMenuSettings(array $settings)
    {
        $mapping = [
            'route' => 'route',
            'url' => 'url',
            'extra' => 'extra',
        ];

        $options = [];

        foreach ($settings as $key => $value) {
            if (array_key_exists($key, $mapping) && null !== $value) {
                $options[$mapping[$key]] = $value;
            }
        }

        return $options;
    }

}