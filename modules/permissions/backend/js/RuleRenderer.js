

pinion.backend.renderer.RuleRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {

        this.$element = $("<div class='pinion-backend-renderer-RuleRenderer'></div>")
            .append("<div class='pinion-backend-icon-rule'></div>")
        
        var updateTextbox = this.addChild({
            type: "input",
            name: "UpdateTextbox",
            value: settings.data.name
        });
        
        updateTextbox.on("change", function(element) {
            this.data.name = element.settings.value;
        }, this);
            
        pinion.data.Info.call(this, ["Time", "Revision", "User"], settings.data);
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.RuleRenderer
    }
    
    return constr;
    
}(jQuery));





pinion.backend.renderer.SimpleRuleRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var _this = this;
        
        
        this.$element = $("<div class='pinion-backend-renderer-SimpleRuleRenderer'></div>")
            .append("<div class='pinion-backend-icon-rule'></div>")
            .append("<div class='rule-name'>"+settings.data.name+"</div>");
        
        if(settings.oneSelected) {
            this.$element
                .click(function() {
                    if(_this._isDirty) {
                        _this.setClean();
                        _this.$element.removeClass("ui-selected");
                    } else {
                        var children = _this.parent.children;
                        for(var i = 0, length = children.length; i < length; i++) {
                            var child = children[i];
                            child.setClean();
                            child.$element.removeClass("ui-selected");
                        }
                        _this.setDirty();
                        _this.$element.addClass("ui-selected");
                    }
                });
        } else if(settings.multipleSelected) {
            this.$element
                .click(function() {
                    if(_this._isDirty) {
                        _this.setClean();
                        _this.$element.removeClass("ui-selected");
                    } else {
                        _this.setDirty();
                        _this.$element.addClass("ui-selected");
                    }
                });
        } else {
            this.$element 
                .click(function() {
                    pinion.fire("rule.permissions", {id: settings.data.id});
                });
        }
        
            
            
        pinion.data.Timeinfo.call(this, settings.data);
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.SimpleRuleRenderer,
        init: function() {
            this.info = {id: this.settings.data.id};
        },
        reset: function() {
            this.$element.removeClass("ui-selected");
        }
    }
    
    return constr;
    
}(jQuery));