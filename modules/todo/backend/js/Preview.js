
pinion.namespace("modules.todo");

pinion.modules.todo.Preview = (function($) {
    
    var constr;
    
    constr = function(data) {
        this.$element = $("<div>")
            .append("<div class='pinion-todo-text'>"+data.text+"</div>");
    };
    
    constr.prototype = {
        constructor: pinion.modules.todo.Preview,
        init: function() {
        }
    };
    
    return constr;
    
}(jQuery));
