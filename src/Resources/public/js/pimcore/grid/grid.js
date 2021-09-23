
pimcore.registerNS("pimcore.plugin.IceCatGrid");
pimcore.plugin.IceCatGrid = Class.create({

    baseParams: {},
    showSubtype: true,
    showKey: true,
    enableEditor: false,

    initialize: function (selectedClass, fields, url, baseParams, isSearch, savedFilters, sortinfo) {
        this.selectedClass = selectedClass;
        this.fields = fields;
        this.isSearch = isSearch;
        this.savedFilters = savedFilters;
        this.sortinfo = sortinfo;
        this.previousPage=1;

        this.url = url;
        if (baseParams) {
            this.baseParams = baseParams;
        } else {
            this.baseParams = {};
        }

        if (!this.baseParams["class"]) {
            this.baseParams["class"] = this.selectedClass;
        }

        var fieldParam = [];
        for (var i = 0; i < fields.length; i++) {
            fieldParam.push(fields[i].key);
        }

        this.baseParams['fields[]'] = fieldParam;
    },

    getStore: function (noBatchColumns, batchAppendColumns, _self) {

        batchAppendColumns = batchAppendColumns || [];
        // the store
        var readerFields = [];
        readerFields.push({name: "oo_id", allowBlank: true});
        readerFields.push({name: "idPath", allowBlank: true});
        readerFields.push({name: "fullpath", allowBlank: true});
        readerFields.push({name: "published", allowBlank: true});
        readerFields.push({name: "type", allowBlank: true});
        readerFields.push({name: "subtype", allowBlank: true});
        readerFields.push({name: "filename", allowBlank: true});
        readerFields.push({name: "classname", allowBlank: true});
        readerFields.push({name: "creationDate", allowBlank: true, type: 'date', dateFormat: 'timestamp'});
        readerFields.push({name: "modificationDate", allowBlank: true, type: 'date', dateFormat: 'timestamp'});
        readerFields.push({name: "inheritedFields", allowBlank: false});
        readerFields.push({name: "metadata", allowBlank: true});
        readerFields.push({name: "#kv-tr", allowBlank: true});

        this.noBatchColumns = [];
        this.batchAppendColumns = [];

        // for (var i = 0; i < this.fields.length; i++) {
        //     if (!in_array(this.fields[i].key, ["creationDate", "modificationDate"])) {
        //
        //         var fieldConfig = this.fields[i];
        //         var type = fieldConfig.type;
        //         var key = fieldConfig.key;
        //         var readerFieldConfig = {name: key, allowBlank: true};
        //         // dynamic select returns data + options on cell level
        //         if ((type == "select" || type == "multiselect") && fieldConfig.layout.optionsProviderClass) {
        //             if (typeof noBatchColumns != "undefined") {
        //                 if (fieldConfig.layout.dynamicOptions) {
        //                     noBatchColumns.push(key);
        //                 }
        //             }
        //
        //             if (type == "select") {
        //                 readerFieldConfig["convert"] = function (key, v, rec) {
        //                     if (v && typeof v.options !== "undefined") {
        //                         // split it up and store the options in a separate field
        //                         rec.set(key + "%options", v.options, {convert: false, dirty: false});
        //                         return v.value;
        //                     }
        //                     return v;
        //                 }.bind(this, key);
        //                 var readerFieldConfigOptions = {name: key + "%options", allowBlank: true, persist: false};
        //             }
        //
        //             readerFields.push(readerFieldConfigOptions);
        //         }
        //
        //         if (pimcore.object.tags[type] && pimcore.object.tags[type].prototype.allowBatchAppend) {
        //             batchAppendColumns.push(key);
        //         }
        //
        //         readerFields.push(readerFieldConfig);
        //     }
        // }

        var glue = '&';
        if (this.url.indexOf('?') === -1) {
            glue = '?';
        }
        var random = Math.floor((Math.random() * 10) + 1);
        var proxy = {
            type: 'ajax',
            url: this.url,
            noCache :true,
            reader: {
                type: 'json',
                totalProperty: 'total',
                successProperty: 'success',
                rootProperty: 'data'
            },
            api: {
                create: this.url + glue + "xaction=create&"+random,
                read: this.url + glue + "xaction=read&"+random,
                update: this.url + glue + "xaction=update&"+random,
                destroy: this.url + glue + "xaction=destroy&"+random
            },
            actionMethods: {
                create: 'POST',
                read: 'POST',
                update: 'POST',
                destroy: 'POST'
            },
            listeners: {
                exception: function (proxy, request, operation, eOpts) {
                }.bind(this)
            },
            extraParams: this.baseParams
        };

        var writer = null;
        var listeners = {
            beforeload: function (store, operation, eOpts) {
                
                let ids = [];
                let selectedRows = this.grid.getSelectionModel().getSelection();
                for (let i = 0; i < selectedRows.length; i++) {
                    ids.push(selectedRows[i].data.gtin);
                }

                if (ids.length > 0) {
                    store.proxy.extraParams.gtins = ids.join(',');
                } else {
                    store.proxy.extraParams.gtins = '';

                }
                store.proxy.extraParams.gtinsPage = this.previousPage;
                this.previousPage = store.currentPage
            }.bind(_self),
            load: function (store, operation, eOpts) {
                let selModel = this.grid.getSelectionModel();
                pimcore.globalmanager.add('productCount',store.getCount());
              
                items = [];
                store.each(function (item, index) {
                   // console.log(item);
                    if (!!+item.data.sel) {
                        items.push(item);

                       // console.log(index);
                    }
                })
                // item.set("selected", !!+item.data.sel, {dirty: true})
                selModel.select(items);
                // console.log({store:store});
                // console.log({storeData:store.getData()});
                // console.log({storeData:store.getData()});

            }.bind(_self)
        };
        if (this.enableEditor) {
            proxy.writer = {
                type: 'json',
                //writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            };
        }

        var sortConfig = [];

        if (typeof this.sortinfo.field !== "undefined") {
            sortConfig = [{property: this.sortinfo.field, direction: this.sortinfo.direction}];
        }

        if (this.savedFilters != '') {
            // var savedFilters = Ext.decode(this.savedFilters);
        }

        this.store = new Ext.data.Store({
            remoteSort: true,
            remoteFilter: true,
            listeners: listeners,
            autoDestroy: true,
            clearOnPageLoad:true,
            fields: readerFields,
            proxy: proxy,
            autoSync: true,
            // filters: savedFilters,
            filters: {},
            sorters: sortConfig
        });


        return this.store;

    },

    selectionColumn: null,
    getSelectionColumn: function () {
        if (this.selectionColumn == null) {
            this.selectionColumn = Ext.create('Ext.selection.CheckboxModel', {});
        }
        return this.selectionColumn;
    },

    getGridColumns: function () {
        // get current class
        var classStore = pimcore.globalmanager.get("object_types_store");
        var klassIndex = classStore.findExact("text", this.selectedClass);
        var klass = classStore.getAt(klassIndex);
        // var propertyVisibility = klass.get("propertyVisibility");
        //
        // if (this.isSearch) {
        //     propertyVisibility = propertyVisibility.search;
        // } else {
        //     propertyVisibility = propertyVisibility.grid;
        // }
        // var showKey = propertyVisibility.path;
        let propertyVisibility = true;
        let showKey = true;
        if (this.showKey) {
            showKey = true;
        }

        // init grid-columns
        var gridColumns = [];

        var gridFilters = this.getGridFilters();

        var fields = this.fields;
        
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];

            if (field.key == "subtype") {
                
                gridColumns.push({text: t("type"), width: this.getColumnWidth(field, 40), sortable: true, dataIndex: 'subtype',
                    hidden: !this.showSubtype,
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<div style="height: 16px;" class="pimcore_icon_asset  pimcore_icon_'
                            + value + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                    }});
            } else if (field.key == "oo_id") {
                gridColumns.push({text: 'ID', width: this.getColumnWidth(field, this.getColumnWidth(field, 40)), sortable: true,
                    dataIndex: 'oo_id', filter: 'numeric'});
            } else if (field.key == "is_product_found") {
                // gridColumns.push({text: 'Found', width: this.getColumnWidth(field, this.getColumnWidth(field, 40)), sortable: true,
                //     dataIndex: 'is_product_found', filter: 'numeric'});
            } else if (field.key == "gtin") {
                gridColumns.push({text: "Icecat Id", width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'gtin', filter: "string"}); }
            else if (field.key == "original_gtin") {
                        gridColumns.push({text: "GTIN", width: this.getColumnWidth(field, 200), sortable: true,
                            dataIndex: 'original_gtin', filter: "string"}); }
            else if (field.key == "product_name") {
                gridColumns.push({text: "Product Name", width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'product_name', filter: "string"});
            } else if (field.key == "fetching_date") {
                // gridColumns.push({text: "Fetching Date (System)", width: this.getColumnWidth(field, 200), sortable: true,
                //     dataIndex: "fetching_date", filter: 'date', editable: false, renderer: function (d) {
                //         return Ext.Date.format(d, "Y-m-d H:i:s");
                //     }/*, hidden: !propertyVisibility.creationDate*/});
            } else if (field.key == "published") {
                gridColumns.push(new Ext.grid.column.Check({
                    text: t("published"),
                    width: 40,
                    sortable: true,
                    filter: 'boolean',
                    dataIndex: "published",
                    disabled: this.isSearch
                }));
            } else if (field.key == "fullpath") {
                gridColumns.push({text: t("path"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'fullpath', filter: "string"});
            } else if (field.key == "filename") {
                gridColumns.push({text: t("filename"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'filename', hidden: !showKey});
            } else if (field.key == "key") {
                gridColumns.push({text: t("key"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'key', hidden: !showKey, filter: 'string'});
            } else if (field.key == "classname") {
                gridColumns.push({text: t("class"), width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: 'classname', renderer: function (v) {
                        return ts(v);
                    }/*, hidden: true*/});
            } else if (field.key == "creationDate") {
                gridColumns.push({text: t("creationdate") + " (System)", width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: "creationDate", filter: 'date', editable: false, renderer: function (d) {
                        return Ext.Date.format(d, "Y-m-d H:i:s");
                    }/*, hidden: !propertyVisibility.creationDate*/});
            } else if (field.key == "modificationDate") {
                gridColumns.push({text: t("modificationdate") + " (System)", width: this.getColumnWidth(field, 200), sortable: true,
                    dataIndex: "modificationDate", filter: 'date', editable: false, renderer: function (d) {

                        return Ext.Date.format(d, "Y-m-d H:i:s");
                    }});
            } else {
                if (fields[i].isOperator) {
                    var operatorColumnConfig = {text: field.attributes.label ? field.attributes.label : field.attributes.key, width: 200, sortable: false,
                        dataIndex: fields[i].key, editable: false};

                    if (field.attributes.renderer && pimcore.object.tags[field.attributes.renderer]) {
                        var tag = new pimcore.object.tags[field.attributes.renderer]({}, {});
                        var fc = tag.getGridColumnConfig({
                            key: field.attributes.key
                        });
                        operatorColumnConfig["renderer"] = fc.renderer;
                    }


                    operatorColumnConfig.getEditor = function () {
                        return new pimcore.object.helpers.gridCellEditor({
                            fieldInfo: {
                                layout: {
                                    noteditable: true
                                }
                            }
                        });
                    }.bind(this);

                    gridColumns.push(operatorColumnConfig);

                } else {
                    var inputtype = ['image', 'country', "manyToManyObjectRelation", 'href', 'multihrefMetadata'];
                    //change type from country to input
                    if (inputtype.includes(fields[i].type)) {
                        var fieldType = 'input';
                    } else {
                        var fieldType = fields[i].type;
                    }


                    var tag = pimcore.object.tags[fieldType];
                    if (tag) {

                        var fc = tag.prototype.getGridColumnConfig(field);

                        fc.width = this.getColumnWidth(field, 100);

                        if (typeof gridFilters[field.key] !== 'undefined') {
                            fc.filter = gridFilters[field.key];
                        }

                        if (this.isSearch) {
                            fc.sortable = false;
                        }

                        gridColumns.push(fc);
                        gridColumns[gridColumns.length - 1].hidden = false;
                        gridColumns[gridColumns.length - 1].layout = fields[i];
                    } else {
                        console.log("could not resolve field type: " + fieldType);
                    }
                }
            }
        }

        return gridColumns;
    },

    getColumnWidth: function (field, defaultValue) {
        if (field.width) {
            return field.width;
        } else if (field.layout && field.layout.width) {
            return field.layout.width;
        } else {
            return defaultValue;
        }
    },

    getGridFilters: function () {
        var configuredFilters = {
            filter: "string",
            creationDate: "date",
            modificationDate: "date"
        };

        var fields = this.fields;
        for (var i = 0; i < fields.length; i++) {

            if (fields[i].key != "oo_id" && fields[i].key != "published"
                && fields[i].key != "filename" && fields[i].key != "classname"
                && fields[i].key != "creationDate" && fields[i].key != "modificationDate") {

                if (fields[i].key == "fullpath") {
                    configuredFilters.fullpath = {
                        type: "string"
                    };
                } else {
                    if (fields[i].isOperator) {
                        continue;
                    }

                    var fieldType = fields[i].type;
                    var tag = pimcore.object.tags[fieldType];
                    if (tag) {
                        var filter = tag.prototype.getGridColumnFilter(fields[i]);
                        if (filter) {
                            configuredFilters[filter.dataIndex] = filter;
                        }
                    } else {
                        console.log("could not resolve fieldType: " + fieldType);

                    }
                }
            }

        }


        return configuredFilters;

    },

    applyGridEvents: function (grid) {
        var fields = this.fields;
        for (var i = 0; i < fields.length; i++) {

            if (fields[i].isOperator) {
                continue;
            }

            if (fields[i].key != "oo_id" && fields[i].key != "published" && fields[i].key != "fullpath"
                && fields[i].key != "filename" && fields[i].key != "classname"
                && fields[i].key != "creationDate" && fields[i].key != "modificationDate") {

                var fieldType = fields[i].type;
                var tag = pimcore.object.tags[fieldType];
                if (tag) {
                    tag.prototype.applyGridEvents(grid, fields[i]);
                } else {
                    console.log("could not resolve field type " + fieldType);
                }
            }

        }
    }

});
