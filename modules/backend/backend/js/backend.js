jQuery(function($) {
    var $body = $("body");
    var currentMarginBottom = parseFloat($body.css("margin-top"));
    $body.css("margin-top", currentMarginBottom + 33 + "px");
    
    var newUrl = document.URL;
    var regex = new RegExp("^(.*?)&");
    var matches = newUrl.match(regex);
    if(matches)
        newUrl = matches[1];
    $("#pinion-backend-logoutLink").attr("href", newUrl + "&logout");
    
    var $logContainer = jQuery("#pinion-backend-log-container");
    var $logArchive = jQuery("#pinion-backend-log-archive");
    $("#pinion-backend-log-link").click(function() {
        if($logContainer.css("display") == "block") {
            $logContainer.hide();
            $logArchive.show();
        } else {
            $logContainer.show();
            $logArchive.hide();
        }
    });
    
    $(window).scroll(function() {
//        $("#pinion-backend").css(pinion.scroll);
//        $("#pinion-backend-bar").css(pinion.scroll);
//        $("#pinion-backend-menuClickStopper").css(pinion.scroll);
    });
    
    pinion.$window.bind('beforeunload', function() {
        if(pinion.Frontend.instance.isDirty()) {
            return 'Are you sure you want to leave?';
        }
    });
    
    var $user = $("#pinion-backend-user");
    
    $user.html(pinion.$link($user.html(), "permissions", "user account"));
});