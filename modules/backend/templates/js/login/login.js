jQuery(function($) {
    
    
    var hover = false;
    var focusUsername = false;
    var focusPassword = false;
    
    var $inputUsername = $("#pinion-page-login-inputs-positioning input[name=username]");
    var $inputPassword = $("#pinion-page-login-inputs-positioning input[name=password]");
    var $inputsWrapper = $("#pinion-page-login-inputs");
    
    $inputUsername
        .focus(function() {focusUsername = true; checkForSlideDown(); })
        .blur(function() {focusUsername = false; checkForSlideUp(); });
        
    $inputPassword
        .focus(function() {focusPassword = true; checkForSlideDown(); })
        .blur(function() {focusPassword = false; checkForSlideUp(); });
        
    
    $inputsWrapper
        .css("width", $inputsWrapper.width()+"px")
        .hover(function() {   
            hover = true;
            checkForSlideDown();
        }, function() {
            hover = false;
            checkForSlideUp();
        })
        .children()
            .css({
                "position": "absolute",
                "bottom": 0
            });
    
    function checkForSlideUp() {
        setTimeout(function() {
            if(!hover && !focusUsername && !focusPassword) {
                $("#pinion-page-login-inputs")
                    .animate({
                        "height": "29px"
                    }, 100);
            }
        }, 100);
    }
    
    function checkForSlideDown() {
        if(hover || focusUsername || focusPassword) {
            $inputsWrapper
                .animate({
                    "height": "35px"
                }, 300);
        }
    }
    
    if($inputUsername.val() == "")
        $inputUsername.focus();
    else
        $inputPassword.focus();
});