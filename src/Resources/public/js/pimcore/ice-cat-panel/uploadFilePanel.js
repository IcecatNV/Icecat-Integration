pimcore.registerNS("pimcore.plugin.iceCatUploadFilePanel");
pimcore.plugin.iceCatUploadFilePanel = Class.create({
    intervalObj: '',
    configData: {},

    initialize: function () {
        this.selectedLanguages = [];
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
                        this.configData.gtinField = response.data.gtinField.name;
                        this.configData.gtinFieldType = response.data.gtinField.type;
                        this.configData.mappingGtinClassField = response.data.gtinField.referenceFieldName;
                        this.configData.mappingGtinLanguageField = response.data.gtinField.language;
                    }
                    if(response.data !== undefined && response.data.brandNameField !== undefined) {
                        this.configData.brandNameField = response.data.brandNameField.name;
                        this.configData.brandNameType = response.data.brandNameField.type;
                        this.configData.mappingBrandClassField = response.data.brandNameField.referenceFieldName;
                        this.configData.mappingBrandLanguageField = response.data.brandNameField.language;
                    }
                    if(response.data !== undefined && response.data.productNameField !== undefined) {
                        this.configData.productNameField = response.data.productNameField.name;
                        this.configData.productNameType = response.data.productNameField.type;
                        this.configData.mappingProductNameClassField = response.data.productNameField.referenceFieldName;
                        this.configData.mappingProductNameLanguageField = response.data.productNameField.language;
                    }
                    if(response.data !== undefined && response.data.cronExpression !== undefined) {
                        this.configData.cronExpression = response.data.cronExpression;
                    }

                    if(response.data !== undefined && response.data.assetFilePath !== undefined) {
                        this.configData.assetFilePath = response.data.assetFilePath;
                    }
                } else {
                    Ext.Msg.alert('Error', response.message);
                }
            }.bind(this)
        });
        console.log(this.configData);
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


        this.classFieldsStore = new Ext.data.JsonStore({
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('icecat_get_class_fields'),
                extraParams: {
                    classId: this.configData.productClass
                },
                reader: {
                    type: 'json',
                    rootProperty: 'attributes'
                }
            },
            fields: ['key', 'title', 'localized', 'type']
        });

        this.selectedLanguagesStore = new Ext.data.JsonStore({
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('icecat_getselectedlanguages'),
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            },
            fields: ['key', 'value']
        });

        this.brandReferenceFieldsStore = new Ext.data.JsonStore({
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('icecat_getreferencefields'),
                extraParams: {
                    field: this.configData.brandNameField,
                    class: this.configData.productClass
                },
                reader: {
                    type: 'json',
                    rootProperty: 'attributes'
                }
            },
            fields: ['key', 'title', 'localized', 'type']
        });

        this.gtinReferenceFieldsStore = new Ext.data.JsonStore({
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('icecat_getreferencefields'),
                extraParams: {
                    field: this.configData.gtinField,
                    class: this.configData.productClass
                },
                reader: {
                    type: 'json',
                    rootProperty: 'attributes'
                }
            },
            fields: ['key', 'title', 'localized', 'type']
        });

        this.productCodeReferenceFieldsStore = new Ext.data.JsonStore({
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('icecat_getreferencefields'),
                extraParams: {
                    field: this.configData.productNameField,
                    class: this.configData.productClass
                },
                reader: {
                    type: 'json',
                    rootProperty: 'attributes'
                }
            },
            fields: ['key', 'title', 'localized', 'type']
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

        if (this.displayPanel) {
            return this.displayPanel;
        }
        let item = {};
        item.id = 1;
        let __self = this;

        this.crontabExecution = Ext.create('Ext.form.FieldContainer', {
            fieldLabel: t(''),
            //layout: 'hbox',
            items: [
                {
                    xtype: 'radiogroup',
                    vertical: 'false',
                    columns: 2,
                    width: 400,
                    items: [
                        {
                            boxLabel: t('Only new products'),
                            name: 'scheduleType',
                            checked: !this.data || this.data.scheduleType !== 'job',
                            inputValue: 'recurring'
                        }, {
                            boxLabel: t('All products'),
                            name: 'scheduleType',
                            checked: this.data?.scheduleType === 'job',
                            inputValue: 'job'
                        }
                    ]
                },
                {
                    xtype: "fieldcontainer",
                    layout: "hbox",
                    items: [
                        {
                            xtype: "textfield",
                            required: true,
                            id: "cron_expression_Icecat",
                            fieldLabel: 'Cron definition',
                            labelWidth: 130,
                            triggerAction: 'all',
                            value: this.configData.cronExpression ? this.configData.cronExpression : '',
                            allowBlank: false,
                            typeAhead: false,
                            anyMatch: true,
                            width: 300,
                            name: 'cronExpression',
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
                            xtype: 'displayfield',
                            style: 'padding-left: 10px',
                            value: '<a target="_blank" href="https://crontab.guru/">' + t('plugin_pimcore_datahub_data_importer_configpanel_execution_cron_generator') + '</a>'
                        }
                    ]
                },
                {
                    xtype: "fieldcontainer",
                    fieldLabel: 'Manual execution',
                    labelWidth: 130,
                    items:[
                        {
                            xtype: 'button',
                            width: 80,
                            text: t('Start')
                        }
                    ]
                }
            ]
        });

        this.lastRunSummary = Ext.create('Ext.form.Label', {
            style: 'margin-bottom: 5px; display: block',
            html: '<b>Total Records:</b><div style="height:10px;"></div><b>Successfully Processed:</b><div style="height:10px;"></div><b>Errored Records:</b>'
        });

        this.progressLabel = Ext.create('Ext.form.Label', {
            style: 'margin-bottom: 5px; display: block'
        });
        this.progressBar = Ext.create('Ext.ProgressBar', {
            hidden: false
        });

        this.cronPanel = new Ext.form.FormPanel({
            items: [
                {
                    xtype: "fieldcontainer",
                    layout: "hbox",
                    items:[
                        {
                            xtype: 'fieldset',
                            width: 950,
                            title: t('Class fields mapping'),
                            items: [
                                {
                                    xtype: "combobox",
                                    required: true,
                                    id: "system_class_loader_Icecat",
                                    fieldLabel: 'Class',
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
                                    name: 'productClass',
                                    listeners: {
                                        'change': function (e, val) {
                                            this.classFieldsStore.getProxy().setExtraParam("classId", val);
                                            this.classFieldsStore.reload();
                                        }.bind(this)
                                    }
                                },
                                {
                                    xtype: "fieldcontainer",
                                    layout: "hbox",
                                    items: [
                                        {
                                            xtype: "combobox",
                                            id: "brand_name_cron_Icecat",
                                            fieldLabel: 'Brand',
                                            triggerAction: 'all',
                                            store: this.classFieldsStore,
                                            displayField: 'title',
                                            forceSelection: true,
                                            valueField: 'key',
                                            value: this.configData.brandNameField ? this.configData.brandNameField : null,
                                            multiselect: false,
                                            typeAhead: false,
                                            anyMatch: true,
                                            name: 'brandNameField',
                                            listeners: {
                                                'change': function (e, newValue, oldValue) {
                                                    Ext.getCmp("mapping_brand_class_field").hide();
                                                    Ext.getCmp("mapping_brand_language_field").hide();
                                                    const record = this.classFieldsStore.findRecord('key', newValue);
                                                    if(record && record.data.type == "manyToOneRelation") {
                                                        this.brandReferenceFieldsStore.getProxy().setExtraParam("field", newValue);
                                                        this.brandReferenceFieldsStore.getProxy().setExtraParam("class", Ext.getCmp("system_class_loader_Icecat").getValue());
                                                        this.brandReferenceFieldsStore.reload();
                                                        Ext.getCmp("mapping_brand_class_field").show();
                                                    } else if(record && record.data.localized == true) {
                                                        //Ext.getCmp("mapping_brand_language_field").show();
                                                    }
                                                }.bind(this)
                                            }
                                        },
                                        {
                                            xtype: "combobox",
                                            required: false,
                                            id: "mapping_brand_class_field",
                                            fieldLabel: 'Reference Class Field',
                                            labelWidth: 160,
                                            triggerAction: 'all',
                                            style: "margin-left:10px;",
                                            forceSelection: true,
                                            store: this.brandReferenceFieldsStore,
                                            displayField: 'title',
                                            valueField: 'key',
                                            multiselect: false,
                                            typeAhead: false,
                                            anyMatch: true,
                                            hidden: this.configData.mappingBrandClassField != "" ? false : true,
                                            value: this.configData.mappingBrandClassField ? this.configData.mappingBrandClassField : null,
                                            name: 'mappingBrandClassField',
                                            listeners: {
                                                // 'change': function (e, newValue, oldValue) {
                                                //     const record = this.brandReferenceFieldsStore.findRecord('key', newValue);
                                                //     if(record.data.localized) {
                                                //         //Ext.getCmp("mapping_brand_language_field").show();
                                                //     }
                                                // }.bind(this)
                                            }
                                        },
                                        {
                                            xtype: "combobox",
                                            id: "mapping_brand_language_field",
                                            fieldLabel: 'Language',
                                            forceSelection: true,
                                            triggerAction: 'all',
                                            style: "margin-left:10px;",
                                            store: this.selectedLanguagesStore,
                                            displayField: 'value',
                                            valueField: 'key',
                                            multiselect: false,
                                            typeAhead: false,
                                            anyMatch: true,
                                            //hidden: this.configData.mappingBrandLanguageField != "" ? false : true,
                                            hidden: true,
                                            value: this.configData.mappingBrandLanguageField ? this.configData.mappingBrandLanguageField : null,
                                            name: 'mappingBrandLanguageField'
                                        },
                                    ]
                                },
                                {
                                    xtype: "fieldcontainer",
                                    layout: "hbox",
                                    items: [
                                        {
                                            xtype: "combobox",
                                            id: "product_code_cron_Icecat",
                                            fieldLabel: 'Product Code',
                                            forceSelection: true,
                                            triggerAction: 'all',
                                            queryMode: 'local',
                                            store: this.classFieldsStore,
                                            displayField: 'title',
                                            valueField: 'key',
                                            value: this.configData.productNameField ? this.configData.productNameField : null,
                                            multiselect: false,
                                            typeAhead: false,
                                            anyMatch: true,
                                            name: 'productNameField',
                                            listeners: {
                                                'change': function (e, newValue, oldValue) {
                                                    Ext.getCmp("mapping_productcode_class_field").hide();
                                                    Ext.getCmp("mapping_productcode_language_field").hide();
                                                    const record = this.classFieldsStore.findRecord('key', newValue);
                                                    if(record && record.data.type == "manyToOneRelation") {
                                                        this.productCodeReferenceFieldsStore.getProxy().setExtraParam("field", newValue);
                                                        this.productCodeReferenceFieldsStore.getProxy().setExtraParam("class", Ext.getCmp("system_class_loader_Icecat").getValue());
                                                        this.productCodeReferenceFieldsStore.reload();
                                                        Ext.getCmp("mapping_productcode_class_field").show();
                                                    } else if(record && record.data.localized == true) {
                                                        //Ext.getCmp("mapping_productcode_language_field").show();
                                                    }
                                                }.bind(this)
                                            }
                                        },
                                        {
                                            xtype: "combobox",
                                            id: "mapping_productcode_class_field",
                                            fieldLabel: 'Reference Class Field',
                                            labelWidth: 160,
                                            triggerAction: 'all',
                                            forceSelection: true,
                                            style: "margin-left:10px;",
                                            store: this.productCodeReferenceFieldsStore,
                                            displayField: 'title',
                                            valueField: 'key',
                                            multiselect: false,
                                            typeAhead: false,
                                            anyMatch: true,
                                            hidden: this.configData.mappingProductNameClassField != "" ? false : true,
                                            value: this.configData.mappingProductNameClassField ? this.configData.mappingProductNameClassField : null,
                                            name: 'mappingProductCodeClassField',
                                            // listeners: {
                                            //     'change': function (e, newValue, oldValue) {
                                            //         const record = this.productCodeReferenceFieldsStore.findRecord('key', newValue);
                                            //         if(record.data.localized) {
                                            //             //Ext.getCmp("mapping_productcode_language_field").show();
                                            //         }
                                            //     }.bind(this)
                                            // }
                                        },
                                        {
                                            xtype: "combobox",
                                            id: "mapping_productcode_language_field",
                                            fieldLabel: 'Language',
                                            triggerAction: 'all',
                                            style: "margin-left:10px;",
                                            store: this.selectedLanguagesStore,
                                            displayField: 'value',
                                            valueField: 'key',
                                            multiselect: false,
                                            forceSelection: true,
                                            typeAhead: false,
                                            anyMatch: true,
                                            hidden: true,
                                            name: 'mappingProductCodeLanguageField'
                                        }
                                    ]
                                },
                                {
                                    xtype: "fieldcontainer",
                                    layout: "hbox",
                                    items: [
                                        {
                                            xtype: "combobox",
                                            id: "gtin_cron_Icecat",
                                            fieldLabel: 'GTIN',
                                            triggerAction: 'all',
                                            forceSelection: true,
                                            queryMode: 'local',
                                            store: this.classFieldsStore,
                                            displayField: 'title',
                                            valueField: 'key',
                                            value: this.configData.gtinField ? this.configData.gtinField : null,
                                            multiselect: false,
                                            typeAhead: false,
                                            anyMatch: true,
                                            name: 'gtinField',
                                            listeners: {
                                                'change': function (e, newValue, oldValue) {
                                                    Ext.getCmp("mapping_gtin_class_field").hide();
                                                    Ext.getCmp("mapping_gtin_language_field").hide();
                                                    const record = this.classFieldsStore.findRecord('key', newValue);
                                                    if(record && record.data.type == "manyToOneRelation") {
                                                        this.gtinReferenceFieldsStore.getProxy().setExtraParam("field", newValue);
                                                        this.gtinReferenceFieldsStore.getProxy().setExtraParam("class", Ext.getCmp("system_class_loader_Icecat").getValue());
                                                        this.gtinReferenceFieldsStore.reload();
                                                        Ext.getCmp("mapping_gtin_class_field").show();
                                                    } else if(record && record.data.localized == true) {
                                                        //Ext.getCmp("mapping_gtin_language_field").show();
                                                    }
                                                }.bind(this)
                                            }
                                        },
                                        {
                                            xtype: "combobox",
                                            id: "mapping_gtin_class_field",
                                            fieldLabel: 'Reference Class Field',
                                            labelWidth: 160,
                                            forceSelection: true,
                                            triggerAction: 'all',
                                            style: "margin-left:10px;",
                                            store: this.gtinReferenceFieldsStore,
                                            displayField: 'title',
                                            valueField: 'key',
                                            multiselect: false,
                                            typeAhead: false,
                                            anyMatch: true,
                                            hidden: this.configData.mappingGtinClassField != "" ? false : true,
                                            value: this.configData.mappingGtinClassField ? this.configData.mappingGtinClassField : null,
                                            name: 'mappingGtinClassField',
                                            // listeners: {
                                            //     'change': function (e, newValue, oldValue) {
                                            //         const record = this.gtinReferenceFieldsStore.findRecord('key', newValue);
                                            //         if(record.data.localized) {
                                            //             //Ext.getCmp("mapping_gtin_language_field").show();
                                            //         }
                                            //     }.bind(this)
                                            // }
                                        },
                                        {
                                            xtype: "combobox",
                                            id: "mapping_gtin_language_field",
                                            fieldLabel: 'Language',
                                            forceSelection: true,
                                            triggerAction: 'all',
                                            style: "margin-left:10px;",
                                            store: this.selectedLanguagesStore,
                                            displayField: 'value',
                                            valueField: 'key',
                                            multiselect: false,
                                            typeAhead: false,
                                            anyMatch: true,
                                            //hidden: this.configData.mappingGtinLanguageField != "" ? false : true,
                                            hidden: true,
                                            value: this.configData.mappingGtinLanguageField ? this.configData.mappingGtinLanguageField : null,
                                            name: 'mappingGtinLanguageField'
                                        },
                                    ]
                                }
                            ]
                        },
                        {
                            xtype: 'fieldset',
                            width: 300,
                            height: 226,
                            style: "margin-left:50px;",
                            title: t('Last import summary'),
                            defaults: {
                                labelWidth: 130
                            },
                            items: [
                                this.lastRunSummary
                            ]
                        }
                    ]
                },
                {
                    xtype: 'fieldset',
                    width: 950,
                    title: t('Asset'),
                    defaults: {
                        labelWidth: 130
                    },
                    items: [
                        this.getAssetComponent()
                    ]
                },
                {
                    xtype: 'tbspacer',
                    height: 10
                },
                {
                    xtype: 'fieldcontainer',
                    layout: "hbox",
                    items: [
                        {
                            xtype: 'fieldset',
                            width: 950,
                            title: t('Scheduler'),
                            defaults: {
                                labelWidth: 130
                            },
                            items: [
                                this.crontabExecution
                            ]
                        },
                        {
                            xtype: 'fieldset',
                            style: "margin-left:50px;",
                            title: t('Execution status'),
                            width: 300,
                            height: 202,
                            items: [
                                this.progressLabel,
                                this.progressBar
                            ]
                        }
                    ]
                },
                {
                    xtype: 'tbspacer',
                    height: 10
                },
                {
                    xtype: 'button',
                    id: 'ice_cat_cron_config_button',
                    text: 'Save Mapping & Scheduler',
                    width: '15%',
                    handler: function () {
                        var form = this.cronPanel.getForm();
                        if (form.isValid()) {
                            form.submit({
                                method: 'POST',
                                url: Routing.generate('icecat_saveconfig'),
                                params: {
                                    gtinFieldType: Ext.getCmp('mapping_gtin_class_field').getValue() != "" ? Ext.getCmp('mapping_gtin_class_field').getValue() : "default",
                                    brandNameFieldType: Ext.getCmp('mapping_brand_class_field').getValue() != "" ? Ext.getCmp('mapping_brand_class_field').getValue() : "default",
                                    productNameFieldType: Ext.getCmp('mapping_productcode_class_field').getValue() != "" ? Ext.getCmp('mapping_productcode_class_field').getValue() : "default"
                                },
                                success: function (form, action) {
                                    if (action.response.status != "undefined" && action.response.status == 200) {
                                        var resp = JSON.parse(action.response.responseText);
                                        if (resp.success === true) {
                                            pimcore.helpers.showNotification('Success', 'Config saved successfully!', 'success');
                                        } else if (resp.success === false && action.response.status == 303) {
                                            pimcore.helpers.showNotification('Failure', 'error', 'error');
                                        } else if (resp.success == false) {
                                            pimcore.helpers.showNotification('Failure', resp.message, 'error');
                                        } else {
                                            pimcore.helpers.showNotification('Failure', 'Something went wrong!', 'error');
                                        }
                                    }
                                },
                                failure: function (form, action) {
                                }
                            });
                        }
                    }.bind(this)
                }
            ]
        });
        
        this.uploadFilePanel = new Ext.form.FormPanel({
            region: "center",
            listeners: {
                'afterrender': function () {
                    let ap = new pimcore.plugin.IceCatActiveProcesses('', 'fetching');
                    if (ap.refreshTask != 'undefined')
                        ap.refreshTask.start();
                }
            },
            items: [
                {
                    xtype: "tagfield",
                    required: true,
                    id: "system_settings_general_languageSelectionIcecat",
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
                                    this.selectedLanguagesStore.reload();
                                }.bind(this),
                                failure: function (err) {
                                }.bind(this)
                            });
                        }.bind(this)
                    }
                },
                {
                    xtype: 'tbspacer',
                    height: 15
                },
                {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    combineErrors: true,
                    items: [
                        {
                            xtype: "checkbox",
                            required: true,
                            id: "system_settings_general_categorization",
                            fieldLabel: 'Icecat Categorization',
                            labelWidth: 160,
                            triggerAction: 'all',
                            queryMode: 'local',
                            name: 'categorization',
                            value: this.configData.categorization ? this.configData.categorization : false,
                            handler:function (component, value) {
                                Ext.MessageBox.confirm(t("are_you_sure"), t("You are about to "+ (value == true ? "make" : "lose") +" Icecat categorization leading in the Pimcore.\n Do you want to switch "+ (value == true ? "on" : "off") +" the Icecat categorization?"),
                                    function (buttonValue) {
                                        if (buttonValue == "yes") {
                                            Ext.Ajax.request({
                                                url: Routing.generate('icecat_saveconfig'),
                                                params: {categorization: value},
                                                method: 'GET',
                                                success: function (res) {
                                                    response = Ext.decode(res.responseText);
                                                    let ufp = Ext.getCmp('pimcore_iceCat_tabPanel');
                                                    if(response.success === false) {
                                                        Ext.Msg.alert('Error', response.message);
                                                        return false;
                                                    }
                                                    this.configData.categorization = value;
                                                    // if(value) {
                                                    //     ufp.child('#iceCatBundle_searchPanel').tab.show();
                                                    // } else {
                                                    //     ufp.child('#iceCatBundle_searchPanel').tab.hide();
                                                    // }
                                                }.bind(this),
                                                failure: function (err) {
                                                }.bind(this)
                                            });
                                        } else {
                                            Ext.getCmp("system_settings_general_categorization").setRawValue(!value);
                                        }
                                    }.bind(this));
                            }.bind(this)
                        },
                        {
                            xtype: "checkbox",
                            required: true,
                            style: "margin-left:30px;",
                            id: "system_settings_general_import_related_products",
                            fieldLabel: 'Import Related Products',
                            labelWidth: 160,
                            triggerAction: 'all',
                            queryMode: 'local',
                            name: 'categorization',
                            value: this.configData.importRelatedProducts ? this.configData.importRelatedProducts : false,
                            handler:function (component, value) {
                                Ext.MessageBox.confirm(t("are_you_sure"), t("You are about to "+ (value == true ? "enable" : "disable") +" importing related products in the Pimcore in case they do not already exist in the Pimcore. Do you wish to enable it?"),
                                    function (buttonValue) {
                                        if (buttonValue == "yes") {
                                            Ext.Ajax.request({
                                                url: Routing.generate('icecat_saveconfig'),
                                                params: {importRelatedProducts: value},
                                                method: 'GET',
                                                success: function (res) {
                                                    response = Ext.decode(res.responseText);
                                                    let ufp = Ext.getCmp('pimcore_iceCat_tabPanel');
                                                    if(response.success === false) {
                                                        Ext.Msg.alert('Error', response.message);
                                                        return false;
                                                    }
                                                    this.configData.categorization = value;
                                                }.bind(this),
                                                failure: function (err) {
                                                }.bind(this)
                                            });
                                        } else {
                                            Ext.getCmp("system_settings_general_import_related_products").setRawValue(!value);
                                        }
                                    }.bind(this));
                            }.bind(this)
                        }
                    ]
                },
                {
                    xtype: 'tbspacer',
                    height: 10
                },
                {
                    xtype: 'fieldset',
                    border: true,
                    bodyPadding: 10,
                    collapsible: true,
                    title: "One time import - Manual",
                    items: [
                        {
                            xtype: 'fieldset',
                            width: 1300,
                            title: 'Upload CSV / Excel file',
                            items: [
                                {
                                    xtype: 'filefield',
                                    name: 'File',
                                    fieldLabel: 'File',
                                    labelWidth: 50,
                                    msgTarget: 'side',
                                    allowBlank: false,
                                    anchor: '100%',
                                    buttonText: 'Select File...',
                                    id: 'file-upload-btn',
        
                                },
        
                                {
                                    xtype: 'button',
                                    id: 'ice_cat_upload_button',
                                    text: 'Upload',
                                    width: '15%',
                                    style: "float: right;",
                                    disabled: pimcore.globalmanager.get('iceCatData').fetchingProcessExist,
        
                                    handler: function () {
                                        var form = this.up('form').getForm();
                                        if (form.isValid()) {
                                            form.submit({
                                                method: 'POST',
                                                url: Routing.generate('icecat_upload-file'),
                                                params: {
                                                    // "class": Ext.getCmp('entityclass').getValue(),
                                                    "user": pimcore.globalmanager.get("user").id,
                                                    "iceCatUser": pimcore.globalmanager.get('iceCatData').user.icecat_user_id
        
                                                },
                                                waitMsg: 'Uploading your File...',
                                                success: function (form, action) {
                                                    if (action.response.status != "undefined" && action.response.status == 200) {
                                                        let helper = new window.top.pimcore.plugin.iceCatHelper();
                                                        let resp = Ext.decode(action.response.responseText);
                                                        helper.setOtherInfo(resp);
        
                                                        if (resp.success == "true") {
        
                                                            if (pimcore.globalmanager.get('objectCreatedFlag')) {
                                                                pimcore.globalmanager.remove('objectCreatedFlag');
                                                            }
        
                                                            if (pimcore.globalmanager.get('activeImport')) {
                                                                pimcore.globalmanager.remove('activeImport');
                                                            }
                                                            if (pimcore.globalmanager.get('shownNotification')) {
                                                                pimcore.globalmanager.remove('shownNotification');
                                                            }
        
        
                                                            if (Ext.getCmp('ice_cat_upload_button')) {
                                                                Ext.getCmp('ice_cat_upload_button').disable();
                                                            }
                                                            if (Ext.getCmp('icecat_upload_by_url_button')) {
                                                                Ext.getCmp('icecat_upload_by_url_button').disable();
                                                            }
                                                            __self.intervalObj = setInterval(function () {
                                                                pimcore.globalmanager.add('iceCatIntervalObj', __self.intervalObj);
        
        
                                                                if (Ext.query('*[id^=fetchProgressBar_active_process_]').length) {
                                                                    helper.getOtherInfos();
                                                                    __self.clearPBarInterval();
                                                                    return;
                                                                }
                                                                let ap = new pimcore.plugin.IceCatActiveProcesses('', 'fetching');
                                                                if (ap.refreshTask != 'undefined')
                                                                    ap.refreshTask.start();
                                                            }.bind(this), 2000);
        
                                                            // setTimeout(function () {
                                                            //
                                                            //     helper.getOtherInfo();
                                                            // }.bind(this), 10000);
        
        
                                                            pimcore.helpers.showNotification('Success', 'File uploaded successfully!', 'Success');
                                                        } else if (resp.success == "false" && resp.status == 303) {
                                                            pimcore.helpers.showNotification('Failure', 'File type not supported,Please use csv or xlsx', 'Failure');
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
                                {
                                    xtype: 'label',
                                    forId: 'myFieldId',
                                    text: 'Add product list with GTIN(EAN) or Brand name and Product code',
                                    margin: '0 0 0 55   '
                                }
                            ]
        
                        },
                        {
                            xtype: 'tbspacer',
                            height: 10
                        },
                        {
                            xtype: 'fieldset',
                            width: 1300,
                            title: 'Upload via URL',
                            items: [
                                {
                                    xtype: 'textfield',
                                    text: 'Upload via URL',
                                    width: '88%',
                                    id: 'icecat_upload_by_url_field',
                                    style: "float:left;",
                                    regex: /(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})$/,
                                },
                                {
                                    xtype: 'button',
                                    id: 'icecat_upload_by_url_button',
                                    text: 'Ok',
                                    width: '10%',
                                    style: "float: right;",
                                    disabled: pimcore.globalmanager.get('iceCatData').fetchingProcessExist,
                                    handler: function () {
                                        let urlComp = Ext.getCmp('icecat_upload_by_url_field');
                                        let languages = Ext.getCmp('system_settings_general_languageSelectionIcecat').value;
                                        if (urlComp.wasValid && urlComp.value && languages.length) {
                                            Ext.Ajax.request({
                                                method: 'POST',
                                                url: Routing.generate('icecat_upload-by-url'),
                                                params: {
                                                    url: urlComp.value,
                                                    "user": pimcore.globalmanager.get("user").id,
                                                    "iceCatUser": pimcore.globalmanager.get('iceCatData').user.icecat_user_id,
                                                    "language": languages.join('|')
                                                },
                                                success: function (response) {
                                                    let helper = new window.top.pimcore.plugin.iceCatHelper();
                                                    let resp = Ext.decode(response.responseText);
                                                    helper.setOtherInfo(resp);
        
                                                    if (pimcore.globalmanager.get('objectCreatedFlag')) {
                                                        pimcore.globalmanager.remove('objectCreatedFlag');
                                                    }
                                                    if (pimcore.globalmanager.get('activeImport')) {
                                                        pimcore.globalmanager.remove('activeImport');
                                                    }
                                                    if (pimcore.globalmanager.get('shownNotification')) {
                                                        pimcore.globalmanager.remove('shownNotification');
                                                    }
        
                                                    if (resp.success == "true") {
                                                        if (Ext.getCmp('icecat_upload_by_url_button')) {
                                                            Ext.getCmp('icecat_upload_by_url_button').disable();
                                                        }
                                                        if (Ext.getCmp('ice_cat_upload_button')) {
                                                            Ext.getCmp('ice_cat_upload_button').disable();
                                                        }
                                                        __self.intervalObj = setInterval(function () {
                                                            pimcore.globalmanager.add('iceCatIntervalObj', __self.intervalObj);
        
        
                                                            if (Ext.query('*[id^=fetchProgressBar_active_process_]').length) {
                                                                helper.getOtherInfos();
                                                                __self.clearPBarInterval();
                                                                return;
                                                            }
                                                            let ap = new pimcore.plugin.IceCatActiveProcesses('', 'fetching');
                                                            if (ap.refreshTask != 'undefined') {
                                                                ap.refreshTask.start();
                                                            }
        
                                                        }.bind(this), 2000);
        
                                                        // setTimeout(function () {
                                                        //
                                                        //     helper.getOtherInfo();
                                                        // }.bind(this), 10000);
        
        
                                                        pimcore.helpers.showNotification('Success', 'File via URL uploaded successfully!', 'Success');
                                                    } else if (resp.success == "false" && resp.status == 303) {
                                                        pimcore.helpers.showNotification('Failure', 'File via URL type not supported,Please use csv or xlsx', 'error');
                                                    } else {
        
                                                        pimcore.helpers.showNotification('Failure', 'Something went wrong!', 'Failure');
                                                    }
        
                                                }.bind(this),
                                                failure: function (err) {
                                                }.bind(this)
                                            });
                                        }
                                    }
        
                                },
                                {
                                    xtype: 'label',
                                    forId: 'myFieldId',
                                    text: 'Add product list with GTIN(EAN) or Brand name and Product code',
                                    margin: '0 0 0 0 ',
                                    style: {
                                        float: 'left',
        
                                    }
                                }
                            ]
        
                        },
                        {
                            xtype: 'tbspacer',
                            height: 10
                        },
                        {
                            xtype: 'fieldset',
                            width: 1300,
                            title: 'Data Import Progress',
                            id: 'fetchProgressBarFieldset',
                            hidden: !pimcore.globalmanager.get('iceCatData').fetchingProcessExist,
                            // items:
                            items: []
        
                        },
                        {
                            xtype: 'tbspacer',
                            height: 10
                        },
                        {
                            xtype: 'button',
                            id: 'ice_cat_process_restart_button',
                            text: 'Restart Process',
                            width: '15%',
                            // style: "float:right; margin-top:-50px;",
                            tooltip: "It will terminate any ongoing process",
                            handler: function () {
                                Ext.Msg.confirm(t('warning'), t('Are you sure you want to restart?')
                                    + "<br />" + t("<b style='color:red'>It will terminate current process </b>"),
                                    function (btn) {
                                        if (btn === 'yes') {
        
                                            Ext.Ajax.request({
                                                url: Routing.generate('icecat_terminate_process'),
                                                success: function (response) {
                                                    responseData = Ext.decode(response.responseText);
                                                    this.createGrid(false, response)
                                                }.bind(this),
                                                failure: function (err) {
                                                }.bind(this)
                                            });
                                            // this.reload(true);
                                            let helper = new window.top.pimcore.plugin.iceCatHelper();
                                            helper.reactivateforNewProcess();
                                        }
                                    }.bind(this)
                                );
                            }
                        }
                    ]
                }
            ]
        });

        this.displayPanel = new Ext.Panel({
            id: "iceCatBundle_uploadFilePanel",
            title: t('Add File'),
            bodyPadding: 10,
            width: 1200,
            border: false,
            autoScroll: true,
            iconCls: "pimcore_icon_upload",
            items: [
                this.uploadFilePanel,
                {
                    xtype: "fieldset",
                    collapsible: true,
                    bodyPadding: 10,
                    title: 'Recurring import - Automated',
                    border: true,
                    items: [
                        {
                            xtype: "label",
                            style: "font-size:15px;",
                            html: "<div class=\"objectlayout_element_text\"><div class=\"alert alert-primary\">Select class and fields mapping <b>OR</b> drag n drop excel file from assets section</div></div>",
                        },
                        {
                            xtype: 'tbspacer',
                            height: 10
                        },
                        this.cronPanel
                    ]
                }
               
            ]
        });
        
        return this.displayPanel;
    },

    clearPBarInterval: function () {
        if (this.intervalObj) {
            clearInterval(this.intervalObj);
        }
    },

    getAssetComponent: function() {

        this.component = Ext.create('Ext.form.TextField', {
            name: 'assetFilePath',
            value: this.configData.assetFilePath,
            fieldCls: 'pimcore_droptarget_input',
            width: 500,
            enableKeyEvents: true,
            msgTarget: 'under',
            listeners: {
                render: function (el) {
                    // add drop zone
                    new Ext.dd.DropZone(el.getEl(), {
                        reference: this,
                        ddGroup: "element",
                        getTargetFromEvent: function (e) {
                            return this.reference.component.getEl();
                        },
                        onNodeOver: function (target, dd, e, data) {
                            if (data.records.length === 1 && this.dndAllowed(data.records[0].data)) {
                                return Ext.dd.DropZone.prototype.dropAllowed;
                            }
                        }.bind(this),

                        onNodeDrop: this.onNodeDrop.bind(this)
                    });

                    el.getEl().on("contextmenu", this.onContextMenu.bind(this));

                }.bind(this)
            }
        });

        let composite = Ext.create('Ext.form.FieldContainer', {
            fieldLabel: t('Excel File'),
            layout: 'hbox',
            items: [
                this.component,
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    style: "margin-left: 5px",
                    handler: this.empty.bind(this)
                }
            ],
            width: 900,
            componentCls: "object_field object_field_type_manyToOneRelation",
            border: false,
            style: {
                padding: 0
            },
            listeners: {
                afterrender: function () {
                }.bind(this)
            }
        });

        return composite;
    },

    onNodeDrop: function (target, dd, e, data) {

        if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
        }

        data = data.records[0].data;

        if (this.dndAllowed(data)) {
            this.component.setValue(data.path);
            return true;
        } else {
            return false;
        }
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('empty'),
            iconCls: "pimcore_icon_delete",
            handler: function (item) {
                item.parentMenu.destroy();
                this.empty();
            }.bind(this)
        }));

        menu.showAt(e.getXY());

        e.stopEvent();
    },

    empty: function () {
        this.component.setValue("");
    },

    dndAllowed: function (data) {
        if (data.elementType === 'asset') {
            return data.type === 'document' || data.type === 'text' || data.type === 'unknown';
        }
        return false;
    },

    updateProgress: function() {
        clearTimeout(this.updateHandle);
        Ext.Ajax.request({
            url: Routing.generate('pimcore_dataimporter_configdataobject_checkimportprogress'),
            method: 'GET',
            params: {
                config_name: this.configName,
            },
            success: function (response) {
                let data = Ext.decode(response.responseText);

                if(data.isRunning) {
                    this.progressBar.show();
                    this.cancelButtonContainer.show();
                    this.progressBar.updateProgress(data.progress, data.processedItems + '/' + data.totalItems + ' ' + t('plugin_pimcore_datahub_data_importer_configpanel_execution_processed'));
                    this.progressLabel.setHtml(t('plugin_pimcore_datahub_data_importer_configpanel_execution_current_progress'));
                } else {
                    this.progressBar.hide();
                    this.cancelButtonContainer.hide();
                    this.progressLabel.setHtml('<b>' + t('plugin_pimcore_datahub_data_importer_configpanel_execution_not_running') + '</b>');
                }

                this.updateHandle = setTimeout(this.updateProgress.bind(this), 5000);

            }.bind(this)
        });
    }
});

new pimcore.plugin.iceCatUploadFilePanel();