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


        this.classFieldMapping = Ext.create('Ext.form.FieldContainer', {
            fieldLabel: t(''),
            items: [{
                xtype: "combobox",
                required: true,
                id: "system_class_loader_Icecat",
                fieldLabel: 'Class',
                labelWidth: 60,
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
                    "display": "inline",
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
                xtype: "combobox",
                required: true,
                id: "brand_name_cron_Icecat",
                fieldLabel: 'Brand',
                labelWidth: 80,
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
                width: 60,
                name: 'brandNameField',
                style: {
                    "display": "inline",
                    "margin-left": '1%'
                },
                listeners: {
                    'change': function (e, val) {

                    }.bind(this)
                }
            },
            {
                xtype: "combobox",
                required: true,
                id: "gtin_cron_Icecat",
                fieldLabel: 'GTIN',
                labelWidth: 60,
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
                    "display": "inline",
                    "margin-left": '1%'
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
                fieldLabel: 'Product Code',
                labelWidth: 100,
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
                    "margin-left": '1%'
                },
                listeners: {
                    'change': function (e, val) {
                    }.bind(this)
                }
            }]
        });

        this.crontabExecution = Ext.create('Ext.form.FieldContainer', {
            fieldLabel: t(''),
            layout: 'hbox',
            items: [{
                xtype: "textfield",
                required: true,
                id: "cron_expression_Icecat",
                fieldLabel: 'Cron definition',
                labelWidth: 100,
                triggerAction: 'all',
                value: this.configData.cronExpression ? this.configData.cronExpression : '',
                allowBlank: false,
                typeAhead: false,
                anyMatch: true,
                width: 300,
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
            },{
                xtype: 'displayfield',
                //style: 'padding-left: 10px',
                value: '<a target="_blank" href="https://crontab.guru/">' + t('plugin_pimcore_datahub_data_importer_configpanel_execution_cron_generator') + '</a>'
            }]
        });

        this.lastRunSummary = Ext.create('Ext.form.Label', {
            style: 'margin-bottom: 5px; display: block',
            html: '<b>Total Records:</b><div style="height:10px;"></div><b>Successfully Processed:</b><div style="height:10px;"></div><b>Errored Records:</b>'
        });

        this.cronPanel = new Ext.FormPanel({
            id: "iceCatBundle_cronPanel",
            title: t('Automated Execution'),
            width: 800,
            height: 100,
            bodyPadding: 10,
            hidden: true,
            border: false,
            iconCls: "pimcore_icon_table_icecat_crontab",
            frame: true,
            items: [
                {
                    xtype: 'fieldset',
                    title: t('Class fields mapping'),
                    defaults: {
                        labelWidth: 130
                    },
                    items: [
                        this.classFieldMapping
                    ]
                },
                {
                    xtype: 'tbspacer',
                    height: 20
                },
                {
                    xtype: 'fieldset',
                    title: t('Crontab execution'),
                    defaults: {
                        labelWidth: 130
                    },
                    items: [
                        this.crontabExecution
                    ]
                },
                {
                    xtype: 'fieldset',
                    title: t('Last import summary'),
                    defaults: {
                        labelWidth: 130
                    },
                    items: [
                        this.lastRunSummary
                    ]
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
                    handler: function () {
                        var form = this.up('form').getForm();
                        if (form.isValid()) {
                            form.submit({
                                method: 'POST',
                                url: Routing.generate('icecat_saveconfig'),
                                params: {
                                    "user": form.cronProductCode,
                                    "iceCatUser": pimcore.globalmanager.get('iceCatData').user.icecat_user_id

                                },
                                success: function (form, action) {
                                    if (action.response.status != "undefined" && action.response.status == 200) {
                                        var resp = JSON.parse(action.response.responseText);
                                        console.log(resp);
                                        if (resp.success === true) {
                                            pimcore.helpers.showNotification('Success', 'Config saved successfully!', 'success');
                                        } else if (resp.success === false && action.response.status == 303) {
                                            pimcore.helpers.showNotification('Failure', 'error', 'error');
                                        } else {
                                            pimcore.helpers.showNotification('Failure', 'Something went wrong!', 'error');
                                        }
                                    }
                                },
                                failure: function (form, action) {
                                }
                            });
                        }
                    }
                }
            ]
            // defaultButton: 'cron_save_button',
            // buttons: [{
            //     reference: 'cron_save_button',
            //     text: t("save"),
            //     iconCls: "pimcore_icon_save"
            // }],
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