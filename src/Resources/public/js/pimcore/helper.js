pimcore.registerNS("pimcore.plugin.iceCatHelper");
pimcore.plugin.iceCatHelper = Class.create({
    initialize: function (parent) {
        this.uploadPanel = new pimcore.plugin.iceCatUploadFilePanel();
        this.importGridPanel = new pimcore.plugin.iceCatImportGridPanel();
        this.unfetchedProductGrid = new pimcore.plugin.unfetchedProductGrid();
        this.applicationLogGridPanel =
            new pimcore.plugin.iceCatApplicationLogGridPanel();
        this.searchPanel = new pimcore.plugin.iceCatSearchPanel(
            this.uploadPanel
        );
    },
    addFileTabIndex: 1,
    loginTabIndex: 0,
    intervalObj: "",

    showPanels: function () {
        let ufp = Ext.getCmp("pimcore_iceCat_tabPanel");
        if (!ufp.child("#iceCatBundle_uploadFilePanel")) {
            ufp.add(this.uploadPanel.getPanel());
        }
        if (!ufp.child("#iceCatBundle_importGridPanel")) {
            ufp.add(this.importGridPanel.getPanel());
        }

        if (!ufp.child("#iceCatBundle_unfetchedProductGrid")) {
            ufp.add(this.unfetchedProductGrid.getPanel());
        }
        if (!ufp.child("#icecatApplicationLoggerPanel")) {
            ufp.add(this.applicationLogGridPanel.getTabPanel());
        }

        // if(this.uploadPanel.getConfigData() && this.uploadPanel.getConfigData().showSearchPanel !== "undefined"
        // && this.uploadPanel.getConfigData().showSearchPanel) {
        //     if (!ufp.child('#iceCatBundle_searchPanel')) {
        //         ufp.add(this.searchPanel.getPanel());
        //     }
        // }

        if (!ufp.child("#iceCatBundle_searchPanel")) {
            ufp.add(this.searchPanel.getPanel());
        }

        ufp.child("#iceCatBundle_uploadFilePanel").tab.show();
        ufp.child("#iceCatBundle_importGridPanel").tab.show();
        ufp.child("#icecatApplicationLoggerPanel").tab.show();
        ufp.child("#iceCatBundle_unfetchedProductGrid").tab.show();

        // if(this.uploadPanel.getConfigData() && this.uploadPanel.getConfigData().showSearchPanel !== "undefined"
        // && this.uploadPanel.getConfigData().showSearchPanel) {
        //     ufp.child('#iceCatBundle_searchPanel').tab.show();
        // }

        ufp.child("#iceCatBundle_searchPanel").tab.show();

        Ext.Ajax.request({
            url: Routing.generate("icecat_check_product_count"),
            success: function (response) {
                responseData = Ext.decode(response.responseText);

                if (responseData.status == "true") {
                    new pimcore.plugin.iceCatObjectGridPanel(responseData.id);
                }
            }.bind(this),
            failure: function (err) {}.bind(this),
        });
    },

    setActiveTab: function (tabIndex = this.addFileTabIndex) {
        if (Ext.getCmp("pimcore_iceCat_tabPanel")) {
            previousActiveTab = Ext.getCmp(
                "pimcore_iceCat_tabPanel"
            ).getActiveTab();
            previousActiveTabId = previousActiveTab.getId();
            Ext.getCmp("pimcore_iceCat_tabPanel").setActiveTab(tabIndex);
            if (
                tabIndex == 2 &&
                previousActiveTabId != "iceCatBundle_importGridPanel"
            ) {
                if (
                    Ext.getCmp("icecat_data_grid") &&
                    Ext.getCmp("icecat_data_grid").dockedItems &&
                    Ext.getCmp("icecat_data_grid").dockedItems.items &&
                    Ext.getCmp("icecat_data_grid").dockedItems.items.length
                )
                    Ext.getCmp(
                        "icecat_data_grid"
                    ).dockedItems.items[0].doRefresh();
            }

            this.checkifKeyEntryNeeded();
        }
    },

    hidePanels: function () {
        let ufp = Ext.getCmp("pimcore_iceCat_tabPanel");
        if (ufp.child("#iceCatBundle_uploadFilePanel")) {
            ufp.child("#iceCatBundle_uploadFilePanel").tab.hide();
        }

        if (ufp.child("#iceCatBundle_importGridPanel")) {
            ufp.child("#iceCatBundle_importGridPanel").tab.hide();
        }

        if (ufp.child("#iceCatBundle_runningProcessesPanel")) {
            ufp.child("#iceCatBundle_runningProcessesPanel").tab.hide();
        }

        if (ufp.child("#iceCatBundle_unfetchedProductGrid")) {
            ufp.child("#iceCatBundle_unfetchedProductGrid").tab.hide();
        }

        if (ufp.child("#icecatApplicationLoggerPanel")) {
            ufp.child("#icecatApplicationLoggerPanel").tab.hide();
        }

        if (ufp.child("#iceCatBundle_objectGridPanel")) {
            ufp.child("#iceCatBundle_objectGridPanel").tab.hide();
        }

        if (ufp.child("#iceCatBundle_searchPanel")) {
            ufp.child("#iceCatBundle_searchPanel").tab.hide();
        }
        if (ufp.child("#iceCatBundle_cronPanel")) {
            ufp.child("#iceCatBundle_cronPanel").tab.hide();
        }
    },

    loginIceCatUser: function (
        userName,
        password,
        loginMsgEle,
        loginButtonEle,
        loginScreen,
        logoutScreen
    ) {
        Ext.Ajax.request({
            url: Routing.generate("icecat_login"),
            async: false,
            params: {
                userName: userName,
                password: password,
            },
            success: function (response) {
                let res = JSON.parse(response.responseText);
                this.setOtherInfo(res);

                if (res.status === "error") {
                    loginMsgEle.html(
                        '<p style="color:red">Invalid User name/Password!</p>'
                    );
                    loginMsgEle.show();
                    loginButtonEle.prop("disabled", false);
                    this.hidePanels();
                } else {
                    pimcore.globalmanager.add("userLoggedIn", 1);
                    loginMsgEle.html("");
                    loginMsgEle.show();
                    loginScreen.hide();
                    logoutScreen.show();
                    this.showPanels();
                    this.setActiveTab();
                }
            }.bind(this),
            failure: function (err) {
                loginMsgEle.html(
                    '<p style="color:red">Invalid User name/Password!</p>'
                );
                loginMsgEle.show();
                loginButtonEle.prop("disabled", false);
                this.hidePanels();
            }.bind(this),
        });
    },

    isUserLoggedIn: function () {
        return pimcore.globalmanager.get("userLoggedIn") == 1 ? true : false;
    },

    logOutCatUser: function (loginScreen, logoutScreen) {
        Ext.Ajax.request({
            url: Routing.generate("icecat_get-logout-page"),
            async: false,
            success: function (response) {
                let res = JSON.parse(response.responseText);
                if (!res.login_status) {
                    this.hidePanels();
                    pimcore.globalmanager.add("iceCatData", {});
                    pimcore.globalmanager.add("userLoggedIn", "");
                    loginScreen.show();
                    logoutScreen.hide();
                }
            }.bind(this),
            failure: function (err) {}.bind(this),
        });
    },

    getOtherInfo: function (showPanels = true, activateAddFileTab = true) {
        Ext.Ajax.request({
            url: Routing.generate("icecat_other-info"),
            success: function (response) {
                let res = JSON.parse(response.responseText);
                this.setOtherInfo(res);
                if (showPanels) {
                    this.showPanels();
                }

                if (activateAddFileTab) {
                    this.setActiveTab();
                }
            }.bind(this),
            failure: function (err) {}.bind(this),
        });
    },

    getOtherInfos: function (showPanels = true) {
        Ext.Ajax.request({
            url: Routing.generate("icecat_other-info"),
            success: function (response) {
                let res = JSON.parse(response.responseText);
                this.setOtherInfo(res);
                if (showPanels) {
                    this.showPanels();
                }
            }.bind(this),
            failure: function (err) {}.bind(this),
        });
    },

    setOtherInfo: function (res) {
        if (res.otherInfo) {
            pimcore.globalmanager.add("iceCatData", {
                uploadExist: res.otherInfo.uploadExist,
                fetchingProcessExist: res.otherInfo.fetchingProcessExist,
                objectCreationProcessExist:
                    res.otherInfo.objectCreationProcessExist,
                user: res.otherInfo.user,
                currentJobId: res.otherInfo.jobId,
                jobId: res.otherInfo.jobId,
            });
        }
    },

    createObject: function (gtins, gtinsPage) {
        if (gtins) {
            gtins = gtins.join(",");
        }
        let __self = this;
        Ext.Ajax.request({
            url: Routing.generate("icecat_create-object"),
            params: {
                jobId: pimcore.globalmanager.get("iceCatData").currentJobId,
                gtins: gtins,
                gtinsPage: gtinsPage,
            },
            success: function (response) {
                let res = JSON.parse(response.responseText);
                if (res.success == "false") {
                    pimcore.helpers.showNotification(
                        "Failure",
                        res.message,
                        "error"
                    );
                    return;
                }
                if (Ext.getCmp("iceCat_create_object_button")) {
                    Ext.getCmp("iceCat_create_object_button").disable();
                }

                __self.intervalObj = setInterval(
                    function () {
                        pimcore.globalmanager.add(
                            "iceCatIntervalObjCreation",
                            __self.intervalObj
                        );
                        if (
                            Ext.query(
                                "*[id^=processingProgressBar_active_process_]"
                            ).length
                        ) {
                            __self.clearPBarInterval();
                            this.getOtherInfos(false);
                            return;
                        }
                        let ap =
                            new pimcore.plugin.IceCatActiveCreationProcesses(
                                "",
                                "processing",
                                "processingProgressBarFieldset"
                            );
                        ap.refreshTask.start();
                    }.bind(this),
                    2000
                );
            }.bind(this),
            failure: function (err) {}.bind(this),
        });
    },

    showObjects: function () {
        Ext.Ajax.request({
            url: Routing.generate("icecat_open-object-listing"),
            success: function (response) {
                let res = JSON.parse(response.responseText);
                if (res.success == "false") {
                    return;
                }
                pimcore.helpers.closeObject(res.folderId);
                pimcore.helpers.openObject(res.folderId, "folder");
            }.bind(this),
            failure: function (err) {}.bind(this),
        });
    },

    reactivateforNewProcess: function () {
        Ext.Ajax.request({
            async: false,
            url: Routing.generate("icecat_getfolderids"),
            success: function (response) {
                let res = JSON.parse(response.responseText);
                if (res.success) {
                    if (res.data.productfolderid) {
                        setTimeout(() => {
                            pimcore.elementservice.refreshNodeAllTrees(
                                "object",
                                res.data.productfolderid
                            );
                            console.log("products refreshed");
                        }, 200);
                    }
                    if (res.data.categoryfolderid) {
                        setTimeout(() => {
                            pimcore.elementservice.refreshNodeAllTrees(
                                "object",
                                res.data.categoryfolderid
                            );
                            console.log("categories refreshed");
                        }, 7000);
                    }
                }
            }.bind(this),
            failure: function (err) {}.bind(this),
        });

        window.setTimeout(
            function (id) {
                new pimcore.plugin.iceCatScreen();
            }.bind(window, this.id),
            100
        );
        Ext.getCmp("ice_cat_integration_panel").close();
    },
    clearPBarInterval: function () {
        if (this.intervalObj) {
            clearInterval(this.intervalObj);
        }
    },

    checkifKeyEntryNeeded: function () {
        var icecatData = pimcore.globalmanager.get("iceCatData");
        if (!icecatData) {
            return;
        }

        userData = icecatData.user;
        var subscriptionLevel = userData.subscription_level;

        if (subscriptionLevel == 1 || subscriptionLevel == 4) {
            if (
                !userData.access_token ||
                !userData.app_key ||
                !userData.content_token
            ) {
                Ext.getCmp("pimcore_iceCat_tabPanel").setActiveTab(0);
            }
        }

        if (subscriptionLevel == 5 || subscriptionLevel == 6) {
            console.log("coming here");
            if (!userData.access_token) {
                Ext.getCmp("pimcore_iceCat_tabPanel").setActiveTab(0);
            }
        }
    },
});
