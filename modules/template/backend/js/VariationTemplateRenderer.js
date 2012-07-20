pinion.backend.renderer.VariationTemplateRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function() {
        this.$element = $("<div class='pinion-backend-renderer-VariationTemplateRenderer'></div>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.VariationTemplateRenderer,
        init: function() {
            var _this = this,
                data = this.settings.data;
            
            // ICON
            var $styleIcon = $("<div class='pinion-style-icon'></div>")
                .appendTo(this.$element);
                   
            if(data.icon) {
               $styleIcon.css("background-image", "url("+data.icon+")");
            }
            
            // TEXT WRAPPER
            var $textWrapper = $("<div class='pinion-textWrapper'></div>")
                .append("<div class='pinion-templateVariation-name'>"+data.name+"</div>")
                .appendTo(this.$element);
            
            var id;
            
            if(pinion.hasPermission("template", "edit template")) {
                id = pinion.getId();
            
                var $codeWrapper = $("<div class='pinion-template-codeareaWrapper'>")
                    .appendTo(this.$element);

                this.addElements([{
                    parent: this.identifier,
                    identifier: id,
                    name: "LazyAddGroup",
                    type: "group",
                    label: "edit",
                    mode: "single",
                    appendTo: $codeWrapper
                }, {
                    parent: id,
                    lazy: id,
                    name: "AjaxSection",
                    type: "group",
                    data: {
                        event: "getFile",
                        module: "template",
                        info: {
                            name: data.id,
                            module: data.module,
                            template: data.template
                        }
                    }
                }]);

                this.backend.elements[id].$element.appendTo($textWrapper);
            }
            
            if(pinion.hasPermission("template", "switch templates of contents")) {
                id = pinion.getId();
            
                this.addElements([{
                    parent: this.identifier,
                    identifier: id,
                    name: "Section",
                    type: "group",
                    label: "edit",
                    plugins: ["singleDirty"]
                }, {
                    parent: id,
                    name: "RadioSwitcher",
                    type: "input",
                    label: "use",
                    events: [{
                        event: "useModuleTemplate",
                        module: "template",
                        info: {
                            name: data.id,
                            module: data.module
                        }
                    }],
                    data: [{id: "page"}, {id: "all"}]
                }, {
                    parent: id,
                    name: "RadioSwitcher",
                    type: "input",
                    label: "delete",
                    events: [{
                        event: "deleteModuleTemplate",
                        module: "template",
                        info: {
                            name: data.id,
                            module: data.module
                        }
                    }],
                    data: [{id: "page"}, {id: "all"}]
                }]);

                this.backend.elements[id].$element.appendTo($textWrapper);
            }
            
            
            // DELETE BUTTON
            pinion.data.Bar.call(this, [
                pinion.data.Delete.call(this, data, function() {
                    _this.info.deleted = true;
                    _this.fadeOut(300, function() {
                        _this.setDirty();
                    });
                })
            ]);
            
        }
    }
    
    return constr;
    
}(jQuery));