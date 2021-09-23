/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 *
 * This file is used for adding toolbar menu configration
 *
 */
pimcore.registerNS("pimcore.plugin.IceCatMenuToolbar");
pimcore.plugin.IceCatMenuToolbar = Class.create({

    initialize: function () {
        //code here
        this.leftNavigation();
    },

    /*
     * Add menu items in the left navigation panel
     */
    leftNavigation: function () {
        var IceCatLi = document.createElement("li");
        IceCatLi.setAttribute("id", "pimcore_menu_ice_cat_integration");
        IceCatLi.setAttribute("data-menu-tooltip", t("Icecat Integration"));
        IceCatLi.setAttribute("class", "pimcore_icon_table_icecat_menu");

        // var navigation = Ext.get("pimcore_navigation");
        // var ulElement = navigation.selectNode('ul');
        ulElement = Ext.get('pimcore_menu_file');
        ulElement.insertSibling(IceCatLi);
        pimcore.helpers.initMenuTooltips();
        var self = this;
        Ext.get("pimcore_menu_ice_cat_integration").on("click", function (e, el) {
            self.createPanel();
        });
    },

    createPanel: function () {
        if (!Ext.getCmp("ice_cat_integration_panel")) {
            pimcore.globalmanager.add('ice_cat_integration_panel', new pimcore.plugin.iceCatScreen());
        } else {
            Ext.getCmp("pimcore_panel_tabs").setActiveItem("ice_cat_integration_panel");
        }
    },
});
var toolbar = new pimcore.plugin.IceCatMenuToolbar();

