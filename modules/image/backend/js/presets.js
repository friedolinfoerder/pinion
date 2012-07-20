// add namespace
pinion.namespace("pinion.backend.image");

pinion.backend.image.addToList = (function() {
    
    var newId = 1;
    
    return function(name) {
        // get values
        var values = [];

        for(var i = 0, length = this.children.length; i < length; i++) {
            var child = this.children[i];

            values.push(child.settings.value);
        }

        this.fire("addPresetFunction", {
            func: {
                newId: newId++,
                name: name,
                values: values
            }
        });

        this.backend.resetElement(this);

        return false;
    };
}());

pinion.backend.image.Crop = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-image-Crop'></div>");
        
        settings.validate = "all";
        settings.groupEvents = true;
    };
    
    constr.prototype = {
        constructor: pinion.backend.image.Crop,
        defaultSettings: {
            withAddButton: false
        },
        init: function() {
            var _this = this,
                settings = this.settings,
                values = settings.data.values,
                isNew = settings.isNew;
            
            this.$dirtyFlag.hide();
            
            this.info = {values: values};
            
            if(settings.data.newId) {
                this.info.newId = settings.data.newId;
            } else {
                this.info.id = settings.data.id;
            }
            
            this.addChild({
                type: "input",
                name: "Textbox",
                label: "left",
                value: values[0],
                dirty: isNew ? "always" : "change"
            }).on("dirty", function() {
                _this.info.values[0] = this.settings.value;
            });
            
            this.addChild({
                type: "input",
                name: "Textbox",
                label: "top",
                value: values[1],
                dirty: isNew ? "always" : "change"
            }).on("dirty", function() {
                _this.info.values[1] = this.settings.value;
            });
            
            this.addChild({
                type: "input",
                name: "Textbox",
                label: "width",
                value: values[2],
                dirty: isNew ? "always" : "change"
            }).on("dirty", function() {
                _this.info.values[2] = this.settings.value;
            });
            
            this.addChild({
                type: "input",
                name: "Textbox",
                label: "height",
                value: values[3],
                dirty: isNew ? "always" : "change"
            }).on("dirty", function() {
                _this.info.values[3] = this.settings.value;
            });
            
            if(settings.withAddButton) {

                $("<input type='submit' value='"+pinion.translate("add")+"' />")
                    .click(function() {
                        return pinion.backend.image.addToList.call(_this, "crop");
                    })
                    .appendTo(this.$childrenContainer);
            } else {
                // DELETE BUTTON
                $("<div class='pinion-presetFunctionDelete'><div class='pinion-icon'></div><div class='pinion-text'>delete</div></div>")
                    .click(function() {
                        _this.parent.parent.parent.removeEditor(_this);
                    })
                    .appendTo(this.$childrenContainer);
            }
            
            
            
        }
    };
    
    return constr;
    
}(jQuery));




pinion.backend.image.Resize = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-image-Resize'></div>");
        
        settings.validate = "all";
        settings.groupEvents = true;
    };
    
    constr.prototype = {
        constructor: pinion.backend.image.Resize,
        defaultSettings: {
            withAddButton: false
        },
        init: function() {
            var _this = this,
                settings = this.settings,
                values = settings.data.values,
                isNew = settings.isNew;
            
            this.$dirtyFlag.hide();
            
            this.info = {values: values};
            
            if(settings.data.newId) {
                this.info.newId = settings.data.newId;
            } else {
                this.info.id = settings.data.id;
            }
            
            this.addChild({
                type: "input",
                name: "Textbox",
                label: "width",
                value: values[0],
                dirty: isNew ? "always" : "change"
            }).on("dirty", function() {
                _this.info.values[0] = this.settings.value;
            });
            
            this.addChild({
                type: "input",
                name: "Textbox",
                label: "height",
                value: values[1],
                dirty: isNew ? "always" : "change"
            }).on("dirty", function() {
                _this.info.values[1] = this.settings.value;
            });
            
            this.addChild({
                type: "list",
                name: "Selector",
                label: "fit",
                value: values[2],
                dirty: isNew ? "always" : "change",
                data: [{
                    id: "inside",
                    column: "inside"
                }, {
                    id: "outside",
                    column: "outside"
                }, {
                    id: "fill",
                    column: "fill"
                }],
                noEmptyValue: true
            }).on("dirty", function() {
                _this.info.values[2] = this.settings.value;
            });
            
            this.addChild({
                type: "list",
                name: "Selector",
                label: "scale",
                value: values[3],
                dirty: isNew ? "always" : "change",
                data: [{
                    id: "any",
                    column: "any"
                }, {
                    id: "down",
                    column: "down"
                }, {
                    id: "up",
                    column: "up"
                }],
                noEmptyValue: true
            }).on("dirty", function() {
                _this.info.values[3] = this.settings.value;
            });
            
            if(this.settings.withAddButton) {
                
                $("<input type='submit' value='"+pinion.translate("add")+"' />")
                    .click(function() {
                        return pinion.backend.image.addToList.call(_this, "resize");
                    })
                    .appendTo(this.$childrenContainer);
            } else {
                // DELETE BUTTON
                $("<div class='pinion-presetFunctionDelete'><div class='pinion-icon'></div><div class='pinion-text'>delete</div></div>")
                    .click(function() {
                        _this.parent.parent.parent.removeEditor(_this);
                    })
                    .appendTo(this.$childrenContainer);
            }
        }
    };
    
    return constr;
    
}(jQuery));



