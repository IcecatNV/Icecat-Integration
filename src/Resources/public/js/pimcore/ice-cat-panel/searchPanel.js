pimcore.registerNS("pimcore.plugin.iceCatSearchPanel");
pimcore.plugin.iceCatSearchPanel = Class.create({
    icon: "pimcore_icon_search",
    initialize: function (uploadPanel) {
        this.uploadPanel = uploadPanel;
        this.selectedLanguages = [];

        if (this.uploadPanel.getConfigData() && this.uploadPanel.getConfigData().searchLanguages !== undefined) {
            this.uploadPanel.getConfigData().searchLanguages.forEach(function (v) {
                this.selectedLanguages.push({ 'key': v.key, 'value': v.value });
            }.bind(this));
        }

        this.languagesStore = new Ext.data.JsonStore({
            autoDestroy: true,
            data: this.selectedLanguages,
            proxy: {
                type: 'memory',
                reader: {
                    type: 'json',
                }
            },
            fields: ['value', 'key'],
        });

    },

    getPanel: function () {
        if (!this.panel) {

            var panelConfig = {
                border: false,
                layout: "fit",
                iconCls: "pimcore_icon_search",
            };

            panelConfig.title = t("Search By Category");
            panelConfig.id = "iceCatBundle_searchPanel";
            panelConfig.tooltip = t("Search By Category");
            this.panel = new Ext.Panel(panelConfig);

            this.categoryStore = new Ext.data.JsonStore({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('icecat_categories_list'),
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'id'
                    }
                },
                fields: ['id', 'name']
            });

            this.brandStore = new Ext.data.JsonStore({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('icecat_brands_list'),
                    reader: {
                        rootProperty: 'data',
                        idProperty: 'id'
                    }
                },
                fields: ['key', 'value']
            });

            var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
            this.store = pimcore.helpers.grid.buildDefaultStore(
                Routing.generate('icecat_searchresult'),
                [
                    'id', 'icecat_productid', 'producttitle', 'productcode', 'brand', 'gtin', 'productfamily'
                ],
                itemsPerPage, {
                autoLoad: false
            }
            );

            var reader = this.store.getProxy().getReader();
            reader.setRootProperty('p_results');
            reader.setTotalProperty('p_totalCount');

            this.pagingToolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

            this.resultpanel = new Ext.grid.GridPanel({
                store: this.store,
                title: t("Search Results"),
                trackMouseOver: false,
                disableSelection: true,
                autoScroll: true,
                region: "center",
                columns: [{
                    text: t("ID"),
                    dataIndex: 'id',
                    flex: 20,
                    align: 'left',
                    sortable: true
                }, {
                    text: t("Icecat Product ID"),
                    dataIndex: 'icecat_productid',
                    flex: 30,
                    sortable: true,
                }, {
                    text: t("Product Title"),
                    dataIndex: 'producttitle',
                    flex: 120,
                    sortable: true,
                    renderer: function (s) {
                        return Ext.util.Format.htmlEncode(s);
                    }
                }, {
                    text: t("Product Code"),
                    dataIndex: 'productcode',
                    flex: 25,
                    sortable: true
                }, {
                    text: t("Brand"),
                    dataIndex: 'brand',
                    flex: 25,
                    sortable: true
                }, {
                    text: t("GTIN/EAN"),
                    dataIndex: 'gtin',
                    flex: 25,
                    sortable: true
                }, {
                    text: t("Product Family"),
                    dataIndex: 'productfamily',
                    flex: 25,
                    sortable: true
                }, {
                    text: t("Action"),
                    dataIndex: 'id',
                    flex: 25,
                    sortable: false,
                    renderer: function (value, p, record) {
                        if (value) {
                            return Ext.String.format('<a href="#" onclick="pimcore.helpers.openElement({0}, \'{1}\')">{2}</a>', value, 'object', '<img src="/bundles/pimcoreadmin/img/flat-color-icons/open_file.svg" class="x-action-col-icon x-action-col-0" data-qtip="Open ID" >');
                        }

                        return '';
                    },
                }],
                viewConfig: {
                    forceFit: true,
                    enableTextSelection: true
                },
                bbar: this.pagingToolbar
            });

            this.searchpanel = new Ext.FormPanel({
                region: "west",
                id: "search_form",
                title: t("log_search_form"),
                width: 465,
                height: 500,
                border: false,
                autoScroll: true,
                containerScroll: true,
                split: true,
                referenceHolder: true,
                defaultButton: 'log_search_button',
                buttons: [{
                    reference: 'log_search_button',
                    text: t("search"),
                    handler: this.find.bind(this),
                    iconCls: "pimcore_icon_search"
                }],
                items: [{
                    xtype: 'fieldset',
                    autoHeight: true,
                    labelWidth: 150,
                    items: [
                        {
                            xtype: 'combo',
                            name: 'language',
                            store: this.languagesStore,
                            fieldLabel: t('Language'),
                            width: 335,
                            listWidth: 150,
                            mode: 'local',
                            typeAhead: true,
                            forceSelection: true,
                            triggerAction: 'all',
                            displayField: 'value',
                            valueField: 'key',
                            listeners: {
                                'change': this.clearValues.bind(this)
                            }
                        }, {
                            xtype: 'combo',
                            name: 'category',
                            store: this.categoryStore,
                            fieldLabel: t('Category'),
                            width: 333,
                            listWidth: 150,
                            mode: 'local',
                            typeAhead: true,
                            forceSelection: true,
                            triggerAction: 'all',
                            displayField: 'name',
                            valueField: 'id',
                            listeners: {
                                'change': this.addComboFields.bind(this)
                            }
                        }, {
                            xtype: 'fieldcontainer',
                            layout: 'hbox',
                            combineErrors: true,
                            items: [{
                                xtype: 'tagfield',
                                name: 'brand[]',
                                store: this.brandStore,
                                multiselect: true,
                                fieldLabel: t('Brand'),
                                width: 333,
                                listWidth: 150,
                                mode: 'local',
                                typeAhead: true,
                                forceSelection: true,
                                triggerAction: 'all',
                                displayField: 'value',
                                valueField: 'key',
                                listeners: {
                                    'beforeselect': this.setBrandCheckbox.bind(this),
                                    'beforedeselect': this.unsetBrandCheckbox.bind(this)
                                }
                            }, {
                                xtype: 'checkbox',
                                name: "checkbox_brand",
                                tooltip: 'Select/Unselect all values',
                                width: 100,
                                style: 'margin-left: 5px',
                                handler: this.toggleBrandComboValues.bind(this)
                            }]
                        }, {
                            xtype: 'fieldcontainer',
                            layout: 'hbox',
                            combineErrors: true,
                            items: [{
                                xtype: 'checkbox',
                                name: "3dtour",
                                fieldLabel: t('3D tour'),
                                tooltip: '3D tour',
                                labelWidth: 110,
                                width: 150
                                
                            }, {
                                xtype: 'checkbox',
                                name: "video",
                                fieldLabel: t('Video'),
                                tooltip: 'Video',
                                labelWidth: 110,
                                width: 150
                                
                            }]
                        }, {
                            xtype: 'fieldcontainer',
                            layout: 'hbox',
                            combineErrors: true,
                            items: [{
                                xtype: 'checkbox',
                                name: "reviews",
                                fieldLabel: t('Reviews'),
                                tooltip: 'Reviews',
                                labelWidth: 110,
                                width: 150
                                
                            }, {
                                xtype: 'checkbox',
                                name: "reasonstobuy",
                                fieldLabel: t('Reasons to buy'),
                                tooltip: 'Reasons to buy',
                                labelWidth: 110,
                                width: 150
                                
                            }]
                        }, {
                            xtype: 'fieldcontainer',
                            layout: 'hbox',
                            combineErrors: true,
                            items: [{
                                xtype: 'checkbox',
                                name: "relatedproducts",
                                fieldLabel: t('Related Products'),
                                tooltip: 'Related Products',
                                labelWidth: 110,
                                width: 150
                                
                            }, {
                                xtype: 'checkbox',
                                name: "productstories",
                                fieldLabel: t('Product Stories'),
                                tooltip: 'Product Stories',
                                labelWidth: 110,
                                width: 150,
                            }]
                        }, {
                            xtype: 'fieldcontainer',
                            layout: 'hbox',
                            combineErrors: true,
                            items: [{
                                xtype: 'checkbox',
                                name: "multimedia",
                                fieldLabel: t('Multimedia'),
                                tooltip: 'Multimedia',
                                labelWidth: 110,
                                width: 150
                                
                            }]
                        } 
                    ]
                }]
            });

            var layout = new Ext.Panel({
                border: false,
                layout: "border",
                items: [this.searchpanel, this.resultpanel],
            });

            this.panel.add(layout);
            pimcore.layout.refresh();
        }
        return this.panel;
    },

    addComboFields: function (component, value) {
        if (!this.isLanguageSelected()) {
            return false;
        }
        this.store.removeAll();
        this.searchpanel.getForm().findField("brand[]").reset();
        this.brandStore.getProxy().setExtraParam("category", value);
        this.brandStore.getProxy().setExtraParam("language", this.searchpanel.getForm().findField("language").getValue());
        this.brandStore.reload();

        Ext.Ajax.request({
            url: Routing.generate('icecat_searchablefeatures_list'),
            params: {
                categoryID: value,
                brand: this.searchpanel.getForm().findField("brand[]").getValue(),
                language: this.searchpanel.getForm().findField("language").getValue()
            },
            method: 'GET',
            success: function (res) {
                var response = Ext.decode(res.responseText);
                if (response.success === false) {
                    return;
                }

                var fieldset = {
                    xtype: 'fieldset',
                    title: 'Category specific filters',
                    autoHeight: true,
                    labelWidth: 150,
                    items: []
                }

                if (response.data && response.data.featuresList !== undefined) {
                    response.data.featuresList.forEach(function (item) {
                        if (item.type == "quantityValue") {
                            fieldset.items.push({
                                xtype: 'fieldcontainer',
                                layout: 'hbox',
                                combineErrors: true,
                                items: [
                                    {
                                        xtype: 'tagfield',
                                        multiselect: true,
                                        width: 300,
                                        store: new Ext.data.JsonStore({
                                            autoDestroy: true,
                                            data: response.data.stores[item.id].values,
                                            proxy: {
                                                type: 'memory',
                                                reader: {
                                                    type: 'json',
                                                }
                                            },
                                            fields: ['value', 'key'],
                                        }),
                                        name: "feature_" + item.id + "[]",
                                        fieldLabel: item.title,
                                        mode: 'local',
                                        typeAhead: true,
                                        forceSelection: true,
                                        triggerAction: 'all',
                                        displayField: 'value',
                                        valueField: 'key',
                                        listeners: {
                                            'beforeselect': this.setCheckbox.bind(this),
                                            'beforedeselect': this.unsetCheckbox.bind(this)
                                        }
                                    },
                                    {
                                        xtype: 'combo',
                                        store: new Ext.data.JsonStore({
                                            autoDestroy: true,
                                            data: response.data.stores[item.id].units,
                                            proxy: {
                                                type: 'memory',
                                                reader: {
                                                    type: 'json',
                                                }
                                            },
                                            fields: ['longname', 'id'],
                                        }),
                                        name: "feature_unit_" + item.id,
                                        mode: 'local',
                                        width: 100,
                                        typeAhead: true,
                                        forceSelection: true,
                                        triggerAction: 'all',
                                        displayField: 'longname',
                                        valueField: 'id',
                                        value: response.data.stores[item.id].units[0] !== undefined ? response.data.stores[item.id].units[0].id : null,
                                        disabled: true
                                    }, {
                                        xtype: 'checkbox',
                                        name: "checkbox_" + item.id,
                                        tooltip: 'Select/Unselect all values',
                                        width: 100,
                                        style: 'margin-left: 5px',
                                        handler: this.toggleComboValues
                                    }
                                ]
                            });

                        } else {
                            fieldset.items.push({
                                xtype: 'fieldcontainer',
                                layout: 'hbox',
                                combineErrors: true,
                                items: [{
                                    xtype: 'tagfield',
                                    multiselect: true,
                                    width: 300,
                                    store: new Ext.data.JsonStore({
                                        autoDestroy: true,
                                        data: response.data.stores[item.id].values,
                                        proxy: {
                                            type: 'memory',
                                            reader: {
                                                type: 'json',
                                            }
                                        },
                                        fields: ['value', 'key'],
                                    }),
                                    name: "feature_" + item.id + "[]",
                                    fieldLabel: item.title,
                                    mode: 'local',
                                    typeAhead: true,
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    displayField: 'value',
                                    valueField: 'key',
                                    listeners: {
                                        'beforeselect': this.setCheckbox.bind(this),
                                        'beforedeselect': this.unsetCheckbox.bind(this)
                                    }
                                }, {
                                    xtype: 'checkbox',
                                    name: "checkbox_" + item.id,
                                    tooltip: 'Select/Unselect all values',
                                    width: 100,
                                    style: 'margin-left: 5px',
                                    handler: this.toggleComboValues
                                }]
                            });
                        }
                    }.bind(this));
                } else {
                    fieldset.html = "No searchable features available";
                }

                this.searchpanel.remove(1);
                this.searchpanel.add(fieldset);
                pimcore.layout.refresh();
            }.bind(this),
            failure: function (err) {
            }.bind(this)
        });
    },

    find: function () {
        if (!this.isLanguageSelected()) {
            return false;
        }
        var proxy = this.store.getProxy();
        var formValues = this.searchpanel.getForm().getFieldValues();
        proxy.extraParams = formValues;
        this.pagingToolbar.moveFirst();
    },

    clearValues: function (component, value) {

        this.categoryStore.getProxy().setExtraParam("language", value);
        this.categoryStore.reload();

        this.brandStore.getProxy().setExtraParam("language", value);
        this.brandStore.getProxy().setExtraParam("category", value);
        this.brandStore.reload();

        this.searchpanel.getForm().findField("brand[]").reset();
        this.searchpanel.getForm().findField("category").reset();

        this.searchpanel.remove(1);
        pimcore.layout.refresh();

        this.store.removeAll();
    },

    toggleComboValues: function (component, value) {
        var featureId = component.name.split('_')[1];
        var featureFieldName = `feature_${featureId}[]`;
        var combo = Ext.getCmp("search_form").getForm().findField(featureFieldName);
        if (combo) {
            if (value) {
                combo.select(combo.getStore().getRange());
            } else {
                combo.reset();
            }
        }
    },

    toggleBrandComboValues: function (component, value) {
        var combo = Ext.getCmp("search_form").getForm().findField('brand[]');
        if (combo) {
            if (value) {
                combo.select(combo.getStore().getRange());
            } else {
                combo.reset();
            }
        }
    },

    setCheckbox: function (component) {
        var featureIdWithBraces = component.name.split('_')[1];
        var featureId = featureIdWithBraces.split('[]')[0];
        var featureFieldName = `feature_${featureId}[]`;
        var combo = Ext.getCmp("search_form").getForm().findField(featureFieldName);

        // +1 because event is called before select
        if(combo.getValue().length+1 === combo.getStore().getRange().length) {
            var checkboxFieldName = `checkbox_${featureId}`;
            var checkbox = Ext.getCmp("search_form").getForm().findField(checkboxFieldName);
            if (checkbox) {
                checkbox.setRawValue(true);
            }
        }
    },

    unsetCheckbox: function (component) {
        var featureIdWithBraces = component.name.split('_')[1];
        var featureId = featureIdWithBraces.split('[]')[0];
        var checkboxFieldName = `checkbox_${featureId}`;
        var checkbox = Ext.getCmp("search_form").getForm().findField(checkboxFieldName);
        if (checkbox) {
            checkbox.setRawValue(false);
        }
    },

    setBrandCheckbox: function (component) {
        var combo = Ext.getCmp("search_form").getForm().findField('brand[]');
        
        // +1 because event is called before select
        if(combo.getValue().length+1 === this.brandStore.getRange().length) {
            var checkbox = Ext.getCmp("search_form").getForm().findField('checkbox_brand');
            if (checkbox) {
                checkbox.setRawValue(true);
            }
        }
    },

    unsetBrandCheckbox: function (component) {
        var checkbox = Ext.getCmp("search_form").getForm().findField('checkbox_brand');
        if (checkbox) {
            checkbox.setRawValue(false);
        }
    },

    isLanguageSelected: function () {
        var formValues = this.searchpanel.getForm().getFieldValues();
        if (formValues.language === null || formValues.language == "") {
            Ext.Msg.alert('Error', 'Please select a language');
            return false;
        }
        return true;
    }

});
