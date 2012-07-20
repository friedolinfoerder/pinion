
pinion.namespace("modules.comment");

pinion.modules.comment.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div>");
        
        var $previewInfo = $("<div class='pinion-preview-info'>").appendTo(this.$element),
            $commentInfo = $("<div class='pinion-comment-infos'>").appendTo($previewInfo),
            $ul = $("<ul>")
                .appendTo($commentInfo)
                .boxMove({windowSize: 175});
            
        var has = ["email", "homepage", "subject"];
        
        for(var i = 0, length = has.length; i < length; i++) {
            $ul.append("<li class='"+(data["has_"+has[i]] ? "pinion-yes" : "pinion-no")+"'><span class='pinion-comment-info'>"+has[i]+"</span><span class='pinion-comment-icon'></span></li>")
        }
        $("<div class='pinion-count'><div class='pinion-icon'></div><div class='pinion-text'>"+data.count+"</div></div>").appendTo($previewInfo);
        
        var $pinionComments = $("<ul class='pinion-comments'></ul>")
            .appendTo(this.$element)
            .boxMove({windowSize: 180, direction: "vertical"});
            
        for(i = 0, length = data.texts.length; i < length; i++) {
            var comment = data.texts[i],
                $li = $("<li>");
                
            $("<div class='pinion-comment-name'>name: "+comment.name+"</div>").appendTo($li);
            for(var j = 0, hasLength = has.length; j < hasLength; j++) {
                if(data["has_"+has[j]] && comment[has[j]] != null) {
                    $("<div class='pinion-comment-"+has[j]+"'>"+has[j]+": "+comment[has[j]]+"</div>").appendTo($li);
                }
            }
            $("<div class='pinion-comment-text'>"+comment.text+"</div>").appendTo($li);
            $li.appendTo($pinionComments);
        }    
    };
    
    constr.prototype = {
        constructor: pinion.modules.comment.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));
