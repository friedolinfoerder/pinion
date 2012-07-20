

pinion.backend.renderer.TemplateRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        this.$element = $("<div class='pinion-backend-renderer-TemplateRenderer'></div>")
            .append("<div class='pinion-colorfield-left'></div>")
            .append("<div class='pinion-colorfield-right'></div>")
            // ICON
            .append("<div class='pinion-template-icon'><img src='"+settings.data.icon+"' /></div>");
            
        settings.groupEvents = true;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.TemplateRenderer,
        init: function() {
            var _this = this,
                data = this.settings.data;
        
            // TEXT WRAPPER
            var $textWrapper = $("<div class='pinion-textWrapper'></div>")
                .appendTo(this.$element);
            
            // HEADLINE WRAPPER
            $("<div class='pinion-headlineWrapper'></div>")
                .append("<div class='pinion-template-title'>"+data.title+"</div>")
                .append("<div class='pinion-template-version'>"+data.version+"</div>")
                .append("<div class='pinion-template-name'>"+data.name+"</div>")
                .appendTo($textWrapper);
            
            // AUTHOR
            if(data.author) {
                $("<div class='pinion-template-author'><div class='pinion-backend-icon-author'></div><span class='pinion-text'>"+data.author+"</span></div>")
                    .appendTo($textWrapper);
            }
            
                
            // DESCRIPTION
            if(data.description) {
                $textWrapper.append("<div class='pinion-template-description'>"+pinion.translate(data.description)+"</div>");
            } else {
                $textWrapper.append("<div class='pinion-template-description pinion-noDescription'>"+pinion.translate("no description available")+"</div>");
            }
            
            // MODULE TEMPLATES
            var $moduleTemplates = $("<div class='pinion-template-moduleTemplates'></div>")
                .appendTo(this.$element);
                
            this.addChild({
                name: "AjaxTitledGroup",
                type: "group",
                title: "supported modules",
                open: false,
                data: {
                    event: "getModuleTemplates",
                    module: "template",
                    info: {
                        name: data.id
                    }
                }
            }).$element.appendTo($moduleTemplates);
            
            
            // RENDERER BAR
            var bar = [];
            
            if(pinion.hasPermission("template", "switch template")) {
                // USE TEMPLATE BUTTON
                var $useTemplate = $("<a class='pinion-template-use'><div class='pinion-text'>"+pinion.translate("use template")+"</div><div class='pinion-icon'></div></a>")
                    .click(function() {
                        var siblings = _this.siblings();
                        for(var i = siblings.length; i--; ) {
                            siblings[i].resetElement();
                        }
                        if(!data.active) {
                            _this.useWrapper.setDirty();
                        }
                        pinion.$(_this.siblings()).removeClass("pinion-active");
                        _this.$element.addClass("pinion-active");
                        return false;
                    });

                this.useWrapper = this.addChild({
                    name: "Wrapper",
                    type: "group",
                    $element: $useTemplate,
                    events: this.settings.events,
                    info: {use: true}
                });

                if(data.active) {
                    this.$element.addClass("pinion-active");
                }
                
                bar.push($useTemplate);
            }
            
            if(pinion.hasPermission("template", "delete template")) {
                bar.push(pinion.data.Delete.call(this, data, function() {
                    _this.info.deleted = true;
                    _this.fadeOut(300, function() {
                        _this.setDirty();
                    });
                }));
            }
            
            if(!pinion.isEmpty(bar)) {
                pinion.data.Bar.call(this, bar);
            }
            
            
            
            this.info.id = data.id;
        }
    }
    
    return constr;
    
}(jQuery));