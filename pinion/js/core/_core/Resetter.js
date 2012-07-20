

pinion.Resetter = (function($) {
    
    var constr,
        elementToReset,
        $resetContainer = $("<div class='pinion-reset-container'></div>")
            .append("<a href='#'>"+pinion.translate("reset")+"</a>")
            .hide()
            .mouseleave(function() { 
                $resetContainer.hide();
            })
            .children("a")
                .click(function() {
                    elementToReset.backend.resetElement(elementToReset);
                    $resetContainer.hide();
                    
                    return false;
                })
                .end();
            
    $(function() {
        $resetContainer.appendTo("body");
    });
    
    constr = function(element) {
        return $("<span class='pinion-backend-icon-dirty'></span>")
            .mouseenter(function() {
                elementToReset = element;
                
                var offset = $(this).offset();
                
                $resetContainer
                    .css(offset)
                    .show();
            });
    };
    
    constr.hide = function() {
        $resetContainer.hide();
    };
    
    constr.prototype = {
        constructor: pinion.Resetter
    };
    
    return constr;
        
}(jQuery));