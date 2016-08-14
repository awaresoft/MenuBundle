<?php

namespace Awaresoft\MenuBundle\Controller;

use Awaresoft\Sonata\AdminBundle\Controller\CRUDController as AwaresoftCRUDController;
use Awaresoft\Sonata\AdminBundle\Traits\ControllerHelperTrait;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

class MenuCRUDController extends AwaresoftCRUDController
{
    use ControllerHelperTrait;

    /**
     * @inheritdoc
     */
    public function preDeleteAction($object)
    {
        $message = $this->checkObjectIsBlocked($object, $this->admin);

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function batchActionDeleteIsRelevant(array $idx)
    {
        $message = null;

        foreach ($idx as $id) {
            $object = $this->admin->getObject($id);
            $message = $this->checkObjectIsBlocked($object, $this->admin);
        }

        if (!$message) {
            return true;
        }

        return $message;
    }
}
