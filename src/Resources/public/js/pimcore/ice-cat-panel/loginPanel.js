pimcore.registerNS("pimcore.plugin.iceCatLoginPanel");
pimcore.plugin.iceCatLoginPanel = Class.create({
    getPanel: function () {
        if (this.loginPanel) {
            return this.loginPanel;
        }
        this.loginPanel = new Ext.Panel({
            id:         "iceCatBundle_login_panel",
            title:      "Login",
            iconCls:    "pimcore_icon_table_icecat_cat",
            items:[
                {
                    xtype: 'component',
                    itemId: 'my_filter',
                    autoScroll: true,
                    autoEl: {
                        tag: 'iframe',
                        style: 'height: 100%; width: 100%; border:none;',
                        frameclass: 'iceCatBundle',
                        src: Routing.generate('icecat_get-login-page')
                    }
                }
            ]
        });
        return this.loginPanel;
    }
});