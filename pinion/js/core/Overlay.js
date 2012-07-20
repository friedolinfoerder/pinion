

pinion.namespace("backend.Overlay");

pinion.backend.Overlay = (function($) {
        
        // private properties
   var  $overlay = $("<div id='pinion-backend-Overlay'></div>").hide(),
        $body,
        $element = $overlay,
        showed = false,
        speed = 1000,
        show = function() {
            if(!showed) {
                showed = true;
                $body.addClass("pinion-overlay");
                $overlay
                    .fadeIn(speed);
            }
        },
        hide = function() {
            if(showed) {
                showed = false;
                $overlay.fadeOut(speed, function() {
                    $body.removeClass("pinion-overlay");
                });
            }
        };
        
        
    $(function() {
        $body = $("body");
        $element.appendTo(pinion.$backend);
    });
   
   // revealing public API
   return {
       $element: $element,
       show: show,
       hide: hide
   };
   
}(jQuery));