pinion.backend.image.Rotate = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-image-Rotate'></div>");
        
        settings.validate = "all";
        settings.groupEvents = true;
    };
    
    constr.prototype = {
        constructor: pinion.backend.image.Rotate,
        defaultSettings: {
            withAddButton: false
        },
        init: function() {
            var _this = this,
                settings = this.settings,
                values = settings.data.values || [],
                isNew = settings.isNew;
            
            this.$dirtyFlag.hide();
            
            this.info = {values: values};
            
            if(settings.data.newId) {
                this.info.newId = settings.data.newId;
            } else {
                this.info.id = settings.data.id;
            }
            
            this.addChild({
                type: "input",
                name: "Slider",
                label: "angle",
                value: values[0],
                dirty: isNew ? "always" : "change",
                min: -180,
                max: 180
            }).on("dirty", function() {
                _this.info.values[0] = this.settings.value;
            });
            
            if(this.settings.withAddButton) {

                $("<input type='submit' value='"+pinion.translate("add")+"' />")
                    .click(function() {
                        return pinion.backend.image.addToList.call(_this, "rotate");
                    })
                    .appendTo(this.$childrenContainer);
            } else {
                // DELETE BUTTON
                $("<div class='pinion-presetFunctionDelete'><div class='pinion-icon'></div><div class='pinion-text'>delete</div></div>")
                    .click(function() {
                        _this.parent.parent.parent.removeEditor(_this);
                    })
                    .appendTo(this.$childrenContainer);
            }
        }
    };
    
    return constr;
    
}(jQuery));


pinion.backend.image.AsGrayscale = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-image-AsGrayscale'></div>");
    };
    
    constr.prototype = {
        constructor: pinion.backend.image.AsGrayscale,
        defaultSettings: {
            withAddButton: false
        },
        init: function() {
            var _this = this;
            
            if(this.settings.withAddButton) {
                
                $("<input type='submit' value='"+pinion.translate("add")+"' />")
                    .click(function() {
                        return pinion.backend.image.addToList.call(_this, "asGrayscale");
                    })
                    .appendTo(this.$childrenContainer);
            } else {
                // DELETE BUTTON
                $("<div class='pinion-presetFunctionDelete'><div class='pinion-icon'></div><div class='pinion-text'>delete</div></div>")
                    .click(function() {
                        _this.parent.parent.parent.removeEditor(_this);
                    })
                    .appendTo(this.$childrenContainer);
            }
            
        }
    };
    
    return constr;
    
}(jQuery));


pinion.backend.image.PresetBuilder = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-image-PresetBuilder'></div>");
        
        settings.validate = "all";
        settings.groupEvents = true;
    };
    
    constr.prototype = {
        constructor: pinion.backend.image.PresetBuilder,
        init: function() {
            var _this = this;
            
            this.nameInput = this.addChild({
                type: "input",
                name: "Textbox",
                label: "name",
                validators: {
                    notEmpty: true
                }
            });
            
            this.$functionList = $("<ul></ul>");
            var $wrapperDiv = $("<div></div>")
                .append(this.$functionList)
                .appendTo(this.$element);
            
            var wrapper = this.addChild({
                type: "group",
                name: "Wrapper",
                $element: $wrapperDiv
            });
            
            wrapper.reset = function() {
                _this.$functionList.children().remove();
            };
            
            pinion.on("addPresetFunction", function(data) {
                var func = data.func;
                
                var $removeLabel = $("<div class='removeLabel'></div>")
                    .click(function() {
                        $(this).parent().remove();
                        
                        if(_this.$functionList.children().length == 0) {
                            wrapper.setClean();
                        }
                    });
                
                var $li = $("<li data-func='"+func.name+"' class='"+func.name+"'>"+pinion.translate(func.name)+"</li>")
                    .append($removeLabel)
                    .appendTo(this.$functionList);
                
                this.$valueList = $("<ol></ol>").appendTo($li);
                
                for(var i = 0, length = func.values.length; i < length; i++) {
                    var value = func.values[i];
                    
                    this.$valueList.append("<li data-value='"+value+"'>"+value+"</li>");
                }
                
                wrapper.setDirty();
            }, this);
            
            this.on("save", function() {
                
                this.info = {
                    name: this.nameInput.settings.value,
                    functions: []
                };
                
                this.$functionList.children().each(function() {
                    var func = {
                        name: $(this).attr("data-func"),
                        values: []
                    };
                    _this.$valueList.children().each(function() {
                        func.values.push($(this).attr("data-value"));
                    });
                    _this.info.functions.push(func);
                });
            });
        }
    };
    
    return constr;
    
}(jQuery));




pinion.on("create.image.addPresetButton", function(data) {
    data.element.click(function() {
        var prev = this.prev();
        prev.pushData.call(prev, [{
            isNew: true,
            functions: []
        }]);
    });
});