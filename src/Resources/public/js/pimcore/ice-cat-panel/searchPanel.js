pimcore.registerNS("pimcore.plugin.iceCatSearchPanel");
pimcore.plugin.iceCatSearchPanel = Class.create({
    icon: "pimcore_icon_search",
    initialize: function (uploadPanel) {
        this.uploadPanel = uploadPanel;
        this.selectedLanguages = [];
        
        if(this.uploadPanel.getConfigData() && this.uploadPanel.getConfigData().selectedLanguages !== undefined) {
            this.uploadPanel.getConfigData().selectedLanguages.forEach(function(v) {
                this.selectedLanguages.push({'key':v, 'value': v});
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
        if(!this.panel) {

            var panelConfig = {
                border: false,
                layout: "fit",
                iconCls: "pimcore_icon_search",
            };

            panelConfig.title = t("Search By Category");
            panelConfig.id =  "iceCatBundle_searchPanel";
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
                trackMouseOver:false,
                disableSelection:true,
                autoScroll: true,
                region: "center",
                columns:[{
                    text: t("ID"),
                    dataIndex: 'id',
                    flex: 20,
                    align: 'left',
                    sortable: true
                },{
                    text: t("Icecat Product ID"),
                    dataIndex: 'icecat_productid',
                    flex: 30,
                    sortable: true,
                },{
                    text: t("Product Title"),
                    dataIndex: 'producttitle',
                    flex: 120,
                    sortable: true,
                    renderer: function (s) {
                        return Ext.util.Format.htmlEncode(s);
                    }
                },{
                    text: t("Product Code"),
                    dataIndex: 'productcode',
                    flex: 25,
                    sortable: true
                },{
                    text: t("Brand"),
                    dataIndex: 'brand',
                    flex: 25,
                    sortable: true
                },{
                    text: t("GTIN/EAN"),
                    dataIndex: 'gtin',
                    flex: 25,
                    sortable: true
                },{
                    text: t("Product Family"),
                    dataIndex: 'productfamily',
                    flex: 25,
                    sortable: true
                },{
                    text: t("Action"),
                    dataIndex: 'id',
                    flex: 25,
                    sortable: false,
                    renderer: function (value, p, record) {
                        if (value) {
                            return Ext.String.format('<a href="#" onclick="pimcore.helpers.openElement({0}, \'{1}\')">{2}</a>', value, 'object', 'Open');
                        }

                        return '';
                    },
                }],
                viewConfig: {
                    forceFit:true,
                    enableTextSelection: true
                },
                bbar: this.pagingToolbar
            });      

            this.searchpanel = new Ext.FormPanel({
                region: "east",
                title: t("log_search_form"),
                width: 450,
                height: 500,
                collapsible: true,
                border: false,
                autoScroll: true,
                referenceHolder: true,
                defaultButton: 'log_search_button',
                buttons: [{
                    reference: 'log_search_button',
                    text: t("search"),
                    handler: this.find.bind(this),
                    iconCls: "pimcore_icon_search"
                }],
                items: [ {
                    xtype:'fieldset',
                    autoHeight:true,
                    labelWidth: 150,
                    items :[
                        {
                            xtype:'combo',
                            name: 'language',
                            store: this.languagesStore,
                            fieldLabel: t('Language'),
                            width: 335,
                            listWidth: 150,
                            mode: 'local',
                            typeAhead:true,
                            forceSelection: true,
                            triggerAction: 'all',
                            displayField: 'value',
                            valueField: 'key',
                            listeners: {
                                'change': this.clearValues.bind(this)
                            }
                        },{
                            xtype:'combo',
                            name: 'category',
                            store: this.categoryStore,
                            fieldLabel: t('Category'),
                            width: 333,
                            listWidth: 150,
                            mode: 'local',
                            typeAhead:true,
                            forceSelection: true,
                            triggerAction: 'all',
                            displayField: 'name',
                            valueField: 'id',
                            listeners: {
                                'change': this.addComboFields.bind(this)
                            }
                        }]
                }]});

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


    clearValues: function(component, value) {
        this.categoryStore.getProxy().setExtraParam("language", value);
        this.categoryStore.reload();

        this.searchpanel.getForm().findField("category").reset();
        this.searchpanel.remove(1);
        pimcore.layout.refresh();
    },

    addComboFields: function(component, value) {

        Ext.Ajax.request({
            url: Routing.generate('icecat_searchablefeatures_list'),
            params: {
                categoryID: value,
                language: this.searchpanel.getForm().findField("language").getValue()
            },
            method: 'GET',
            success: function (res) {
                var response = Ext.decode(res.responseText);
                if(response.success === false) {
                    return;
                }

                var fieldset = {
                    xtype:'fieldset',
                    title: 'Category specific filters',
                    autoHeight:true,
                    labelWidth: 150,
                    items :[]
                }

                if(response.data && response.data.featuresList !== undefined) {
                    response.data.featuresList.forEach(function(item) {

                        if(item.type == "quantityValue") {
                            fieldset.items.push({
                                xtype: 'fieldcontainer',
                                layout: 'hbox',
                                combineErrors: true,
                                items: [
                                    {
                                        xtype:'tagfield',
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
                                        name: "feature_"+item.id+"[]",
                                        fieldLabel: item.title,
                                        mode: 'local',
                                        typeAhead:true,
                                        forceSelection: true,
                                        triggerAction: 'all',
                                        displayField: 'value',
                                        valueField: 'key',
                                    },
                                    {
                                        xtype:'combo',
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
                                        name: "feature_unit_"+item.id,
                                        mode: 'local',
                                        width: 100,
                                        typeAhead:true,
                                        forceSelection: true,
                                        triggerAction: 'all',
                                        displayField: 'longname',
                                        valueField: 'id',
                                    }
                                ]
                            });
                            
                        } else {
                            fieldset.items.push({
                                xtype:'tagfield',
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
                                name: "feature_"+item.id+"[]",
                                fieldLabel: item.title,
                                mode: 'local',
                                typeAhead:true,
                                forceSelection: true,
                                triggerAction: 'all',
                                displayField: 'value',
                                valueField: 'key',
                            });
                        }
                    });
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

    find: function() {
        var formValues = this.searchpanel.getForm().getFieldValues();
        console.log(formValues.language);
        if(formValues.language === null) {
            Ext.Msg.alert('Error', 'Please select a language');
            return false;
        }

        var proxy = this.store.getProxy();
        proxy.extraParams = formValues;
        this.pagingToolbar.moveFirst();
    }
});
