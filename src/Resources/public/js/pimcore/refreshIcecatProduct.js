pimcore.plugin.refreshIcecatProduct = Class.create({
    initialize: function (object) {
        this.object = object
        this.configData = {}
        this.getData();
    },

    getData: function () {
        Ext.Ajax.request({
            async: false,
            url: Routing.generate('icecat_valid_languages'),
            success: function (response) {

                this.data = Ext.decode(response.responseText);
                this.languagesStore = new Ext.data.JsonStore({
                    autoDestroy: true,
                    data: this.data,
                    proxy: {
                        type: 'memory',
                        reader: {
                            type: 'json',
                            rootProperty: 'data'
                        }
                    },
                    fields: ['display_value', 'key'],
                    async: false
                });

            }.bind(this)
        });
    },

    attachRefreshButton: function () {
        this.refreshIcecatProductButton = {
            text: t("Refresh from Icecat"),
            iconCls: "pimcore_icon_refresh",
            cls: "x-btn-button-default-toolbar-medium",
            scale: "medium",
            handler: function (obj) {
                this.getInputWindow();
                this.detailWindow.show();
            }.bind(this, this.object),
        };
        if(this.object.toolbar.query('[text=icecat]')) {
            this.object.toolbar.insert(this.object.toolbar.items.indexOf(this.object.toolbar.query('[text=Actions]')[0]), this.refreshIcecatProductButton);
        }
    },

    getInputWindow: function () {

        if(!this.detailWindow) {
            //var height = 200;
            this.detailWindow = new Ext.Window({
                width: 500,
                //height: height,
                //iconCls: "pimcore_icon_info",
                title: 'Refresh product data from Icecat',
                layout: "fit",
                closeAction:'close',
                plain: true,
                autoScroll: true,
                modal: true,
            });

            this.createPanel();
        }
        return this.detailWindow;
    },

    createPanel: function() {

        let items = [
            {
                xtype: "tagfield",
                required: true,
                //id: "ice_cat_refresh_product_language_selection_"+this.object.id,
                fieldLabel: 'Select Language',
                labelWidth: 160,
                triggerAction: 'all',
                queryMode: 'local',
                store: this.languagesStore,
                displayField: 'display_value',
                valueField: 'key',
                value: this.configData.selectedLanguages ? this.configData.selectedLanguages : ['en'],
                multiselect: true,
                forceSelection: true,
                allowBlank: false,
                typeAhead: false,
                anyMatch: true,
                width: 40,
                name: 'language[]',
                style: {
                    "display": "inline"
                }
            },
            {
                xtype: 'tbspacer',
                height: 20
            },
            {
                xtype: 'button',
                //id: 'ice_cat_refresh_product_button'+this.object.id,
                text: 'Proceed',
                width: '200',
                handler: function () {
                    this.detailWindow.hide();
                    var form = this.panel.getForm();
                    if (form.isValid()) {

                        this.downloadProgressBar = new Ext.ProgressBar({
                            text: t(`Pimcore ID ${this.object.id} is getting updated ...`)
                        });
            
                        this.downloadProgressWin = new Ext.Window({
                            layout:'fit',
                            width:300,
                            bodyStyle: "padding: 5px; margin-right:10px;",
                            closable:false,
                            plain: true,
                            items: [this.downloadProgressBar],
                            listeners: pimcore.helpers.getProgressWindowListeners()
                        });
                        this.downloadProgressWin.show();

                        form.submit({
                            method: 'POST',
                            url: Routing.generate('icecat_refresh_product'),
                            params: {
                                objectId: this.object.id
                            },
                            success: function (form, action) {
                                this.downloadProgressWin.close();
                                if (action.response.status != "undefined" && action.response.status == 200) {
                                    var resp = JSON.parse(action.response.responseText);
                                    if (resp.success === true) {
                                        pimcore.helpers.showNotification('Success', `Pimcore ID ${this.data.general.o_key} updated successfully`, 'success');
                                    } else if (resp.success === false && action.response.status == 303) {
                                        pimcore.helpers.showNotification('Failure', 'error', 'error');
                                    } else {
                                        pimcore.helpers.showNotification('Failure', 'Something went wrong!', 'error');
                                    }
                                }
                            }.bind(this),
                            update: function (currentStep, steps, percent) {
                                if(this.downloadProgressBar) {
                                    var status = currentStep / steps;
                                    this.downloadProgressBar.updateProgress(status, percent + "%");
                                }
                            }.bind(this),
                            failure: function (form, action) {
                            },
                            complete: function(){ 
                            }.bind(this)
                        });
                    }
                }.bind(this)
            }
        ];

        this.panel = new Ext.form.FormPanel({
            border: false,
            frame:false,
            bodyStyle: 'padding:10px',
            items: items,
            defaults: {
                labelWidth: 130
            },
            collapsible: false,
            autoScroll: true
        });

        this.detailWindow.add(this.panel);
    }
});