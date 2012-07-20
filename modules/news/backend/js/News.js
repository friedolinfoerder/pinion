
pinion.backend.module.News = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div></div>");
        
        settings.events = [{
            event: settings._isNew ? "add" : "edit",
            module: "News",
            info: settings._isNew ? {identifier: settings.identifier} : {id: settings.moduleId}
        }];
        settings.groupEvents = true;
        settings.validateAll = true;
    };
    
    constr.prototype = {
        constructor: pinion.backend.module.News,
        init: function() {
            this.addChild({
                name: "Selector",
                type: "list",
                label: "structure",
                description: "name",
                translateValues: false,
                data: {
                    event: "getStructures",
                    module: "Container",
                    info: {}
                },
                validators: {
                    notEmpty: true
                },
                events: this.settings.events
            });
            
            this.addChild({
                name: "Slider",
                type: "input",
                label: "count",
                min: 3,
                max: 50,
                value: this.settings.data.count || 10,
                events: this.settings.events
            });
            
            this.addChild({
                name: "Selector",
                type: "list",
                label: "container template",
                infoKey: "template",
                data: {
                    event: "getStyles",
                    module: "page",
                    info: {
                        module: "container"
                    }
                },
                value: this.settings.data.templatepath,
                events: this.settings.events
            });
        }
    };
    
    return constr;
    
}(jQuery));