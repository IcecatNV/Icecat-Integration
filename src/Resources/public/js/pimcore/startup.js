pimcore.registerNS("pimcore.plugin.IceCatBundle");

pimcore.plugin.IceCatBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.IceCatBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        // alert("IceCatBundle ready!");
    },
    
    postOpenObject: function (object, type) {
        if (
            object && object.data &&
            object.data.general && object.data.general.o_className == 'Icecat'
        ) {
            (new pimcore.plugin.refreshIcecatProduct(object)).attachRefreshButton();
        }

    },

});

var IceCatBundlePlugin = new pimcore.plugin.IceCatBundle();
