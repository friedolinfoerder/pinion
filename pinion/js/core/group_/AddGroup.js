

pinion.backend.group.LazyAddGroup = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-group-LazyAddGroup'></div>");
        
        this.elements = [];
        this.counter = 0;
        this.group = null;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.LazyAddGroup,
        defaultSettings: {
            label: "add",
            group: {},
            groupEvents: true,
            mode: "multiple",
            data: [],
            appendTo: null // jquery object
        },
        init: function() {
            var _this = this,
                settings = this.settings,
                clickHandler;
                
            if(settings.appendTo) {
                this.appendTo = settings.appendTo;
            } else {
                this.appendTo = this.$element;
            }
            
            
            // define click handler
            if(settings.mode == "single") {
                clickHandler = function() {   
                    if(_this.group) {
                        if(_this.open) {
                            _this.group.$element.detach();
                            _this.open = false;
                        } else {
                            _this.group.$element.appendTo(_this.appendTo);
                            _this.open = true;
                        }
                    } else {
                        var elements = $.extend(true, [], _this.elements);
                        
                        _this.group = _this.addChild({
                            name: "Section",
                            type: "group",
                            groupEvents: true
                        }, _this.settings.group);
                        
                        for(var i = 0, length = elements.length; i < length; i++) {
                            var element = elements[i];

                            // change parent from this to group
                            if(element.parent == _this.identifier) {
                                element.parent = _this.group.identifier;
                            }
                        }
                        _this.backend.addElements(elements, _this.group);
                        
                        _this.group.$element.appendTo(_this.appendTo);
                        _this.open = true;
                    }
                };
            } else {
                clickHandler = function() {
                    var elements = $.extend(true, [], _this.elements);
                    
                    _this.addSection(elements);
                };
            }
            
            var button = this.addChild({
                name: "Button",
                type: "input",
                label: this.settings.label,
                click: clickHandler
            });
            
            this
                .on("dirty", function() {
                    button.$element.addClass("pinion-active");
                })
                .on("clean", function() {
                    button.$element.removeClass("pinion-active");
                });
                
            for(var i = 0, length = settings.data.length; i < length; i++) {
                var d = settings.data[i];
                
                var elements = $.extend(true, [], _this.elements);
                for(var j = 0, elementsLength = elements.length; j < elementsLength; j++) {
                    var element = elements[j];
                    var infoKey;
                    if(element.infoKey) {
                        infoKey = element.infoKey;
                    } else {
                        infoKey = element.label;
                    }
                    if(d[infoKey]) {
                        element.value = d[infoKey];
                    }
                }
                this.addSection(elements);
            }
        },
        addSection: function(elements) {
            var count = ++this.counter;
            
            if(this.group == null) {
                this.group = this.addChild($.extend({
                    name: "SelectGroup",
                    type: "group",
                    groupEvents: "groupEvent"
                }, this.settings.group));

                this.group.$element.appendTo(this.appendTo);
            }

            var section = this.group.addChild({
                name: "TitledSection",
                type: "group",
                groupEvents: true,
                title: count
            });

            for(var i = 0, length = elements.length; i < length; i++) {
                var element = elements[i];

                // change identifier
                element.identifier = element.identifier+"@LazyAddGroup_"+count;

                // change parent from this to group
                if(element.parent == this.identifier) {
                    element.parent = section.identifier;
                } else {
                    // change parent
                    element.parent = element.parent+"@LazyAddGroup_"+count;
                    // change lazy setting
                    if(element.lazy) {
                        element.lazy = element.lazy+"@LazyAddGroup_"+count;
                    }
                }
            }
            this.backend.addElements(elements, section);
        },
        reset: function() {
            if(this.group) {
                this.counter = 0;
                
                // remove group and set null
                this.group.remove();
                this.group = null;
            }
            
        }
    }
    return constr;
    
}(jQuery));
