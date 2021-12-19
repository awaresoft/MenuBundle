<?php

namespace Awaresoft\MenuBundle\DataFixtures\ORM;

use Awaresoft\MenuBundle\Entity\Menu;
use Awaresoft\DoctrineBundle\DataFixtures\AbstractFixture;
use Awaresoft\SettingBundle\Entity\Setting;
use Awaresoft\SettingBundle\Entity\SettingHasField;
use Doctrine\Persistence\ObjectManager;

/**
 * Class LoadMenuData
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class LoadMenuData extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public static function getGroups(): array
    {
        return ['prod', 'dev'];
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 12;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadMenus($manager);
        $this->loadSettings($manager);
    }

    protected function loadMenus(ObjectManager $manager)
    {
        $object = new Menu();
        $object->setName('main');
        $object->setEnabled(true);
        $object->setSite($this->getReference('page-site'));
        $manager->persist($object);

        $object = new Menu();
        $object->setName('footer');
        $object->setEnabled(true);
        $object->setSite($this->getReference('page-site'));
        $manager->persist($object);

        $manager->flush();
    }

    protected function loadSettings(ObjectManager $manager)
    {
        $setting = new Setting();
        $setting
            ->setName('MENU')
            ->setEnabled(false)
            ->setHidden(true)
            ->setInfo('Menu global parameters.');
        $manager->persist($setting);

        $settingField = new SettingHasField();
        $settingField->setSetting($setting);
        $settingField->setName('MAX_DEPTH');
        $settingField->setValue('1');
        $settingField->setInfo(
            'Set max depth for menu items. If you want to specific max depth for selected menu, please add option MAX_DEPTH_[MENU_NAME]'
        );
        $settingField->setEnabled(false);
        $manager->persist($settingField);

        $manager->flush();
    }
}
