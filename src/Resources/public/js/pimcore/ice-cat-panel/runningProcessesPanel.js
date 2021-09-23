pimcore.registerNS("pimcore.plugin.iceCatRunningProcessesPanel");
pimcore.plugin.iceCatRunningProcessesPanel = Class.create({
    initialize : function(parent) {
        // this.getPanel();
    },

    getPanel: function () {
        if (this.runningProcessesPanel) {
            return this.runningProcessesPanel;
        }
        this.runningProcessesPanel = new Ext.Panel({
            id:         "iceCatBundle_runningProcessesPanel111",
            title:      "Running Processes",
            width: 800,
            height: 100,
            bodyPadding: 10,
            hidden: true,
            border:     false,
            iconCls:    "pimcore_icon_upload",
            frame: true,
            listeners:{
                'afterrender':function(){
                }
            },
            style: {
                // 'margin-top':'40%'
            },
            items: [
                {
                    xtype: 'fieldset',
                    title: 'Object Creation Progress',
                    id: 'processingProgressBarFieldset111',
                    hidden: !pimcore.globalmanager.get('iceCatData').objectCreationProcessExist,
                    // items:
                    items: [],
                    listeners: {
                        'afterrender': function () {

                            // let ap = new pimcore.plugin.IceCatActiveCreationProcesses('', 'processing', 'processingProgressBarFieldset');
                            // ap.refreshTask.start();
                            // console.log("ap.displayList", ap.displayList);
                        }
                    }

                }
            ]
        });
        return this.runningProcessesPanel;
    }
});