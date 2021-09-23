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
    }
});

var IceCatBundlePlugin = new pimcore.plugin.IceCatBundle();
