


pinion.backend.renderer.WordRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var _this = this,
            data = settings.data;

        this.$element = $("<div class='pinion-backend-renderer-WordRenderer'></div>")
            .append("<div class='pinion-flag pinion-flag-"+data.language+"'></div>")
            
        
        
        // WORD
        $("<div class='pinion-word'>"+data.word+"</div>").appendTo(this.$element);
//        var updateTextboxWord = this.addChild({
//            type: "input",
//            name: "UpdateTextbox",
//            value: data.word
//        });
//        updateTextboxWord.$element.appendTo(this.$element);
//        
//        updateTextboxWord.on("change", function(element) {
//            data.word = element.settings.value;
//        }, this);
        
        
        var $translationWrapper = $("<div class='pinion-translation'></div>").appendTo(this.$element);
        
        // TRANSLATION
        var updateTextboxTranslation = this.addChild({
            type: "input",
            name: "UpdateTextbox",
            infoKey: "translation",
            events: settings.events,
            info: {
                word: data.word,
                language: data.language
            },
            value: data.translation
        });
        updateTextboxTranslation.$element.appendTo($translationWrapper);
        
        updateTextboxTranslation.on("change", function(element) {
            data.word = element.settings.value;
        }, this);
        
        // DELETE BUTTON
        pinion.data.Bar.call(this, [
            pinion.data.Delete.call(this, settings.data)
        ]);
        
        
        if(data.created) {
            pinion.data.Info.call(this, ["Time", "Revision", "User"], settings.data);
        } else {
            this.$element.addClass("pinion-new");
        }
        
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.WordRenderer
    }
    
    return constr;
    
}(jQuery));