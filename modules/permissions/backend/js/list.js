
pinion.namespace("backend.permissions");


pinion.backend.permissions.ResourcesFinder = function(settings, backend) {
    
    var element = new pinion.backend.list.Finder(settings, backend);
    
    pinion.on("rule.permissions", function(data) {
        var parent = element.parent;
        if(parent.settings.id == data.id) {
            parent.parent.$tabTitles.children().eq(parent.getChildIndex()).click();
        }
    });
    
    return element;
};