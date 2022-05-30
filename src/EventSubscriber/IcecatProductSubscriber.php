<?php

namespace IceCatBundle\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IcecatProductSubscriber implements EventSubscriberInterface
{
    /**
     * Register events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            DataObjectEvents::PRE_UPDATE => [
                ['checkForCategorizationFlag', 70],
            ],
        ];
    }

    public function checkForCategorizationFlag(DataObjectEvent $event)
    {
        $object = $event->getObject();
        if ($object instanceof DataObject\Icecat) {
            if(is_array($object->getRelatedCategories()) && count($object->getRelatedCategories())) {
                $object->setCategorization(true);
            } else {
                $object->setCategorization(false);
            }
        }

    }
}
