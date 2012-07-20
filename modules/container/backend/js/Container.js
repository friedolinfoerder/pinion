
pinion.backend.module.Container = (function($) {
    
    var constr,
        modules = pinion.php.modules,
        Frontend = pinion.Frontend,
        FrontendBox = pinion.FrontendBox;
    
    constr = function(settings) {
        this.$element = $("<div></div>");
        
        settings.events = [{
            event: settings._isNew ? "add" : "edit",
            module: "container",
            info: settings._isNew ? {identifier: settings.identifier} : {id: settings.moduleId}
        }];
        
        settings.groupEvents = true;  
    };
    
    constr.prototype = {
        constructor: pinion.backend.module.Container,
        init: function() {
            var _this = this;
            
            if(this.settings._isNew) {
                var selector = this.addChild({
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
                    events: this.settings.events
                });
                
                selector.on("dirty", function() {
                    var data = this.settings.data,
                        value = this.settings.value;
                    for(var i = data.length; i--; ) {
                        var d = data[i];
                        if(d.id == value) {
                            _this.buildModules(d.contents);
                        }
                    }
                });
            } else {
                _this.buildModules(this.settings.vars.elements);
            }
        },
        buildModules: function(contents) {
            if(this.stepGroup) {
                // remove step group
                this.stepGroup.remove();
            }
            
            // add step group
            var _this = this,
                stepGroup = this.stepGroup = this.addChild({
                    name: "StepGroup",
                    type: "group"
                }),
                position = 0,
                isNew = this.settings._isNew,
                i,
                length,
                section;
            
            if(isNew) {
                this.identifiers = {};
                this.ids = {};
                for(i = 0, length = contents.length; i < length; i++) {
                    var content = contents[i],
                        name = content.module,
                        howmany = content.howmany;

                    for(var j = 0; j < howmany; j++) {
                        // add section to step group
                        section = stepGroup.addChild({
                            name: "TitledSection",
                            type: "group",
                            title: modules[name].title
                        });


                        this.backend.doInitialization = false;
                        var module = section.addChild({
                            name: Frontend.getClassName(name),
                            type: "module",
                            moduleId: null,
                            contentId: null,
                            moduleName: name,
                            data: {},
                            content: {
                                visible: false
                            },
                            vars: {},
                            _isNew: isNew
                        });
                        module.name = name;
                        module.mode = "backend";

                        this.backend.doInitialization = true;

                        module._isDeleted = false;

                        if(module.init instanceof Function) {
                            this.backend._inizializeElements(module);
                            module.init();
                            module._initialized = true;
                        }
                        
                        // add existing
                        var $editBar = $("<div class='pinion-Module-editBar'></div>").appendTo(section.$element);
                        
                        if(pinion.hasPermission(name, "add existing")) {

                            var $contentButton = $("<div class='pinion-backend-icon-content'></div>")
                                .click({module: module, position: position, section: section}, function(event) {
                                    var $button = $(this);
                                    if($button.hasClass("pinion-active")) {
                                        FrontendBox.hide();
                                    } else {
                                        var eventData = event.data,
                                            $list = pinion.frontend.ContentList.show(eventData.module);
                                        $list
                                            .off("click")
                                            .on("click", "li.pinion-frontend-content", function() {
                                                
                                                FrontendBox.hide();
                                                
                                                // add id to existing modules
                                                var $this = $(this),
                                                    $preview = $this.children(":last").clone(true),
                                                    id = $this.attr("data-id");
                                                    
                                                _this.ids[eventData.position] = {id: id, module: eventData.module.name};
                                                
                                                // remove module
                                                eventData.module.remove();
                                                
                                                // add preview to section
                                                eventData.section.$element
                                                    .children(".preview")
                                                        .remove()
                                                        .end()
                                                    .prepend($("<div class='preview'>").append($preview));
                                                
                                                // set dirty
                                                _this.setDirty();
                                                
                                                return false;
                                            });
                                        FrontendBox.show($button, $list, {steps: 250});
                                    }
                                })
                                .appendTo($editBar);

                            pinion.registerHelp($contentButton, "add existing content");
                        }
                        
                        this.identifiers[module.identifier] = position++;
                    }
                }
                this.info.modules = this.identifiers;
                this.info.existingModules = this.ids;
            } else {
                this.replace = {};
                for(i = 0, length = contents.length; i < length; i++) {
                    var content = contents[i],
                        name = content.module,
                        module = modules[content.module];
                        
                    if(module) {
                        // add section to step group
                        section = stepGroup.addChild({
                            name: "TitledSection",
                            type: "group",
                            title: module.title
                        });

                        var ajaxSection = section.addChild({
                            name: "AjaxSection",
                            type: "group",
                            data: {
                                event: "getEditModule",
                                module: "page",
                                info: {
                                    id: content.moduleid,
                                    module: content.module
                                }
                            }
                        });
                        
                        // add existing
                        var $editBar = $("<div class='pinion-Module-editBar'></div>").appendTo(section.$element);
                        
                        if(pinion.hasPermission(name, "add existing")) {
                            // add the name to the content, so the ContentList can build the list
                            content.name = name;
                            
                            var $contentButton = $("<div class='pinion-backend-icon-content'></div>")
                                .click({content: content, position: position, section: section, ajaxSection: ajaxSection}, function(event) {
                                    var $button = $(this);
                                    if($button.hasClass("pinion-active")) {
                                        FrontendBox.hide();
                                    } else {
                                        var eventData = event.data,
                                            $list = pinion.frontend.ContentList.show(eventData.content);
                                        $list
                                            .off("click")
                                            .on("click", "li.pinion-frontend-content", function() {
                                                
                                                FrontendBox.hide();
                                                
                                                // add id to existing modules
                                                var $this = $(this),
                                                    $preview = $this.children(":last").clone(true),
                                                    id = $this.attr("data-id");
                                                    
                                                _this.replace[eventData.position] = id;
                                                
                                                // remove ajax section
                                                eventData.ajaxSection.remove();
                                                
                                                // add preview to section
                                                eventData.section.$element
                                                    .children(".preview")
                                                        .remove()
                                                        .end()
                                                    .prepend($("<div class='preview'>").append($preview));
                                                
                                                // set dirty
                                                _this.setDirty();
                                                
                                                return false;
                                            });
                                        FrontendBox.show($button, $list, {steps: 250});
                                    }
                                })
                                .appendTo($editBar);

                            pinion.registerHelp($contentButton, "add existing content");
                        }
                    }
                    
                    position++;
                }
                this.info.replace = this.replace;
            }
        }
    };
    
    return constr;
    
}(jQuery));