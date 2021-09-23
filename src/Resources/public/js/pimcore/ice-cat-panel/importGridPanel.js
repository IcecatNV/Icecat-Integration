pimcore.registerNS("pimcore.plugin.iceCatImportGridPanel");
pimcore.plugin.iceCatImportGridPanel = Class.create({
    initialize : function(parent) {
    },

    getPanel: function () {
        if (this.importGridPanel) {
            return this.importGridPanel;
        }
        Ext.Ajax.request({
            url: Routing.generate('icecat_grid-get-col-config'),
            success: function (response) {
              
                responseData = Ext.decode(response.responseText);
              
                this.createGrid(false, response)
            }.bind(this),
            failure: function (err) {
            }.bind(this)
        });
        this.importGridPanel = new Ext.Panel({
            id:         "iceCatBundle_importGridPanel",
            title:      "Import Grid",
            iconCls:    "pimcore_icon_table_tab",
            layout: "fit",
            closable: false,
            listeners: {beforeshow: function(){
                Ext.Ajax.request({
                    url: Routing.generate('icecat_grid-total-fetched-records'),
                    success: function (response) {
                      
                        responseData = Ext.decode(response.responseText);
                        if(responseData.job)
                        {
                            if(responseData.count == 0)
                            {   
                                Ext.get('productStatus').setHtml("<b>Status: " +responseData.count+  " product found,Please Restart process for using other file</b>");
                            }
                            else{
                                Ext.get('productStatus').setHtml("<b>Status: " +responseData.count+  " product found</b></span>");
                            }
                           
                        }else{
                            Ext.get('productStatus').setHtml("<b>Status: No job running </b></span>");
                        }
                    
                    }.bind(this),
                    failure: function (err) {
                    }.bind(this)
                });

            }},
           
        });
     
        return this.importGridPanel;
    },

    createGrid: function (fromConfig, response, settings, save, deletedColumns) {
        this.data = {};
        this.data.id= 'changeable1';
        this.data.createdBy = 'changeable ICECAT';
        this.sortinfo = {};
        this.sortinfo.field = 'id';
        this.sortinfo.direction = 'asc';
        this.fromClearFilter = false;
        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);
        var fields = [];
        if (response.responseText) {
            response = Ext.decode(response.responseText);
            if (response.pageSize) {
                itemsPerPage = response.pageSize;
            }

            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.sortinfo = response.sortinfo;
            this.settings = response.settings || {};
            this.availableConfigs = response.availableConfigs;
            this.sharedConfigs = response.sharedConfigs;
            if (response.onlyDirectChildren) {
                this.onlyDirectChildren = response.onlyDirectChildren;
            }
        } else {
           
            fields = response;
            this.settings = settings;
        }

        this.fields = fields;
        this.fieldObject = {};
        for (var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

//Remove filters and sortinfo if new fields added/removed
        if (fromConfig) {
            var savedColumns = Ext.decode(this.columnFilters);
            if (typeof (deletedColumns) !== 'undefined') {
                for (var i = 0; i < savedColumns.length; i++) {
                    if (deletedColumns.includes(savedColumns[i].property)) {
                        savedColumns.splice(i, 1);
                    }
                }
            }

            this.columnFilters = Ext.encode(savedColumns);
            this.sortinfo = '';
        }

        var plugins = ['pimcore.icGridfilters'];
        let objectTypeStores = pimcore.globalmanager.get("object_types_store");
        if (objectTypeStores.removed.length) {
            for (let i =0; i < objectTypeStores.removed.length; i++) {
                if (objectTypeStores.removed[i].id == this.classId) {
                    objectTypeStores.add(objectTypeStores.removed[i]);
                }
            }
        }
        // get current class
        var classStore = pimcore.globalmanager.get("object_types_store");
        var klass = classStore.getById(this.classId);
        var gridHelper = new pimcore.plugin.IceCatGrid(
            "IceCat",
            fields,
            "/admin/icecat/grid-proxy?classId=" + this.classId + "&objectId=" + this.data.id,
            {
                language: this.gridLanguage,
                columnConfig: Ext.encode(this.fields)
            },
            false,
            this.columnFilters,
            this.sortinfo
        );
        gridHelper.showSubtype = false;
        gridHelper.enableEditor = true;
        gridHelper.limit = itemsPerPage;


        //Filter Toolbar
        this.toolbarFilterInfo = new Ext.Button({
            iconCls: "pimcore_icon_filter_condition",
            hidden: true,
            text: '<b>' + t("filter_active") + '</b>',
            tooltip: t("filter_condition"),
            handler: function (button) {
                Ext.MessageBox.alert(t("filter_condition"), button.pimcore_filter_condition);
            }.bind(this)
        });

        //Clear filter button
        this.clearFilterButton = new Ext.Button({
            iconCls: "pimcore_icon_clear_filters",
            hidden: true,
            text: t("clear_filters"),
            tooltip: t("clear_filters"),
            handler: function (button) {

                this.columnFilters = '';
                this.store.filters.clear();
                this.grid.filters.clearFilters();
                this.toolbarFilterInfo.hide();
                this.clearFilterButton.hide();
                this.fromClearFilter = true;
                this.clearFilterButton.setHidden(true);
            }.bind(this)
        });
        //get store for data grid
        this.store = gridHelper.getStore(this.noBatchColumns, [], this);
      

        this.store.setPageSize(itemsPerPage);
        this.languageInfo = new Ext.Toolbar.TextItem({
            text: t("grid_current_language") + ": " + (this.gridLanguage == "default" ? t("default") : pimcore.available_languages[this.gridLanguage])
        });
        var gridColumns = gridHelper.getGridColumns();
        this.gridfilters = gridHelper.getGridFilters();
        this.helper = new window.top.pimcore.plugin.iceCatHelper();
        //Open Column config button
        this.createButton = new Ext.Button({
            id: "iceCat_create_object_button",
            text: t('Create Products'),
            iconCls: "pimcore_icon_table_col pimcore_icon_overlay_edit",
            cls: 'test',
             bodyPadding: 10,
            
            // disabled: (pimcore.globalmanager.get('iceCatData').objectCreationProcessExist || !pimcore.globalmanager.get('iceCatData').fetchingProcessExist),
            disabled: (pimcore.globalmanager.get('iceCatData').objectCreationProcessExist),
            handler: function (e) {
                let selectedRows = this.grid.getSelectionModel().getSelection();
                let ids = [];
                for (let i = 0; i < selectedRows.length; i++) {
                    ids.push(selectedRows[i].data.gtin);
                }

                let gtinsPage = this.grid.getStore().currentPage;

                this.helper.createObject(ids, gtinsPage);
                e.stopPropagation();
            }.bind(this)
        });

        this.runningProcessPanel = new Ext.Panel({
            id:         "iceCatBundle_runningProcessesPanel",
            // title:      "Running Processes",
            width: '100%',
            // height: 100,
            bodyPadding: 10,
            // hidden: true,
            border:     false,
            // iconCls:    "pimcore_icon_upload",
            // frame: true,
            region: 'center',
            listeners:{
                'afterrender':function(){
                }
            },
            style: {
                // 'margin-top':'40%'
                'clear': 'both',
                'float': 'left',
            },
            items: [
                {
                    xtype: 'fieldset',
                    title: 'Object Creation Progress',
                    id: 'processingProgressBarFieldset',
                    hidden: !pimcore.globalmanager.get('iceCatData').objectCreationProcessExist,
                    // items:
                    items: [],
                    listeners: {
                     
                        'afterrender': function () {

                            let ap = new pimcore.plugin.IceCatActiveCreationProcesses('', 'processing', 'processingProgressBarFieldset');
                            ap.refreshTask.start();
                           
                        }
                    }

                }
            ]
        });
        //createdBy and creationDate
        this.createdBy = '<b>Created By :</b> '+ this.data.createdBy;
        this.productStatus ="<span id = 'productStatus'><b>Status: No product found</b></span>";
      
        this.creationDate = '<b>Created Date : </b>'+ this.data.creationDate;

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        // return;
        //Create data grid
        this.grid = Ext.create('Ext.grid.Panel', {

            frame: false,
            store: this.store,
            columns: gridColumns,
            columnLines: true,
            stripeRows: true,
            bodyCls: "pimcore_editable_grid",
            border: true,
            trackMouseOver: true,
            loadMask: true,
            plugins: plugins,
            viewConfig: {
                forceFit: false,
                xtype: 'patchedgridview'
            },
            listeners: {
                // // beforeshow: function(e){

                // //     alert("hello");
                // // },
                
                // afterrender: function(grid){
                   
                  
                //     var store = grid.getStore();
                //     if (store.isLoaded()) {
                //       console.log('Store loaded ');
                //       console.log(store.getCount());
                //       Ext.get('productStatus').setHtml("<b>Status:" +store.getCount()+  "product found</b></span>");
                //     }
                // }.bind(this),
            },
            selModel: gridHelper.getSelectionColumn(),

            cls: 'pimcore_object_grid_panel',
            // tbar: [
            //     this.createdBy,
            //     this.creationDate,
            //     this.toolbarFilterInfo,
            //     this.clearFilterButton, "->",
            //     this.createButton,
            //     this.runningProcessPanel
            // ],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'top',
                    
                    items: [
                        // this.createdBy,
                        // this.creationDate,
                        this.productStatus,
                        this.toolbarFilterInfo,
                        this.clearFilterButton, "->",
                        this.createButton,
                    ]
                },
              
                {
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [
                        this.runningProcessPanel
                    ]
                }
            ],
        });
        this.grid.on('selectionchange',function(grid, selection, eOpts){
             createObjectButton =  Ext.getCmp('iceCat_create_object_button');
             buttonText = Ext.getCmp('iceCat_create_object_button-btnInnerEl');
          if((!selection.length)){
           
           
            createObjectButton.removeCls("test2");
          }else{
        
            
             createObjectButton.addCls("test2");
             buttonText.setStyle("color","white");
           
          }
        });
        this.grid.on("sortchange", function (ct, column, direction, eOpts) {
            this.sortinfo = {
                field: column.dataIndex,
                direction: direction
            };
        }.bind(this));
        // check for filter updates
        this.grid.on("filterchange", function () {
            // this.filterUpdateFunction(this.grid, this.toolbarFilterInfo, this.clearFilterButton);
        }.bind(this));
        //show filter toolbars when added saved filters
        this.grid.on("viewready", function () {
            this.filterUpdateFunction(this.grid, this.toolbarFilterInfo, this.clearFilterButton);
        }.bind(this));
        this.grid.on("rowcontextmenu", this.onRowContextmenu);
        gridHelper.applyGridEvents(this.grid);
        // this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: itemsPerPage});
        this.editor = new Ext.Panel({
            layout: "border",
            items: [
                new Ext.Panel({
                id: 'icecat_data_grid',
                items: [this.grid],
                region: "center",
                layout: "fit",
                bbar: this.pagingtoolbar
            })
            ]
        });
        this.importGridPanel.removeAll();
        this.importGridPanel.add(this.editor);
        this.importGridPanel.updateLayout();
    },
    filterUpdateFunction: function (grid, toolbarFilterInfo, clearFilterButton) {
        var filterStringConfig = [];
        var filterData = grid.getStore().getFilters().items;
        // reset
        toolbarFilterInfo.setTooltip(" ");
        if (filterData.length > 0) {

            for (var i = 0; i < filterData.length; i++) {

                var operator = filterData[i].getOperator();
                if (operator == 'lt') {
                    operator = "&lt;";
                } else if (operator == 'gt') {
                    operator = "&gt;";
                } else if (operator == 'eq') {
                    operator = "=";
                }

                var value = filterData[i].getValue();
                if (value instanceof Date) {
                    value = Ext.Date.format(value, "Y-m-d");
                }

                if (value && typeof value == "object") {
                    filterStringConfig.push(filterData[i].config.colLabel + " " + operator + " ("
                        + value.join(" OR ") + ")");
                } else {
                    filterStringConfig.push(filterData[i].config.colLabel + " " + operator + " " + value);
                }
            }

            var filterCondition = filterStringConfig.join(" AND ") + "</b>";
            toolbarFilterInfo.setTooltip("<b>" + t("filter_condition") + ": " + filterCondition);
            toolbarFilterInfo.pimcore_filter_condition = filterCondition;
            toolbarFilterInfo.setHidden(false);
        }
        toolbarFilterInfo.setHidden(filterData.length == 0);
        clearFilterButton.setHidden(!toolbarFilterInfo.isVisible());
    },
    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts) {

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);
        if (typeof data.data.o_id !== "undefined") {
            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    pimcore.helpers.openObject(data.data.o_id, "object");
                }.bind(this, data)
            }));
        }

        pimcore.plugin.broker.fireEvent("prepareOnRowContextmenu", menu, this);
        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    }

});