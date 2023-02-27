Ext.override(pimcore.object.tags.classificationstore, {
    getLayoutEdit: function () {
        this.fieldConfig.datatype = "layout";
        this.fieldConfig.fieldtype = "panel";

        var wrapperConfig = {
            bodyCls: "pimcore_object_tag_classification_store",
            border: true,
            style: "margin-bottom: 10px",
        };

        if (this.fieldConfig.width) {
            wrapperConfig.width = this.fieldConfig.width;
        }

        if (this.fieldConfig.region) {
            wrapperConfig.region = this.fieldConfig.region;
        }

        if (this.fieldConfig.title) {
            wrapperConfig.title = this.fieldConfig.title;
        }

        var nrOfLanguages = this.frontendLanguages.length;

        var tbarItems = [];

        if (
            !this.fieldConfig.noteditable &&
            !this.fieldConfig.disallowAddRemove
        ) {
            tbarItems.push({
                xtype: "button",
                iconCls: "pimcore_icon_add",
                handler: function () {
                    var storeId = this.fieldConfig.storeId;
                    var keySelectionWindow =
                        new pimcore.object.classificationstore.keySelectionWindow(
                            {
                                parent: this,
                                enableGroups: true,
                                enableCollections: true,
                                enableGroupByKey: true,
                                storeId: storeId,
                                object: this.object,
                                fieldname: this.fieldConfig.name,
                                maxItems: this.fieldConfig.maxItems,
                            }
                        );
                    keySelectionWindow.show();
                }.bind(this),
            });
        }

        if (this.dropdownLayout) {
        } else {
            var panelConf = {
                autoScroll: true,
                cls: "object_field object_field_type_" + this.type,
                activeTab: 0,
                height: "auto",
                items: [],
                deferredRender: true,
                forceLayout: true,
                enableTabScroll: true,
                tbar: {
                    items: tbarItems,
                },
            };

            if (this.fieldConfig.height) {
                panelConf.height = this.fieldConfig.height;
                panelConf.autoHeight = false;
            }

            for (var i = 0; i < nrOfLanguages; i++) {
                this.currentLanguage = this.frontendLanguages[i];
                this.languageElements[this.currentLanguage] = [];
                this.groupElements[this.currentLanguage] = {};

                var childItems = [];

                for (var groupId in this.fieldConfig.activeGroupDefinitions) {
                    var groupedChildItems = [];

                    if (
                        this.fieldConfig.activeGroupDefinitions.hasOwnProperty(
                            groupId
                        )
                    ) {
                        var group =
                            this.fieldConfig.activeGroupDefinitions[groupId];

                        var fieldset = this.createGroupFieldset(
                            this.currentLanguage,
                            group,
                            groupedChildItems
                        );

                        childItems.push(fieldset);
                    }
                }
                var title = this.frontendLanguages[i];
                if (title != "default") {
                    var title = t(pimcore.available_languages[title]);
                    var icon =
                        "pimcore_icon_language_" +
                        this.frontendLanguages[i].toLowerCase();
                } else {
                    var title = t(title);
                    var icon = "pimcore_icon_white_flag";
                }

                var item = new Ext.Panel({
                    border: false,
                    height: "auto",
                    padding: "10px",
                    deferredRender: false,
                    hideMode: "offsets",
                    iconCls: icon,
                    title: title,
                    items: childItems,
                });

                this.languagePanels[this.currentLanguage] = item;

                if (this.fieldConfig.labelWidth) {
                    item.labelWidth = this.fieldConfig.labelWidth;
                }

                if (this.fieldConfig.labelAlign) {
                    item.labelAlign = this.fieldConfig.labelAlign;
                }

                // Removes default tab from Icecat Classification store
                if (this.object.data.general.o_className === "Icecat") {
                    if (
                        icon != "pimcore_icon_white_flag" &&
                        this.fieldConfig.name == "Features"
                    ) {
                        panelConf.items.push(item);
                    }
                } else {
                    panelConf.items.push(item);
                }
            }

            this.tabPanel = new Ext.TabPanel(panelConf);

            wrapperConfig.items = [this.tabPanel];
        }

        this.currentLanguage = this.frontendLanguages[0];

        this.component = new Ext.Panel(wrapperConfig);

        this.component.updateLayout();
        return this.component;
    },
});
