<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace IceCatBundle\Website\LinkGenerator;

use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tool;

class Demo implements LinkGeneratorInterface
{
    /**
     * @param Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function generate(Concrete $object, array $params = []): string
    {

        //getting active language

        $activated = Tool::getValidLanguages();
        $defaultLocale = Tool::getSupportedLocales();
        $activatedLangWithDisplayValue = array_intersect_key($defaultLocale, array_flip($activatedLanguage));
        $finalResult = [];
        $i = 0;
        foreach ($activatedLangWithDisplayValue as $key => $language) {
            $finalResult[$i]['display_value'] = $language;
            $finalResult[$i]['key'] = $key;

            $i++;
        }

        $defaultLocale = \Pimcore\Tool::getDefaultLanguage();

        //Getting stored json for managing order of classification store

        $db = \Pimcore\Db::get();
        $query = 'select distinct language,data_encoded from icecat_imported_data where gtin = ' . $object->getKey();
        $data = $db->fetchAll($query);

        $finalJson = [];
        foreach ($data as $value) {
            $finalJson[$value['language']] = json_decode(base64_decode($value['data_encoded']), true);
        }

        include('Template.php');
        die;

        return 0;
    }
}
