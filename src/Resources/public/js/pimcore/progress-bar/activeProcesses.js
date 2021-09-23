pimcore.registerNS("pimcore.plugin.IceCatActiveProcesses");

pimcore.plugin.IceCatActiveProcesses = Class.create(pimcore.plugin.admin, {
    displayList : [],
    toast : null,
    refreshIntervalSeconds : 3,
    toastElement: null,
    type: 'running',

    currentStep: 0,
    helper: null,
    importGridTabSet: false,

    // activeItemIds: [],

    initialize: function (toastElement, type, parentCmp='fetchProgressBarFieldset') {

       
        this.displayList = [];
        this.parentCmp = parentCmp;
        this.type = type ? type : this.type;
        this.toastElement = toastElement;
        let runner = new Ext.util.TaskRunner(), task;
      
            this.refreshTask = runner.newTask({
            run: this.requestServerData.bind(this),
            fireOnStart : true,
            interval: this.refreshIntervalSeconds * 1000
            });
        
     

        if (this.helper === null) {
            this.helper = new window.top.pimcore.plugin.iceCatHelper();
        }

    },

    getPanelByItemId : function(id){
        return Ext.getCmp('fetchProgressBar_active_process_'+id);
    },

    _buildBar: function(item) {

        if (this.getPanelByItemId(item.id)) {
            let currentItem = this.getPanelByItemId(item.id);
            let progressBar = Ext.getCmp('fetchProgressBar_monitoring_item_progress_bar_' + item.id);
            let actionBtnsPanel = Ext.getCmp('fetchProgressBar_active_process_action_buttons_' + item.id);

            this._updateProgressBar(progressBar, item);
            // this._updatePrimaryBtn(item);
            // this._updateActionBtns(actionBtnsPanel, item);

            return;
        }

        let progressBar = Ext.create('Ext.ProgressBar', {
            cls: 'process-manager-notification-progress-bar',
            id: 'fetchProgressBar_monitoring_item_progress_bar_' + item.id,
            alignOnScroll: false,
            height: 30,
            flex: 5,
            style: 'margin-top: 0px'
        });

        this._updateProgressBar(progressBar, item);

        let headerText = {
            hideLabel : true,
            xtype: 'displayfield',
            alignOnScroll : false,
            value: item.name + ' (ID: <a href="#" class="process-manager-details-link" onclick="fetchProgressBarPlugin.openLogId(' + item.id + ');  return false;">' + item.id +')</a>',
            labelStyle : 'display:none',
            cls: 'process-manager-active-processes-header-text'
        };

        let progressPanel = Ext.create('Ext.panel.Panel', {
            //bodyPadding: 5,  // Don't want content to crunch against the borders
            id: 'fetchProgressBar_monitoring_item_progress_panel_' + item.id,
            flex: 12,
            layout: {
                type: 'hbox',
            },
            items: [
                progressBar,
                // primaryBtn
            ],
        });

        let panel = Ext.create('Ext.panel.Panel', {
            id: 'fetchProgressBar_active_process_' + item.id,
            monitoringItemData : item,
            hideLabel: true,
            style: {
                borderBottom: '1px solid #e0e1e2',
                paddingBottom: '5px',
                marginBottom: '12px'
            },
            items: [
                // headerText,
                progressPanel,
                // actionBtnsPanel
            ],
        });

        //workaround with displayList go the toast displayed properly the fist time (items has to be added before toast is created)
        this.displayList.push(panel);
        if (this.toast != null) {
            this.toast.insert(0,panel);
            this.toast.show();
        }

    },

    _updateProgressBar: function(progressBar, item) {
        
        let message = '';
        if(item.message){
            message = item.message;
        }

        if(item.progressPercentage){
            message += ' (' + Math.round(item.progressPercentage) + '%)';
        }


        if(item.totalSteps > 1){
            message += ' - Step ' + item.currentStep + '/' + item.totalSteps;
        }

        message = message.trim();


        progressBar.updateProgress(item.progressPercentage/100, message, true);
        progressBar.removeCls('unknown');
        progressBar.removeCls('finished');
        progressBar.removeCls('finished_with_errors');
        progressBar.removeCls('failed');
        progressBar.removeCls('running');
        progressBar.removeCls('initializing');

        progressBar.addCls(item.status);
       
        let activeImport = pimcore.globalmanager.get('activeImport');
        // if (Math.round(item.progressPercentage) == 100 && activeImport == false) {
            if (Math.round(item.progressPercentage) == 100 && !this.importGridTabSet) {
              
            this.importGridTabSet = true;
              pimcore.globalmanager.add('activeImport', {});
              let intervalObj = pimcore.globalmanager.get('iceCatIntervalObj');
              if (intervalObj) {
                  clearInterval(intervalObj);
                  pimcore.globalmanager.remove('iceCatIntervalObj')
              }
              if(!pimcore.globalmanager.get('shownNotification'))
              {

                pimcore.globalmanager.add('shownNotification','set')
                pimcore.helpers.showNotification('Success', 'Data for uploaded file fetched successfully!', 'Success');

              }
                this.refreshTask.stop();
                this.helper.setActiveTab(2);
        }
    },

    _updatePrimaryBtn: function(item) {
        
        let button = Ext.getCmp('fetchProgressBar_monitoring_item_primary_button_' + item.id);
        if(item.isAlive){
            button.setIcon('/bundles/pimcoreadmin/img/flat-color-icons/cancel.svg')
            button.setTooltip(t('plugin_pm_stop'));
        }else{
            button.setIcon('/bundles/pimcoreadmin/img/flat-color-icons/approve.svg');
            button.setTooltip(t('plugin_pm_process_list_set_unpublished'));
        }
        //need to set the handler here to get the correct value for the item
        button.handler = function() {
            if(item.isAlive){
                Ext.Ajax.request({
                    url: '/admin/elementsfetchProgressBar/monitoring-item/cancel',
                    method : 'get',
                    params : {
                        id : item.id
                    },
                    success: function (content) {
                        let result = Ext.decode(content.responseText);
                        if(result.success){
                        }
                    }.bind(this)
                });
            }else{
                Ext.Ajax.request({
                    url: '/admin/elementsfetchProgressBar/monitoring-item/update',
                    method : 'post',
                    params : {
                        id : item.id,
                        published : true
                    },
                    success: function (content) {
                        let result = Ext.decode(content.responseText);
                        if(result.success){
                            this._removeProgressPanel(result.data.id);
                        }
                    }.bind(this)
                });
            }
        }.bind(this)
        //button.setDisabled(item.isAlive);
    },

    _updateActionBtns: function(actionBtnsPanel, item) {

        actionBtnsPanel.items.removeAll(); //do not remove them as it causes flickering
        actionBtnsPanel.update();

        if(item.actionItems.length){
            for(let i = 0; i < item.actionItems.length; i++){
                let actionData = item.actionItems[i];

                if(actionData.dynamicData && actionData.dynamicData.extJsClass){

                    let obj = eval('new ' + actionData.dynamicData.extJsClass + '()');
                    if(typeof obj.executeActionForActiveProcessList == 'function'){
                        obj.executeActionForActiveProcessList(actionBtnsPanel, actionData,item,this,i);
                    }
                }

            }
        }

        actionBtnsPanel.update();
        actionBtnsPanel.updateLayout();
    },

    _updatePanelTitle : function(data){
        if(this.toast){
            //  let title = t('plugin_pm_process_list_title') + ' <small id="fetchProgressBarActiveText">(' + data.active +' from  ' + data.total +' active)</small> ';
            //this.toast.setTitle(title);
            //do not use setTitle as it causes scrolling
            let el = document.getElementById('fetchProgressBarActiveText');
            if(el){
                let s = '';
                if(data.active){
                    s = '(' + data.active +' ' + t('plugin_pm_from') +' ' + data.total +' ' + t('plugin_pm_active') + ')';
                }else{
                    s = '(<img src="/bundles/pimcoreadmin/img/flat-color-icons/approve.svg" alt="" class="active_processes_finished_icon">' + t('plugin_pm_all_finished') +')'
                }
                el.innerHTML = s;
            }
        }

    },

    // destoryAllPanel

    requestServerData: function() {

        //console.log('Query Monitoring items... ');
        if(pimcore.globalmanager.get('activeImport') == false)
        {
        Ext.Ajax.request({
            url: Routing.generate('icecat_get-progress-bar-data'),
            params: {
                type: this.type,
                previousStep: this.currentStep
            },
            success: function (content) {
                let data = Ext.decode(content.responseText);
                let items = data.items;

                 let activeItemIds = [];

              

                if (items.length > 0) {


                    // let activeItemIds = [];
                    for (itemKey in items) {
                        this._buildBar(items[itemKey]);
                        this.currentStep = items[itemKey].currentStep;
                        activeItemIds.push(items[itemKey].id);
                    }

                    if(!this.toast){
                        this.toast = Ext.getCmp(this.parentCmp);
                      
                        for (let i in this.displayList) {
                            this.toast.add(this.displayList[i]);
                        }
                        this.toast.on("destroy", function () {
                            this.refreshTask.stop();

                            // this.toast.removeAll(true,true);
                            this.toast = null;
                            this.displayList = [];

                        }.bind(this))
                    }

                } else{
                    this.refreshTask.stop();
                }

                if(data.active == 0){
                    this.refreshTask.stop();
                }else{
                    if(this.toast.isHidden()){
                        this.toast.show(); //show on restart if hidden
                    }
                }

                //monitoring item might have been delete through an other process or in the grid view
                if(this.toast){
                    for(let i = 0; i < this.toast.items.items.length;i++){
                        let currentPanel = this.toast.items.items[i];
                      
                        if(!activeItemIds.includes(currentPanel.monitoringItemData.id)){
                            this._removeProgressPanel(currentPanel.monitoringItemData.id);
                        }
                    }
                }
                this._updatePanelTitle(data);

            }.bind(this)
        });
    }
    },


    _removeProgressPanel: function(itemId) {
        if (!this.toast) {
            return;
        }

        let panel = this.getPanelByItemId(itemId);
        panel.el.slideOut('t', {
            easing: 'easeOut',
            duration: 500,
            scope: this,
            callback : function () {
                for (let i=0; i < this.toast.items.length; i++) {
                    let p = this.toast.items.get(i);
                    if (p.id == panel.id) {

                        this.toast.remove(i,true);
                        if (this.toast.items.length == 0) {
                            this.toast.removeAll(true,true);
                            this.toast.remove(true);
                        }
                    }
                }
            }
        });
    }

});