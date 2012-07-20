
pinion.namespace("modules.menu");

pinion.modules.menu.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        var items = data.items;
        
        this.$element = $("<div></div>");
        
        $("<div class='pinion-preview-info'><div class='pinion-menu-name'><div class='pinion-icon'></div><div class='pinion-text'>"+data.name+"</div></div></div>").appendTo(this.$element);
        
        if(items.length) {
            $(this.buildMenu(items))
                .addClass("pinion-menu")
                .appendTo(this.$element)
                .boxMove({windowSize: 180, direction: "vertical"});
        }
    };
    
    constr.prototype = {
        constructor: pinion.modules.menu.Preview,
        buildMenu: function(items) {
            var length = items.length,
                ul = "<ul>";
            
            if(items.length) {
                for(var i = 0, length = items.length; i < length; i++) {
                    var item = items[i];

                    ul += "<li><span class='title'>"+item.title+"</span><span class='url'>"+item.url+"</span>"+this.buildMenu(item.children)+"</li>";
                }

                ul += "</ul>";

                return ul;
            } else {
                return "";
            }
            
        }
    }
    
    return constr;
    
}(jQuery));

