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

use TestBundle\Website\Tool\ForceInheritance;
use TestBundle\Website\Tool\Text;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
//use TestBundle\Model\Product\Icecat;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ProductInterface;
use Pimcore\Model\DataObject\Icecat;
use Pimcore\Tool;


class ProductLinkGenerator implements LinkGeneratorInterface
{

    /**
     * @param Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function generate(Concrete $object, array $params = []): string
    {


        include('Template.php');
        //echo $object->getId();
        die('error');
    }
}
