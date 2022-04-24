pimcore.registerNS("pimcore.plugin.iceCatUploadFilePanel");
pimcore.plugin.iceCatUploadFilePanel = Class.create({
    intervalObj: '',
    initialize: function () {
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

        if (this.uploadFilePanel) {
            return this.uploadFilePanel;
        }
        let item = {};
        item.id = 1;
        let __self = this;
        this.uploadFilePanel = Ext.create('Ext.form.Panel', {
            id: "iceCatBundle_uploadFilePanel",
            title: t('Add File'),
            width: 800,
            height: 100,
            bodyPadding: 10,
            hidden: true,
            border: false,
            iconCls: "pimcore_icon_upload",
            frame: true,
            listeners: {
                'afterrender': function () {
                    let ap = new pimcore.plugin.IceCatActiveProcesses('', 'fetching');
                    if (ap.refreshTask != 'undefined')
                        ap.refreshTask.start();

                }
            },
            style: {
                // 'margin-top':'40%'
            },
            items: [


                {
                    xtype: "tagfield",
                    required: true,
                    id: "system_settings_general_languageSelectionIcecat",
                    fieldLabel: 'Select Language',
                    labelWidth: 100,
                    triggerAction: 'all',
                    queryMode: 'local',
                    store: this.languagesStore,
                    displayField: 'display_value',
                    valueField: 'key',
                    value: this.languagesStore ? this.languagesStore.getAt(0) : '',
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
                        'afterrender': function () {

                            //  this.setValue("agq");
                            //  this.getData();
                        }
                    }
                },
                {
                    xtype: 'button',
                    id: 'ice_cat_process_restart_button',
                    text: 'Restart Process',
                    width: '15%',
                    style: "float:right",
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



                },
                {
                    xtype: 'tbspacer',
                    height: 20
                },

                {


                    xtype: 'fieldset',
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
                    height: 20
                },
                {
                    xtype: 'fieldset',
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
                    height: 40
                },
                {
                    xtype: 'fieldset',
                    title: 'Data Import Progress',
                    id: 'fetchProgressBarFieldset',
                    hidden: !pimcore.globalmanager.get('iceCatData').fetchingProcessExist,
                    // items:
                    items: []

                },

            ]
        });

        return this.uploadFilePanel;
    },

    clearPBarInterval: function () {
        if (this.intervalObj) {
            clearInterval(this.intervalObj);
        }
    }
});

new pimcore.plugin.iceCatUploadFilePanel();