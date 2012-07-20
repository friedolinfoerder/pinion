


pinion.namespace("backend.table.DataTable");

pinion.backend.table.DataTable = (function($) {
    
    var _this,
        constr,
        showData = function() {
            
            _this.$element.html("");
            
            var $tBody = $("<tbody></tbody>");
            
            for(var i = 0, dataLength = _this.data.length; i < dataLength; i++) {
                var row = _this.data[i];
                if(i == 0) {
                    var $tHead = $("<thead></thead>").appendTo(_this.$element);
                    $tBody.appendTo(_this.$element);
                    var $tr = $("<tr></tr>").appendTo($tHead);
                    for(var key in row) {
                        if(key == "id") continue;
                        if(_this.settings.label !== undefined && _this.settings.label[key] !== undefined) {
                            key = _this.settings.label[key];
                        }
                        $tr.append("<th>"+key+"</th>");
                    }
                }
                
                var $tr = $("<tr></tr>").appendTo($tBody);
                for(var key in row) {
                    if(key == "id") {
                        $tr.attr("data-id", row[key]);
                        continue;
                    }
                    $tr.append("<td>"+row[key]+"</td>");
                }
            }
        };
    
    // public API -- constructor
    constr = function(settings) {
        _this = this;
        
        this.settings = settings;
        
        this.$element = $("<table class='pinion-backend-DataFinder'></table>");
        
        if(typeof settings.dataEvent === "object") {
            pinion.ajax(settings.dataEvent, function(data) {
                this.data = data;
                showData();
            });
        } else if(Array.isArray(settings.data)) {
            this.data = settings.data;
            showData();
        } else {
            console.error("No data or no dataFunction given.");
        }
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.table.DataTable,
        version: "1.0"
    }
    return constr;
    
}(jQuery));