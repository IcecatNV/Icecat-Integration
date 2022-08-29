<?php

namespace IceCatBundle\EventSubscriber;

use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldset;
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

        if ($object instanceof \Pimcore\Model\DataObject\Icecat && $requestStack && $currentRequest = $requestStack->getCurrentRequest()) {
            $data = \json_decode($currentRequest->get("data"), true);

            if (isset($data["localizedfields"])) {
                $layoutChilds = ClassDefinition::getByName("Icecat")->getFieldDefinition("localizedfields")->getChildren();
                $localizedNameTitlePairs = [];
                foreach ($layoutChilds as $field) {
                    if ($field instanceof Fieldset) {
                        foreach ($field->getChildren() as $c) {
                            $localizedNameTitlePairs[$c->getName()] = $c->getTitle();
                        }
                    } else {
                        $localizedNameTitlePairs[$field->getName()] = $field->getTitle();
                    }
                }

                // added hardcoded as it take nested loops to get this data -- for block fields
                $localizedNameTitlePairs["galleryIconBlock"] = "Gallery Icons Block";
                $localizedNameTitlePairs["productStory"] = "Product Stories";
                $localizedNameTitlePairs["reviews"] = "Reviews";

                foreach ($data["localizedfields"] as $lang => $langData) {
                    foreach ($langData as $fields) {
                        foreach ($langData as $name => $value) {
                            $this->saveLog($object, $name, $localizedNameTitlePairs[$name] ?? null, $lang);
                        }
                    }
                }
            }

            if (isset($data["Features"])) {
                foreach ($data["Features"] as $featuresData) {
                    foreach ($featuresData as $lang => $group) {
                        if (!is_array($group) || count($group) === 0) {
                            continue;
                        }
                        foreach ($group as $keys) {
                            foreach ($keys as $k => $v) {
                                $sql = "SELECT title from classificationstore_keys WHERE id = {$k}";
                                $title = Db::get()->fetchOne($sql);
                                $this->saveLog($object, $title, $title, $lang);
                            }
                        }
                    }
                }
            }

            foreach ($data as $name => $value) {
                if ($name == "localizedfields" || $name == "Features") {
                    continue;
                }
                $title =  ClassDefinition::getByName("Icecat")->getFieldDefinition($name)->getTitle();
                $this->saveLog($object, $name, $title);
            }
        }
    }


    public function saveLog($object, $name, $title, $lang = null)
    {
        $icecatFieldLog = new \Pimcore\Model\DataObject\IcecatFieldsLog\Listing();
        if ($lang) {
            $icecatFieldLog->setCondition("pimcoreId = {$object->getId()} AND lang = '{$lang}' AND name = '{$name}'");
        } else {
            $icecatFieldLog->setCondition("pimcoreId = {$object->getId()} AND name = '{$name}'");
        }

        $list = $icecatFieldLog->load();
        if (count($list) && $record = $list[0]) {
            $record->save();
        } else {

            if($lang) {
                $key = "{$object->getGtin()}-{$lang}-{$name}";
            } else {
                $key = "{$object->getGtin()}-{$name}";
            }

            $icecatFieldLog = new \Pimcore\Model\DataObject\IcecatFieldsLog();
            $icecatFieldLog->setPimcoreId($object->getId());
            $icecatFieldLog->setIcecatId($object->getIcecat_Product_Id());
            $icecatFieldLog->setEan($object->getGtin());
            $icecatFieldLog->setLang($lang);
            $icecatFieldLog->setBrand($object->getBrand());
            $icecatFieldLog->setProductCode($object->getProduct_Code());
            $icecatFieldLog->setName($name);
            $icecatFieldLog->setField($title);
            $icecatFieldLog->setParent(Service::createFolderByPath('/Icecat overwritten fields log'));
            $icecatFieldLog->setPublished(true);
            $icecatFieldLog->setKey($key);
            $icecatFieldLog->save();
        }
    }
}
