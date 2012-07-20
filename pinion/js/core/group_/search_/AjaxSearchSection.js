


pinion.backend.group.AjaxSearchSection = (function($) {
    
    var constr,
        ElementBase = pinion.backend.ElementBase;
    
    // public API -- constructor
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-group-Section'></div>");
    }
    
    // inherit from Section
    pinion.inherit(constr, pinion.backend.group.Section);
    
    constr.prototype.defaultSettings = {
        groupEvents: true
    };
    
    constr.prototype.init = function() {
        this.addChild({
            name: "Button",
            type: "input",
            label: "search",
            click: function() {
                var loader = new pinion.Loader("darkblue-40px", 40),
                    dirtyElements = {},
                    events = [];
                
                // remove existing elements from result wrapper
                resultWrapper.removeChildren();
                
                // add the loader
                loader.$element.appendTo(resultWrapper);
                
                // get the dirty elements of this element
                for(var i = _this.children.length; i--; ) {
                    var child = _this.children[i];
                    if(child != resultWrapper) {
                        ElementBase.getDirtyElements(child, dirtyElements);
                    }   
                }
                
                // get the events of the dirty elements
                canCollect = true;
                events = this.backend._collectEvents(dirtyElements);
                canCollect = false;
                
                pinion.ajax(events, function(data) {
                    loader.remove();

                    var elements = data.elements;
                    resultWrapper.asParentFor(elements).addElements(elements);
                });
            }
        });
        
        var _this = this,
            canCollect = false,
            resultWrapper = this.addChild({
                name: "Wrapper",
                type: "group",
                $element: this.$element
            });
        
        
        // don't save the search elements
        var elements = [],
            children = this.children;
        for(var i = children.length; i--; ) {
            var child = children[i];
            if(child != resultWrapper) {
                ElementBase.getElements(child, elements);
            }   
        }
        
        for(i in elements) {
            elements[i].on("save", function() {
                return canCollect;
            });
        }
    };
    
    return constr;
    
}(jQuery));