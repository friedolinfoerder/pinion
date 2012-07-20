pinion.backend.renderer.ModuleTemplateRenderer = (function($) {
    
    var constr,
        url = pinion.php.url,
        phpModules = pinion.php.modules;
    
    // public API -- constructor
    constr = function() {
        this.$element = $("<div class='pinion-backend-renderer-ModuleTemplateRenderer'></div>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.ModuleTemplateRenderer,
        init: function() {
            var _this = this,
                data = this.settings.data;
            
            var module = phpModules[data.name] || {
                icon: url+"pinion/assets/images/icons/defaultModuleIcon.png",
                name: data.name,
                title: data.name
            };
            
            // ICON
            $("<div class='pinion-moduleIcon'><img src='"+module.icon+"' /></div>")
                .appendTo(this.$element);
            
            // TEXT WRAPPER
            var $textWrapper = $("<div class='pinion-textWrapper'></div>")
                .click(function() {
                    $templates.slideToggle(300);
                    _this.$element.toggleClass("pinion-expanded");
                })
                .append("<div class='pinion-module-title'>"+module.title+"</div>")
                .append("<div class='pinion-showTemplates'>"+pinion.translate("show templates")+"</div>")
                .appendTo(this.$element);
            
            // TEMPLATE VARIATIONS
            var $templates = $("<div class='pinion-template-templateVariations'>")
                .hide()
                .appendTo(this.$element);
            
            
            
            this.addChild({
                name: "Finder",
                type: "list",
                data: data.templates,
                renderer: {
                    name: "VariationTemplateRenderer"
                }
            }).$element.appendTo($templates);
            
            if(pinion.hasPermission("template", "edit template")) {
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
    }
    
    return constr;
    
}(jQuery));