pimcore.registerNS("pimcore.plugin.iceCatScreen");
pimcore.plugin.iceCatScreen = Class.create({

    initialize : function(parent) {
        this.getPanel();
    },

    // Business Rule Left Panel (tree)
    getPanel : function() {

        //left Panel
        const tabs = new pimcore.plugin.tabPanels();

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id : "ice_cat_integration_panel",
                title : 'Icecat Integration',
                iconCls : "pimcore_icon_table_icecat_menu",
                bodyStyle : "padding: 10px;",
                layout : "fit",
                closable : true,
                items : [ tabs.getTabPanels() ]
            });

            const tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("ice_cat_integration_panel");

            this.panel.on("destroy", function() {
                pimcore.globalmanager.remove("ice_cat_integration_panel");
            }.bind(this));
            pimcore.layout.refresh();
        }
        return this.panel;
    }
});