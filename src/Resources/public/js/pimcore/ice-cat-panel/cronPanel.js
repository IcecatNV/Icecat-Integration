pimcore.registerNS("pimcore.plugin.iceCatCronPanelPanel");
pimcore.plugin.iceCatCronPanelPanel = Class.create({
    intervalObj: '',
    configData: {},

    initialize: function () {
        
        this.loadConfig();
    },

    loadConfig: function() {
        Ext.Ajax.request({
            async: false,
            url: Routing.generate('icecat_getconfig'),
            success: function (res) {
                let response = Ext.decode(res.responseText);
                if(response.success) {
                    if(response.data !== undefined && response.data.languages !== undefined) {
                        this.configData.selectedLanguages = response.data.languages; 
                    }
                    if(response.data !== undefined && response.data.categorization !== undefined) {
                        this.configData.categorization = response.data.categorization;
                    }

                    if(response.data !== undefined && response.data.importRelatedProducts !== undefined) {
                        this.configData.importRelatedProducts = response.data.importRelatedProducts;
                    }
                    if(response.data !== undefined && response.data.showSearchPanel !== undefined) {
                        this.configData.showSearchPanel = response.data.showSearchPanel;
                    }
                    if(response.data !== undefined && response.data.searchLanguages !== undefined) {
                        this.configData.searchLanguages = response.data.searchLanguages;
                    }
                    if(response.data !== undefined && response.data.classes !== undefined) {
                        this.configData.classes = response.data.classes;
                    }
                    if(response.data !== undefined && response.data.productClass !== undefined) {
                        this.configData.productClass = response.data.productClass;
                    }
                    if(response.data !== undefined && response.data.gtinField !== undefined) {
                        this.configData.gtinField = response.data.gtinField;
                    }
                    if(response.data !== undefined && response.data.brandNameField !== undefined) {
                        this.configData.brandNameField = response.data.brandNameField;
                    }
                    if(response.data !== undefined && response.data.productNameField !== undefined) {
                        this.configData.productNameField = response.data.productNameField;
                    }
                    if(response.data !== undefined && response.data.cronExpression !== undefined) {
                        this.configData.cronExpression = response.data.cronExpression;
                    }
                } else {
                    Ext.Msg.alert('Error', response.message);
                }
            }.bind(this)
        });
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
        Ext.Ajax.request({
            async: false,
            url: Routing.generate('icecat_get_classes'),
            success: function (response) {
                this.data = Ext.decode(response.responseText);
                this.classStore = new Ext.data.JsonStore({
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


        Ext.Ajax.request({
            async: false,
            url: Routing.generate('icecat_get_class_fields'),
            params: {classId: this.configData.productClass !== undefined ? this.configData.productClass : -1},
            success: function (response) {
                this.data = Ext.decode(response.responseText);
                this.classFieldsStore = new Ext.data.JsonStore({
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

    getConfigData: function() {
        return this.configData;
    },

    getValue: function (key, ignoreCheck) {

        var nk = key.split("\.");
        var current = this.data.values;

        for (var i = 0; i < nk.length; i++) {
            if (typeof current[nk[i]] != "undefined") {
                current = current[nk[i]];
            } else {
                current = null;
                break;
            }
        }

        if (ignoreCheck || (typeof current != "object" && typeof current != "array" && typeof current != "function")) {
            return current;
        }

        return "";
    },
    
    getPanel: function () {

        this.getData();

        if (this.cronPanel) {
            return this.cronPanel;
        }
        let item = {};
        item.id = 1;
        let __self = this;
        this.cronPanel = Ext.create('Ext.form.Panel', {
            id: "iceCatBundle_cronPanel",
            title: t('Crontab'),
            width: 800,
            height: 100,
            bodyPadding: 10,
            hidden: true,
            border: false,
            iconCls: "pimcore_icon_upload",
            frame: true,
            listeners: {
                'afterrender': function () {
                    // let ap = new pimcore.plugin.IceCatActiveProcesses('', 'fetching');
                    // if (ap.refreshTask != 'undefined')
                    //     ap.refreshTask.start();

                }
            },
            style: {
                // 'margin-top':'40%'
            },
            items: [


                {
                    xtype: "combobox",
                    required: true,
                    id: "system_class_loader_Icecat",
                    fieldLabel: 'Select Product Class',
                    labelWidth: 200,
                    triggerAction: 'all',
                    queryMode: 'local',
                    store: this.classStore,
                    displayField: 'display_value',
                    valueField: 'key',
                    value: this.configData.productClass,
                    multiselect: false,
                    forceSelection: true,
                    allowBlank: false,
                    typeAhead: false,
                    anyMatch: true,
                    width: 120,
                    name: 'productClass',
                    style: {
                        "display": "inline"
                    },
                    listeners: {
                        'change': function (e, val) {
                            Ext.Ajax.request({
                                url: Routing.generate('icecat_get_class_fields'),
                                params: {classId: val},
                                method: 'GET',
                                success: function (res) {
                                    let response = Ext.decode(res.responseText);
                                    console.log('response');
                                    console.log(response);
                                    if(response.success === false) {
                                        Ext.Msg.alert('Error', response.message);
                                        return false;
                                    }
                                    this.classFieldsStore.setData(response.data);
                                }.bind(this),
                                failure: function (err) {
                                }.bind(this)
                            });
                        }.bind(this)
                    }
                },
                {
                    xtype: 'tbspacer',
                    height: 20
                },
                {
                    xtype: "combobox",
                    required: true,
                    id: "gtin_cron_Icecat",
                    fieldLabel: 'Select GTIN Field',
                    labelWidth: 200,
                    triggerAction: 'all',
                    queryMode: 'local',
                    store: this.classFieldsStore,
                    displayField: 'display_value',
                    valueField: 'key',
                    value: this.configData.gtinField ? this.configData.gtinField : -1,
                    multiselect: false,
                    forceSelection: true,
                    allowBlank: false,
                    typeAhead: false,
                    anyMatch: true,
                    width: 80,
                    name: 'gtinField',
                    style: {
                        "display": "inline"
                    },
                    listeners: {
                        'change': function (e, val) {
                        }.bind(this)
                    }
                },
                {
                    xtype: 'tbspacer',
                    height: 20
                },
                {
                    xtype: "combobox",
                    required: true,
                    id: "brand_name_cron_Icecat",
                    fieldLabel: 'Select Brand Name Field',
                    labelWidth: 200,
                    triggerAction: 'all',
                    queryMode: 'local',
                    store: this.classFieldsStore,
                    displayField: 'display_value',
                    valueField: 'key',
                    value: this.configData.brandNameField ? this.configData.brandNameField : -1,
                    multiselect: false,
                    forceSelection: true,
                    allowBlank: false,
                    typeAhead: false,
                    anyMatch: true,
                    width: 80,
                    name: 'brandNameField',
                    style: {
                        "display": "inline"
                    },
                    listeners: {
                        'change': function (e, val) {

                        }.bind(this)
                    }
                },
                {
                    xtype: "combobox",
                    required: true,
                    id: "product_code_cron_Icecat",
                    fieldLabel: 'Select Product Code Field',
                    labelWidth: 200,
                    triggerAction: 'all',
                    queryMode: 'local',
                    store: this.classFieldsStore,
                    displayField: 'display_value',
                    valueField: 'key',
                    value: this.configData.productNameField ? this.configData.productNameField : -1,
                    multiselect: false,
                    forceSelection: true,
                    allowBlank: false,
                    typeAhead: false,
                    anyMatch: true,
                    width: 80,
                    name: 'productNameField',
                    style: {
                        "display": "inline",
                        "margin-left": '5%'
                    },
                    listeners: {
                        'change': function (e, val) {
                        }.bind(this)
                    }
                },
                {
                    xtype: 'tbspacer',
                    height: 20
                },
                {
                    xtype: "textfield",
                    required: true,
                    id: "cron_expression_Icecat",
                    fieldLabel: 'Enter Cron Expression',
                    labelWidth: 200,
                    triggerAction: 'all',
                    // queryMode: 'local',
                    // store: this.languagesStore,
                    // displayField: 'display_value',
                    // valueField: 'key',
                    value: this.configData.cronExpression ? this.configData.cronExpression : '',
                    // multiselect: false,
                    // forceSelection: true,
                    allowBlank: false,
                    typeAhead: false,
                    anyMatch: true,
                    width: 80,
                    name: 'cronExpression',
                    style: {
                        "display": "inline"
                    },
                    listeners: {
                        'change': function (e, val) {
                            Ext.Ajax.request({
                                url: Routing.generate('icecat_saveconfig'),
                                params: {languages: val.join('|')},
                                method: 'GET',
                                success: function (res) {
                                    response = Ext.decode(res.responseText);
                                    if(response.success === false) {
                                        Ext.Msg.alert('Error', response.message);
                                        return false;
                                    }
                                    this.configData.selectedLanguages = val;
                                }.bind(this),
                                failure: function (err) {
                                }.bind(this)
                            });
                        }.bind(this)
                    }
                },
                {
                    xtype: 'tbspacer',
                    height: 20
                },
                {
                    xtype: 'button',
                    id: 'ice_cat_cron_config_button',
                    text: 'Save',
                    width: '15%',
                    style: "float: right;",
                    // disabled: pimcore.globalmanager.get('iceCatData').fetchingProcessExist,

                    handler: function () {
                        var form = this.up('form').getForm();
                        console.log('===Form', form);
                        if (form.isValid()) {
                            form.submit({
                                method: 'POST',
                                url: Routing.generate('icecat_saveconfig'),
                                params: {
                                    // "class": Ext.getCmp('entityclass').getValue(),
                                    "user": form.cronProductCode,
                                    "iceCatUser": pimcore.globalmanager.get('iceCatData').user.icecat_user_id

                                },
                                // waitMsg: 'Uploading your File...',
                                success: function (form, action) {
                                    if (action.response.status != "undefined" && action.response.status == 200) {
                                        // let helper = new window.top.pimcore.plugin.iceCatHelper();
                                        // let resp = Ext.decode(action.response.responseText);
                                        helper.setOtherInfo(resp);

                                        if (resp.success == "true") {


                                            pimcore.helpers.showNotification('Success', 'Config saved successfully!', 'Success');
                                        } else if (resp.success == "false" && resp.status == 303) {
                                            pimcore.helpers.showNotification('Failure', 'error', 'Failure');
                                        } else {

                                            pimcore.helpers.showNotification('Failure', 'Something went wrong!', 'Failure');
                                        }
                                    }
                                },
                                failure: function (form, action) {
                                }
                            });
                        }
                    }
                },





            ]
        });

        return this.cronPanel;
    },

    clearPBarInterval: function () {
        if (this.intervalObj) {
            clearInterval(this.intervalObj);
        }
    }
});

new pimcore.plugin.iceCatCronPanelPanel();