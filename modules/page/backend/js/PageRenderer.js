


pinion.backend.renderer.PageRenderer = (function($) {
    
    var constr,
        url = pinion.php.url;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var _this = this,
            data = settings.data;
        
        
        this.$element = $("<div class='pinion-backend-renderer-PageRenderer'></div>");
        
        // TEXTWRAPPER
        var $textWrapper = $("<div class='pinion-textWrapper'></div>")
            .appendTo(this.$element);
        
        var $urlWrapper = $("<div class='pinion-url'><div class='pinion-url-site'>"+pinion.php.url+"</div></div>")
            .appendTo($textWrapper);
        
        
        // UPDATE TEXTBOX (FOR URL)
        var updateTextbox = this.addChild({
            type: "input",
            infoKey: "url",
            name: "UpdateTextbox",
            validators: {
                events: [{
                    event: "validateUrl",
                    module: "page",
                    info: {}
                }]
            },
            value: data.url ? data.url : "",
            events: settings.events
        });
        updateTextbox.$element.appendTo($urlWrapper);
        
        updateTextbox.on("change", function(element) {
            data.url = element.value;
        }, this);
        
        var $titleWrapper = $("<div class='pinion-backend-page-title'></div>")
            .appendTo($textWrapper);
        
        // UPDATE TEXTBOX (FOR TITLE)
        updateTextbox = this.addChild({
            type: "input",
            name: "TranslationTextbox",
            label: "title",
            value: data.title ? data.title : "",
            events: settings.events
        });
        updateTextbox.$element.appendTo($titleWrapper);
        
        updateTextbox.on("change", function(element) {
            data.title = element.value;
        }, this);
        
        // AUTHOR INFO    
        $("<div class='pinion-backend-page-author'><span class='author-title'>"+pinion.translate("created from:")+"</span><span class='author-name'>"+data.user+"</span></div>")
            .appendTo($textWrapper);
        
        // PAGE LINK    
        $("<div class='pinion-backend-page-link'><div class='pinion-backend-icon-link'></div><a href='"+url+data.url+"' class='pinion-text'>"+pinion.translate("go to page")+"</a></div>")
            .appendTo($textWrapper);
        
        // DELETE BUTTON
        pinion.data.Bar.call(this, [
            pinion.data.Delete.call(this, data, function() {
                _this.info.deleted = true;
                _this.fadeOut(300, function() {
                    _this.setDirty();
                });
            })
        ]);
        
        
        // INFO
        pinion.data.Info.call(this, ["Revision", "User", "Time"], data);
        
        // group events
        settings.groupEvents = true;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.PageRenderer,
        init: function() {
            this.info.id = this.settings.data.id;
        },
        reset: function() {
            this.data.deleted = false;
            this.$element.show();
        }
    }
    
    return constr;
    
}(jQuery));

