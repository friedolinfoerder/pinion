$(function() {
    
    $("ul ul").hide();
    
    var a = $("a.hasChildren");
    a.css({
        "position": "relative",
        "padding-left": "25px"
    });
    $("<div>&mapsto;</div>")
        .appendTo(a)
        .css({
            "position":"absolute",
            "top":0,
            "left":0,
            "height":"100%",
            "line-height":"22px",
            "width":"20px",
            "background": "#000",
            "opacity": "0.5",
            "text-align":"center"
        })
        .click(function() {
            if($(this).hasClass("expanded")) {
                $(this).removeClass("expanded");
                $(this).html("&mapsto;");
            } else {
                $(this).addClass("expanded");
                $(this).html("&mapstodown;");
            }
            
            var ul = $(this).parent().next();
            ul.slideToggle(500);
            
            return false;
        });
        
    
});