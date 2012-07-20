jQuery(function($) {
    var $ul = $("<ul class='tab-list'>")
        .on("click", "li", function() {
            var $this = $(this);
            $tabs.hide().eq($this.index()).show();
            $this.addClass("active-tab").siblings().removeClass("active-tab");
        });
        
    var $tabs = $(".module-tab").hide().each(function(index) {
        var $this = $(this),
            $h2 = $this.children("h2:first"),
            text;
            
        if($h2.length) {
            text = $h2.text();
        } else {
            text = "tab "+(index+1);
        }
        
        $ul.append("<li>"+text+"</li>");
    });
    
    $ul.insertBefore($tabs.first()).children(":first").click();
});