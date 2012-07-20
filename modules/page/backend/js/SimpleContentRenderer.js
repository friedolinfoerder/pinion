


pinion.backend.renderer.SimpleContentRenderer = (function($) {
    
    var constr,
        visible,
        invisible,
        setVisible,
        setInvisible,
        area,
        templatePath,
        modules = pinion.php.modules,
        url = pinion.php.url;
    
    // public API -- constructor
    constr = function(settings, backend) {
        this.$element = $("<div class='pinion-backend-renderer-SimpleContentRenderer'></div>");
        
        // group events
        settings.groupEvents = true;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.SimpleContentRenderer,
        init: function() {
            this.info.id = this.settings.data.id;
            
            var _this = this,
                settings = this.settings,
                data = settings.data,
                module = modules[data.module];

            if(!visible) {
                visible      = pinion.translate("visible");
                invisible    = pinion.translate("invisible");
                setVisible   = pinion.translate("set visible");
                setInvisible = pinion.translate("set invisible");
                area         = pinion.translate("area");
                templatePath = pinion.translate("template path")
            }


            // TEXTWRAPPER
            var $textWrapper = $("<div class='pinion-textWrapper'></div>")
                .appendTo(this.$element);

            // VISIBILITY INFO
            var $visibilityInfo = $("<div class='pinion-backend-content-visibleInfo'><div class='pinion-icon'></div><div class='pinion-text'>"+visible+"</div></div>")
                .appendTo($textWrapper);

            // PAGE
            $("<div class='pinion-backend-content-page'><div class='pinion-icon'></div><a href='"+url+data.page+"' class='pinion-page-name'>"+(data.page == "" ? "<i>"+pinion.translate("start page")+"</i>" : data.page)+"</a></div>")
                .appendTo($textWrapper);

            // AREA
            $("<div class='pinion-backend-content-area'><div class='pinion-icon'></div><span class='pinion-area-name'>"+data.areaname+"</span></div>")
                .appendTo($textWrapper);

            // TEMPLATE
            $("<div class='pinion-backend-content-template'><div class='pinion-icon'></div><span class='pinion-templatePath'>"+data.templatepath+"</span></div>")
                .appendTo($textWrapper);

            // VISIBILITY
            var $visibility = $("<div class='pinion-content-changeVisibility'><div class='pinion-icon'></div><div class='pinion-text'>set invisible</div></div>")
                .click(function() {
                    data.visible = !data.visible;
                    _this.info.visible = data.visible;
                    _this.$element.toggleClass("pinion-invisible");
                    if(data.visible) {
                        $visibility.children(".pinion-text").text(setInvisible);
                        $visibilityInfo.children(".pinion-text").text(visible);
                    } else {
                        $visibility.children(".pinion-text").text(setVisible);
                        $visibilityInfo.children(".pinion-text").text(invisible);
                    }
                    if(data.visible == _this.initSettings.data.visible) {
                        visibilityWrapper.setClean();
                    } else {
                        visibilityWrapper.setDirty();
                    }
                });

            var visibilityWrapper = this.addChild({
                name: "Wrapper",
                type: "group",
                $element: $visibility,
                groupEvents: true
            });
            
            // DELETE BUTTON
            pinion.data.Bar.call(this, [
                $visibility,
                pinion.data.Delete.call(this, data, function() {
                    _this.info.deleted = true;
                    _this.fadeOut(300, function() {
                        _this.setDirty();
                    });
                })
            ]);
            

            if(!data.visible) {
                this.$element.addClass("pinion-invisible");
                $visibility.children(".pinion-text").text(setVisible);
                $visibilityInfo.children(".pinion-text").text(invisible);
            }
            
            // INFOS
            pinion.data.Info.call(this, ["Time", "Revision", "User"], data);
        },
        reset: function() {
            this.data.deleted = false;
            this.$element.show();
        }
    }
    
    return constr;
    
}(jQuery));

