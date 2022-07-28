<?php

namespace IceCatBundle\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Service;
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
                ['logChangedFields', 50],
            ],
        ];
    }

    public function checkForCategorizationFlag(DataObjectEvent $event)
    {
        $object = $event->getObject();
        if ($object instanceof DataObject\Icecat) {
            if (is_array($object->getRelatedCategories()) && count($object->getRelatedCategories())) {
                $object->setCategorization(true);
            } else {
                $object->setCategorization(false);
            }
        }
    }

    public function logChangedFields(DataObjectEvent $event)
    {
        $requestStack = \Pimcore::getKernel()->getContainer()->get("request_stack");
        $object = $event->getObject();

        if($object instanceof \Pimcore\Model\DataObject\Icecat && $requestStack && $currentRequest = $requestStack->getCurrentRequest()) {
            $data = \json_decode($currentRequest->get("data"), true);
            foreach($data["localizedfields"] as $lang => $langData) {
                foreach($langData as $fields) {
                    foreach($langData as $name => $value) {
                        $icecatFieldLog = new \Pimcore\Model\DataObject\IcecatFieldsLog\Listing();
                        $icecatFieldLog->setCondition("pimcoreId = {$object->getId()} AND lang = '{$lang}' AND name = '{$name}'");
                        $list = $icecatFieldLog->load();
                        if(count($list) && $record = $list[0]) {
                            $record->save();
                        } else {
                            $icecatFieldLog = new \Pimcore\Model\DataObject\IcecatFieldsLog();
                            $icecatFieldLog->setPimcoreId($object->getId());
                            $icecatFieldLog->setEan($object->getGtin());
                            $icecatFieldLog->setLang($lang);
                            $icecatFieldLog->setName($name);
                            $icecatFieldLog->setField($name);
                            $icecatFieldLog->setParent(Service::createFolderByPath('/Icecat Fields Log'));
                            $icecatFieldLog->setPublished(true);
                            $icecatFieldLog->setKey("{$object->getGtin()}-{$name}");
                            $icecatFieldLog->save();
                        }
                    }
                }
            }

            foreach($data as $name => $value) {
                if($name == "localizedfields") {
                    continue;
                }

                $icecatFieldLog = new \Pimcore\Model\DataObject\IcecatFieldsLog\Listing();
                $icecatFieldLog->setCondition("pimcoreId = {$object->getId()} AND name = '{$name}'");
                $list = $icecatFieldLog->load();
                if(count($list) && $record = $list[0]) {
                    $record->save();
                } else {
                    $icecatFieldLog = new \Pimcore\Model\DataObject\IcecatFieldsLog();
                    $icecatFieldLog->setPimcoreId($object->getId());
                    $icecatFieldLog->setEan($object->getGtin());
                    $icecatFieldLog->setName($name);
                    $icecatFieldLog->setField($name);
                    $icecatFieldLog->setParent(Service::createFolderByPath('/Icecat Fields Log'));
                    $icecatFieldLog->setPublished(true);
                    $icecatFieldLog->setKey("{$object->getGtin()}-{$name}");
                    $icecatFieldLog->save();
                }
            }
        }

    }
}
