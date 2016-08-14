<?php

namespace Awaresoft\MenuBundle\Admin;

use Awaresoft\Sonata\PageBundle\Entity\PageRepository;
use Awaresoft\TreeBundle\Admin\AbstractTreeAdmin;
use Awaresoft\MenuBundle\Entity\Menu;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

/**
 * Class MenuAdmin
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class MenuAdmin extends AbstractTreeAdmin
{
    /**
     * @inheritdoc
     */
    protected $baseRoutePattern = 'awaresoft/menu/menu';

    /**
     * @inheritdoc
     */
    protected $multisite = true;

    /**
     * @inheritdoc
     */
    protected $titleField = 'name';

    /**
     * @inheritdoc
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        parent::configureRoutes($collection);
    }

    /**
     * @inheritdoc
     *
     * @param Menu $object
     */
    public function prePersist($object)
    {
        if ($object->getParent() && $object->getParent()->getSite() !== $object->getSite()) {
            $object->setSite($object->getParent()->getSite());
        }
    }

    /**
     * @inheritdoc
     *
     * @param Menu $object
     */
    public function preUpdate($object)
    {
        $this->prePersist($object);
    }

    /**
     * @inheritdoc
     *
     * @param Menu $object
     */
    public function postUpdate($object)
    {
        $object->prepareUrl();
    }

    /**
     * @inheritdoc
     *
     * @param Menu $object
     */
    public function postPersist($object)
    {
        $object->prepareUrl();
    }

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->with($this->trans('admin.admin.form.group.main'))
            ->add('name')
            ->add('site')
            ->add('text')
            ->add('textColor', null, [
                'label' => $this->trans('admin.admin.label.text_color'),
                'template' => 'SonataAdminBundle:CRUD:show_color.html.twig',
            ])
            ->add('parent')
            ->add('level')
            ->add('enabled')
            ->add('deletable')
            ->end();

        $showMapper
            ->with($this->trans('admin.admin.form.group.url'))
            ->add('page', null, [
                'admin_code' => 'awaresoft.page.admin.cms',
            ])
            ->add('url', 'url')
            ->add('externalUrl', 'boolean')
            ->end();

        $showMapper
            ->with($this->trans('admin.admin.form.group.optional'))
            ->add('header')
            ->add('class')
            ->add('template')
            ->end();
    }

    protected function configureListFieldsExtend(ListMapper $listMapper)
    {
        $listMapper
            ->add('site')
            ->add('url', 'url')
            ->add('externalUrl', 'boolean')
            ->add('enabled', null, ['editable' => true]);

        $editable = false;
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $editable = true;
        }

        $listMapper
            ->add('deletable', null, ['editable' => $editable]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $this->prepareFilterMultisite($datagridMapper);

        $datagridMapper
            ->add('name')
            ->add('parent')
            ->add('level')
            ->add('page', null, [], null, null, [
                'admin_code' => 'awaresoft.page.admin.cms',
            ])
            ->add('externalUrl')
            ->add('enabled')
            ->add('deletable');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        /**
         * @var Menu $object
         */
        $object = $this->getSubject();
        $maxDepthLevel = $this->prepareMaxDepthLevel('MENU');

        $formMapper
            ->with($this->trans('admin.admin.form.group.main'), ['class' => 'col-md-6'])->end()
            ->with($this->trans('admin.admin.form.group.url'), ['class' => 'col-md-6'])->end()
            ->with($this->trans('admin.admin.form.group.optional'), ['class' => 'col-md-6'])->end();

        $formMapper
            ->with($this->trans('admin.admin.form.group.main'))
            ->add('name')
            ->add('enabled', null, [
                'required' => false,
            ]);

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $formMapper
                ->add('deletable', null, [
                    'required' => false,
                ]);
        }

        if ($this->hasSubject() && !$object->getId()) {
            $formMapper
                ->add('site', null, ['required' => true, 'read_only' => true]);
        }

        $this->addParentField($formMapper, $maxDepthLevel, $object->getSite());

        $formMapper
            ->end();

        $formMapper
            ->with($this->trans('admin.admin.form.group.url'))
            ->add('page', 'entity', [
                'class' => 'AwaresoftSonataPageBundle:Page',
                'choice_label' => 'name',
                'label' => $this->trans('admin.admin.label.redirect_to_page'),
                'required' => false,
                'query_builder' => function (PageRepository $pr) use ($object) {
                    return $pr->findCmsPages($object->getSite());
                },
            ], [
                'admin_code' => 'awaresoft.page.admin.cms',
            ])
            ->add('externalUrl', 'text', [
                'required' => false,
            ])
            ->add('url', 'text', [
                'attr' => [
                    'readonly' => true,
                ],
                'required' => false,
            ])
            ->end();

        if ($object && !$object->getTemplate()) {
            $formMapper
                ->with($this->trans('admin.admin.form.group.optional'))
                ->add('header', 'textarea', [
                    'required' => false,
                ])
                ->end();
        }

        if ($this->isGranted('SUPER_ADMIN')) {
            $formMapper
                ->with($this->trans('admin.admin.form.group.optional'))
                ->add('class', null, [
                    'required' => false,
                ])
                ->add('template', null, [
                    'required' => false,
                ])
                ->end();
        }

        $formMapper->setHelps([
            'url' => $this->trans('admin.admin.help.url_page_or_plaintext'),
        ]);
    }
}
