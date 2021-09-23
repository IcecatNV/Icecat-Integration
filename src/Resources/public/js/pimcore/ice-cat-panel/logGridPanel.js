pimcore.registerNS("pimcore.plugin.iceCatLogGridPanel.js");
pimcore.plugin.iceCatLogGridPanel = Class.create({
    initialize : function(parent) {
    },

    getPanel: function () {
        if (this.logGridPanel) {
            return this.logGridPanel;
        }

          Ext.Ajax.request({
            url: Routing.generate('icecat_grid-get-col-config'),
            success: function (response) {
               
                responseData = Ext.decode(response.responseText);
              
                this.createGrid()
            }.bind(this),
            failure: function (err) {
            }.bind(this)
        });

        this.logGridPanel = new Ext.Panel({
            id:         "iceCatBundle_logGridPanel",
            title:      "Log Grid",
            iconCls:    "pimcore_icon_table_tab",
            layout: "fit",
            closable: false,
             listeners: {beforeshow: function(e){

                Ext.getCmp('loggridid').getStore().reload();
                
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
              url: Routing.generate('icecat_loggrid_data'),
              reader: {
                  type: 'json',
                  rootProperty: 'data'
              }
          },
          autoLoad: true
      });
      

      this.grid =  Ext.create('Ext.grid.Panel', {
            title: 'Log Files',
            store: Ext.data.StoreManager.lookup('simpsonsStore'),
            id: 'loggridid',
            columns: [
                { text: 'Time', dataIndex: 'name', width:250, },
                       
                {
                    xtype:'actioncolumn',
                    text: 'Download Log File',
                    width:250,
                    items: [{
                        iconCls: 'x-fa fa-download',
                        tooltip: 'Download',
                        handler: function(grid, rowIndex, colIndex) {
                        var rec = grid.getStore().getAt(rowIndex);
                         pimcore.settings.showCloseConfirmation = false;
                          window.setTimeout(function () {
                              pimcore.settings.showCloseConfirmation = true;
                          }, 1000);
                           window.location.href = Routing.generate('ice_cat_download_log',{name :rec.get('name')});
                        }
                    },
                  ]
                },      
                
                {
                    xtype:'actioncolumn',
                    text: 'Delete Log',
                    width:250,
                    items: [{
                        iconCls: 'x-fa fa-trash',
                        tooltip: 'Delete',
                        handler: function(grid, rowIndex, colIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            Ext.Ajax.request({
                                    url: Routing.generate('deletelogfile',{name :rec.get('name')}),
                                   //data:{'name':rec.get('name')},
                                    success: function (response) {
                                    
                                        
                                        grid.getStore().removeAt(rowIndex);
                                     
                                    }.bind(this),
                                    failure: function (err) {
                                    }.bind(this)
                                });
                        }
                    },
                    
                  ],
                },
                  
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