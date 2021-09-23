Ext.define('pimcore.filters', {
    extend: 'Ext.grid.filters.Filters',
    alias: 'plugin.pimcore.icGridfilters',

    createColumnFilter: function (column) {
        this.callSuper(arguments);
        var type = column.filter.type;
        var theFilter = column.filter.filter;
        var colLabel = column.config.text;

        if (typeof column.config.layout !== 'undefined') {
            var columnLayoutMain = column.config.layout;
            if (typeof columnLayoutMain.layout !== 'undefined') {
                var columnLayout = columnLayoutMain.layout;
                if (typeof columnLayout.classId !== 'undefined') {
                    var propertyClassId = columnLayout.classId;
                }
                if (typeof columnLayout.fieldname !== 'undefined') {
                    var propertyFieldname = columnLayout.fieldname;
                }
            }
        }

        if (type == "date" || type == "numeric") {
            theFilter.lt.config.type = type;
            theFilter.gt.config.type = type;
            theFilter.eq.config.type = type;
            theFilter.lt.config["classId"] = propertyClassId;
            theFilter.gt.config["classId"] = propertyClassId;
            theFilter.eq.config["classId"] = propertyClassId;
            theFilter.lt.config["fieldname"] = propertyFieldname;
            theFilter.gt.config["fieldname"] = propertyFieldname;
            theFilter.eq.config["fieldname"] = propertyFieldname;
            theFilter.lt.config["colLabel"] = colLabel;
            theFilter.gt.config["colLabel"] = colLabel;
            theFilter.eq.config["colLabel"] = colLabel;
        } else {
            theFilter.config.type = type;
            theFilter.config["classId"] = propertyClassId;
            theFilter.config["fieldname"] = propertyFieldname;
            theFilter.config["colLabel"] = colLabel;
        }
    }
});