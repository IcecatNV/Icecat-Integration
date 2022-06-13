<?php

namespace IceCatBundle\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IcecatCategorySubscriber implements EventSubscriberInterface
{
    /**
     * Register events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            DataObjectEvents::PRE_DELETE => [
                ['checkForCategorizationFlag', 70],
            ],
        ];
    }

    public function checkForCategorizationFlag(DataObjectEvent $event)
    {
        $object = $event->getObject();
        if ($object instanceof DataObject\IcecatCategory) {
            $sql = "SELECT * FROM object_Icecat WHERE RelatedCategories LIKE ',{$object->getId()},'";
            $result = \Pimcore\Db::get()->fetchAll($sql);

            foreach ($result as $r) {
                $productId = $r['oo_id'];
                $productObject = AbstractObject::getById($productId);
                $categoryIds = array_filter(explode(',', $r['RelatedCategories']));

                if (count($categoryIds) === 1) {
                    \Pimcore\Db::get()->executeQuery("UPDATE object_query_Icecat SET categorization = NULL WHERE oo_id = {$productId}");
                    \Pimcore\Db::get()->executeQuery("UPDATE object_store_Icecat SET categorization = NULL WHERE oo_id = {$productId}");
                    if ($productObject) {
                        \Pimcore\Cache::clearTags($productObject->getCacheTags());
                    }
                }
            }
        }
    }
}
