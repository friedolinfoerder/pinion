

pinion.backend.renderer.StructureRenderer = (function($) {
    
    var constr;
    
    constr = function(settings, backend) {
        
        this.$element = $("<div class='pinion-backend-renderer-StructureRenderer'></div>");
        
        settings.events = [{
            event: "deleteStructure",
            module: "container",
            info: {id: settings.data.id}
        }];
    };
    
    constr.prototype = {
        constructor: pinion.backend.renderer.StructureRenderer,
        init: function() {
            var _this = this;
            
            // TEXTWRAPPER
            var $textWrapper = $("<div class='pinion-textWrapper'></div>")
                .appendTo(this.$element);
            
            if(pinion.hasPermission("container", "rename structure")) {
                this.addChild({
                    name: "UpdateTextbox",
                    type: "input",
                    value: this.settings.data.name,
                    infoKey: "name",
                    validators: {
                        events: [{
                           event: "hasStructureName",
                           module: "container",
                           info: {}
                        }],
                        notEmpty: true
                    },
                    events: [{
                        event: "renameStructure",
                        module: "container",
                        info: {id: this.settings.data.id}
                    }]
                }).$element.appendTo($textWrapper);
            } else {
                $textWrapper.append("<div class='pinion-structure-name'>"+this.settings.data.name+"</div>");
            }
            
            
            var sortAllowed = pinion.hasPermission("container", "sort structure");
            this.addChild({
                name: "Finder",
                type: "list",
                data: this.settings.data.contents,
                draggable: sortAllowed,
                renderer: "StructureContentRenderer",
                events: sortAllowed ? [{
                    event: "sortStructure",
                    module: "container",
                    info: {id: this.settings.data.id}
                }] : []
            });
            
            if(pinion.hasPermission("container", "delete structure")) {
                // DELETE
                pinion.data.Bar.call(this, [
                    pinion.data.Delete.call(this, this.settings.data, function() {
                        _this.resetElement();
                        _this.info.deleted = true;
                        _this.fadeOut(300, function() {
                            _this.setDirty();
                        });
                    })
                ]);
            }
            
        }
    };
    
    return constr;
    
}(jQuery));