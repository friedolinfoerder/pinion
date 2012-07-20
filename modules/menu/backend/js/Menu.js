



pinion.backend.module.Menu = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div></div>");
        
        settings.groupEvents = true;
        settings.validate = "all";
    };
    
    constr.prototype = {
        constructor: pinion.backend.module.Menu,
        init: function() {
            var _this = this,
                settings = this.settings;
            
            var idCounter = 0;

            this.addChild({
                name: "Textbox",
                type: "input",
                label: "name",
                value: settings._isNew ? "" : settings.vars.name,
                validators: {
                    notEmpty: true
                },
                events: [{
                    event: "add",
                    module: "menu",
                    info: {identifier: this.identifier}
                }],
                groupEvents: true
            });

            var addGroup = this.addChild({
                name: "TitledGroup",
                type: "group",
                title: "Menuitems",
                validate: "all"
            });
            
            var url = addGroup.addChild({
                name: "AutoCompleteTextbox",
                type: "input",
                label: "url",
                data: {
                    event: "getUrlData",
                    module: "page",
                    info: {}
                }
            });

            var title = addGroup.addChild({
                name: "Textbox",
                type: "input",
                label: "title"
            });
            
            // BUTTON
            addGroup.addChild({
                name: "Button",
                type: "input",
                label: "add",
                validators: {
                    notEmpty: true
                },
                click: function() {
                    if(url.isDirty()) {
                        _this.list.addData([{
                            id: idCounter++,
                            title: title.settings.value,
                            url: url.settings.value,
                            position: 0,
                            isNew: true,
                            menuitem_id: null
                        }]);
                        addGroup.resetElement();
                    }
                }
            });
                
            if(!settings._isNew) {
                pinion.ajax({
                    event: "getMenuItems",
                    module: "menu",
                    info: {id: _this.settings.moduleId}
                }, function(data) {
                    _this.list.setData(data.menuitems)
                });
            }
            
            this.list = this.addChild({
                name: "Finder",
                type: "list",
                label: "menu items",
                recursive: "menuitem_id",
                renderer: {
                    name: "MenuitemRenderer"
                },
                events: [{
                    event: this.settings._isNew ? "add" : "edit",
                    module: "menu",
                    info: this.settings._isNew ? {} : {id: _this.settings.moduleId}
                }],
                validators: {
                    minOne: true
                },
                selectable: false,
                draggable: true
            });
        }
    };
    
    return constr;
    
}(jQuery));