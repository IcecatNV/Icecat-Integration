pimcore.registerNS("pimcore.plugin.unfetchedProductGrid.js");
pimcore.plugin.unfetchedProductGrid = Class.create({
    initialize : function(parent) {
    },

    getPanel: function () {
        if (this.unfetchedProductGrid) {
            return this.unfetchedProductGrid;
        }

          Ext.Ajax.request({
            url: Routing.generate('icecat_grid_get_unfound_products_info'),
            success: function (response) {
               
                responseData = Ext.decode(response.responseText);
              
                this.createGrid()
            }.bind(this),
            failure: function (err) {
            }.bind(this)
        });

        this.logGridPanel = new Ext.Panel({
            id:         "iceCatBundle_unfetchedProductGrid",
            title:      "Products not found",
            iconCls:    "pimcore_icon_table_tab",
            layout: "fit",
            closable: false,
             listeners: {beforeshow: function(e){

                Ext.getCmp('productgridid').getStore().reload();
              
            }},
           

        });
      
        return this.logGridPanel;
    },

    createGrid: function () {
       
     

         this.myStore = Ext.create('Ext.data.Store', {
           storeId: 'simpsonsStore',
            fields:[ 'fileName', 'fileUrl'],
              proxy: {
              type: 'ajax',
              url: Routing.generate('icecat_grid_get_unfound_products_info'),
              reader: {
                  type: 'json',
                  rootProperty: 'product'
              }
          },
          autoLoad: true
      });
      

      this.grid =  Ext.create('Ext.grid.Panel', {
            title: 'Products that are not found',
            store: Ext.data.StoreManager.lookup('simpsonsStore'),
            id: 'productgridid',
            columns: [
                { text: 'Row number in uploaded file', dataIndex: 'rowNumber', width:250, },
                       
                { text: 'Message', dataIndex: 'message', width:250, },
                { text: 'Searched Key', dataIndex: 'searchKey', width:250, },
                // {
                //     xtype:'actioncolumn',
                //     text: 'Searched By',
                //     width:250,
                //     items: [{
                //         iconCls: 'x-fa fa-trash',
                //         tooltip: 'Delete',
                //         handler: function(grid, rowIndex, colIndex) {
                //             var rec = grid.getStore().getAt(rowIndex);
                //             Ext.Ajax.request({
                //                     url: Routing.generate('deletelogfile',{name :rec.get('name')}),
                //                    //data:{'name':rec.get('name')},
                //                     success: function (response) {
                                    
                                        
                //                         grid.getStore().removeAt(rowIndex);
                                     
                //                     }.bind(this),
                //                     failure: function (err) {
                //                     }.bind(this)
                //                 });
                //         }
                //     },
                    
                //   ],
                // },
                  
        ],
            height: 200,
            width: 200,
         
        });
       this.editor = new Ext.Panel({
            layout: "border",
            items: [new Ext.Panel({
                items: [this.grid],
                region: "center",
                layout: "fit",
              
            })
            ]
        });
   

        this.logGridPanel.removeAll();
        this.logGridPanel.add(this.editor);
        this.logGridPanel.updateLayout();
    },
   
    
});