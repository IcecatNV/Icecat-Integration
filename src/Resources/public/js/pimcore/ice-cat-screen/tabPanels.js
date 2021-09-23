pimcore.registerNS("pimcore.plugin.tabPanels");
pimcore.plugin.tabPanels = Class.create({
    initialize : function(parent) {
        // this.getPanel();
    },

    getTabPanels: function () {
        let loginPanel = new pimcore.plugin.iceCatLoginPanel();
        let uploadPanel = new pimcore.plugin.iceCatUploadFilePanel();
        let importGridPanel = new pimcore.plugin.iceCatImportGridPanel();
        let runningProcessesPanel = new pimcore.plugin.iceCatRunningProcessesPanel();
      //  let logGridPanel = new pimcore.plugin.iceCatLogGridPanel();
       
     

        this.tabPanel = new Ext.TabPanel({
            id: "pimcore_iceCat_tabPanel",
            region: "center",
            plugins: ['tabclosemenu']
        });

        this.tabPanel.add(loginPanel.getPanel());
        
        // this.tabPanel.add(importGridPanel.getPanel());
        // this.tabPanel.add(runningProcessesPanel.getPanel());
        this.tabPanel.setActiveTab(0)
        pimcore.layout.refresh();
        return this.tabPanel;
    }
});