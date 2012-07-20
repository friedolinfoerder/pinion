
pinion.on("addModule", function(data) {
    
    var FrontendBox = pinion.FrontendBox,
        $parent = FrontendBox.getParent(),
        // find content element
        $contentElement = $parent.closest(".cms"),
        $newModuleContentElement = jQuery("<div class='cms cms-content'></div>"),
        newModule;

    // insert new element before/after (mode) this module
    $contentElement[data.mode]($newModuleContentElement);
    
    newModule = pinion.Frontend.getModuleElement(data.name, {
        content: {
            areaname: $contentElement.data("element").content.areaname
        }
    }, $newModuleContentElement, true);
    
    newModule.$editButton.click();
    
    FrontendBox.hide();
    
    if($contentElement.hasClass("cms-areaPlaceholder")) {
        $contentElement.remove();
    }
});



pinion.Module = (function($) {
    
    var constr,
        FrontendBox = pinion.FrontendBox,
        userid = pinion.php.userid,
        hasAddModuleButton = (function() {
            var php = pinion.php,
                modules = php.modules,
                permissions = php.permissions;
                
            for(var i in modules) {
                var module = modules[i];
                if(module.isFrontend && pinion.hasPermission(i, "add")) {
                    return true;
                }
            }
            return false;
        }());
    
    constr = function() {};
    
    constr.modulize = function(module) {
        
        module.dirtyElements = {};
        module.validations = {};
        module.elements = {};
        module.indices = {};
            
        // BackendDisplay has also an identifier and is part of the elements
        module.mode = "frontend";
        module.editMode = false;
        module.elements[module.identifier] = module;
        module.settings = $.extend(true, {}, module.globalElementSettings, module.settings);
        module.identifier = module.settings.identifier;
        module.initSettings = $.extend(true, {}, module.settings);
        module.backend = module;
        module.info = module.settings.info;
        module.content = module.settings.content;
        module.data = module.settings.data;
        module.vars = module.settings.vars;
        module.children = [];
        module._isDirty = false;
        module._isChanged = false;
        module._isValid = null;
        module._inizialized = false;
        module.$contents = module.$contentElement.contents();
        module._isOwnData = (module.data.user_id && module.data.user_id == userid);
        module._isOwnContent = (module.content.user_id && module.content.user_id == userid);
        
        module
            .on("dirty", function() {
                module.$contentElement.addClass("dirty");
                module.parent.dirtyElements[module.identifier] = module;
            })
            .on("clean", function() {
                module.$contentElement.removeClass("dirty");
                
                delete module.parent.dirtyElements[module.identifier];
            })
            .on("saveStart", function(data) {
                if(module.mode == "frontend") {
                    if(module.settings._isNew) {
                        data.events.push({
                            module: "page",
                            event: "addContent",
                            info: {
                                identifier: module.identifier, areaname: module.settings.content.areaname
                            }
                        });
                    }
                }
            })
            .on("saveDone", function() {
                module.resetElement(module, true);
            });
        
        
        // mark as invisible when the content is invisible
        if(!module.content.visible) {
            module.$contentElement.addClass("pinion-invisible");
        } 
        if(!module.settings._isNew && module.content.content_id == null && module.content.module != "page") {
            module.$contentElement.addClass("pinion-areaContent");
        }
        
        // show iconBar on hover
        module.$contentElement
            .hover(function() {
                if(!module.editMode){
                    module.$contentElement.addClass("pinion-hover");
                }
            }, function() {
                var $parent = FrontendBox.getParent();
                if(!module.editMode && (!$parent || !$parent.parent().is($iconBar))) {
                    module.$contentElement.removeClass("pinion-hover");
                }
            });
        
        module.saveClick = function() {
            if($submitButton.hasClass("disabled")) return;
                    
            // hide FrontendBox
            FrontendBox.hide();

            // save module
            $submitButton.addClass("disabled");
            module.save();

            // add loader
            loader = new pinion.Loader();
            $submitButton
                .addClass("pinion-loading")
                .append(loader.$element);

            // remove loader
            module.one("invalid", function() {
                $submitButton.removeClass("pinion-loading")
                loader.remove();
            });
            module.one("saveDone", function() {
                $submitButton.removeClass("pinion-loading")
                loader.remove();
            });
        };
        
        var name = module.name = module.settings.moduleName,
            $iconBarWrapper = $("<div class='pinion-Module-iconBarWrapper'></div>"),
            $iconBar = $("<div class='pinion-Module-iconBar'></div>"),
            $editBar = $("<div class='pinion-Module-editBar'></div>"),
            loader,
            // SUBMIT BUTTON
            $submitButton = $("<div class='pinion-backend-icon-submit'></div>")
                .click(module.saveClick)
                .addClass("disabled")
                .appendTo($editBar);
        
        
            
        
        module
            .on("invalid", function() {
                $submitButton.addClass("disabled");
            })
            .on("dirty", function() {
                if(pinion.isEmpty(module.validations)) {
                    $submitButton.removeClass("disabled");
                }
            })
            .on("clean", function() {
                $submitButton.addClass("disabled");
            })
            .on("savePossible", function() {
                $submitButton.removeClass("disabled");
            });
            
                
        pinion.registerHelp($submitButton, "save the content");
                
            // CANCEL BUTTON    
        var $cancelButton = $("<div class='pinion-backend-icon-cancel'></div>")
            .click(function() {
                // hide FrontendBox
                FrontendBox.hide();

                if(module.settings._isNew) {
                    var $contentElement = module.$contentElement;
                    $contentElement.fadeOut(300, function() {

                        pinion.fire("removeModule", module);
                        pinion.Frontend.instance.removeElement(module);
                        $contentElement.remove();
                    });


                } else {
                    
                    // remove edit class
                    module.$contentElement.removeClass("pinion-editMode");
                    
                    module.editMode = false;
                    $iconBarWrapper.css("display", "");
                    
                    // show content elements
                    module.$contents.appendTo(module.$contentElement);
                    
                    // hide edit element
                    module.$element.hide();

                    module.resetElement(module);
                }

            })
            .appendTo($editBar);
                
        pinion.registerHelp($cancelButton, "cancel the edit mode");
        
        // SEPERATOR
        $("<div class='pinion-Module-iconBar-separator'></div>")
            .appendTo($editBar);
        
        // BACKEND BUTTON
        if(pinion.hasPermission(name, "backend")) {
            var $backendButton = pinion.$link("", name)
                .addClass("pinion-backend-icon-backend")
                .appendTo($editBar);

            pinion.registerHelp($backendButton, "go to the backend of the module");
        }
        
        // REFRESH BUTTON
        var $refreshButton = $("<div class='pinion-backend-icon-refresh'></div>")
            .click(function() {
                var newModule;
                
                FrontendBox.hide();
                if(module.settings._isNew) {
                    var $newModuleContentElement = $("<div class='cms cms-content'></div>");
                    
                    // add the new content element after this module
                    module.$contentElement.after($newModuleContentElement);
                    
                    // create new module
                    newModule = pinion.Frontend.getModuleElement(module.name, {
                        data: {
                            areaname: module.content.areaname
                        }
                    }, $newModuleContentElement, true);
                    
                    // delete the old content element
                    pinion.fire("removeModule", module);
                    pinion.Frontend.instance.removeElement(module);
                    module.$contentElement.remove();
                } else {
                    newModule = pinion.Frontend.replaceContent(module.settings.contentId, pinion.php.content[module.settings.contentId].html);
                }
                
                newModule.$editButton.click();
            })
            .appendTo($editBar);

        pinion.registerHelp($refreshButton, "refresh the editor");
        
            
        // CONTENT BUTTON
        if(pinion.hasPermission(name, "add existing") || pinion.hasPermission(name, "add existing of own")) {

            // SEPERATOR
            $("<div class='pinion-Module-iconBar-separator'></div>")
                .appendTo($editBar);

            var $contentButton = $("<div class='pinion-backend-icon-content'></div>")
                .click(function() {
                    if($contentButton.hasClass("pinion-active")) {
                        FrontendBox.hide();
                    } else {
                        FrontendBox.show($contentButton, pinion.frontend.ContentList.show(module), {steps: 250});
                    }
                })
                .appendTo($editBar);

            pinion.registerHelp($contentButton, "add existing content");
        }

        // MINIMIZE BUTTON OF THE EDITBAR
        var $minimizeButton = $("<div class='pinion-Module-editBar-arrow'></div>")
            .click(pinion.Module.prototype.getMinimizeHandler.call($minimizeButton))
            .appendTo($editBar);
        
        pinion.registerHelp($minimizeButton, "minimize/maximize this bar");
        
        // editBarWrapper (for animation purposes)
        $("<div class='pinion-Module-editBarWrapper'></div>")
            .append($editBar)
            .appendTo(module.$element);
        
        module.$element.hide();
                
        // add dirtyIcon to content element
        module.$dirtyFlag.appendTo(module.$contentElement);
        
        var hasOneIcon = false;
        
        // EDIT ICON
        if(pinion.hasPermission(name, "edit") || (pinion.hasPermission(name, "edit own") && module._isOwnData) || module.settings._isNew) {
            hasOneIcon = true;
            module.$editButton = $("<div class='pinion-backend-icon-edit'></div>")
                .click(function() {
                    
                    if(!module._initialized) {
                        module._inizializeElements(module);
                        module.init();
                        module._initialized = true;
                    }
                    
                    // add edit class
                    module.$contentElement.addClass("pinion-editMode");
                    
                    module.editMode = true;
                    $iconBarWrapper.hide();
                    
                    // hide content elements
                    module.$contents.detach();
                    
                    // show edit element
                    module.$element.show();
                    
                    module.fire("edit");
                    
                    FrontendBox.hide();
                })
                .appendTo($iconBar);
            
            pinion.registerHelp(module.$editButton, "Edit this content");
            
        // REVISION ICON    
            module.$revisionButton = $("<div class='pinion-backend-icon-revision'></div>")
                .click(function() {
                    if(module.$revisionButton.hasClass("pinion-active")) {
                        FrontendBox.hide();
                    } else {
                        FrontendBox.show(module.$revisionButton, pinion.frontend.RevisionList.show(module.name, module.settings.moduleId), {steps: 80});
                    }
                })
                .appendTo($iconBar);
                
            pinion.registerHelp(module.$revisionButton, "Show revisions");
        }
        
        // VISIBLE ICON
        if(pinion.hasPermission(name, "change visibility") || (pinion.hasPermission(name, "change visibility of own") && module._isOwnContent)) {
            hasOneIcon = true;
            var $visible = $("<div class='pinion-backend-icon-visible'></div>")
                .click(function() {
                    
                    pinion.fire("changeVisibility", module);
                    
                    FrontendBox.hide();
                })
                .appendTo($iconBar);
            
            pinion.registerHelp($visible, "Change the visibility of this content");
        }
        
        // AREA CONTENT ICON
        if(pinion.hasPermission(name, "change assignment") || (pinion.hasPermission(name, "change assignment of own") && module._isOwnContent)) {
            hasOneIcon = true;
            var $visible = $("<div class='pinion-backend-icon-belonging'></div>")
                .click(function() {
                    
                    pinion.fire("changeBelonging", module);
                    
                    FrontendBox.hide();
                })
                .appendTo($iconBar);
            
            pinion.registerHelp($visible, "Change belonging (page or area) of content");
        }
        
        // REMOVE ICON
        if(pinion.hasPermission(name, "delete") || (pinion.hasPermission(name, "delete own") && module._isOwnContent)) {
            hasOneIcon = true;
            var $remove = $("<div class='pinion-backend-icon-remove'></div>")
                .click(function() {
                    var $contentElement = module.$contentElement;
                    
                    $contentElement.fadeOut(300, function() {
                        pinion.fire("removeModule", module);
                        module._isDeleted = true;
                        
                        if(module.settings._isNew) {
                            pinion.Frontend.instance.removeElement(module);
                            $contentElement.remove();
                        }
                        
                    });
                    
                    FrontendBox.hide();
                })
                .appendTo($iconBar);
            
            pinion.registerHelp($remove, "Remove this content");
        }
        
        // STYLE ICON
        if(pinion.hasPermission(name, "change style") || (pinion.hasPermission(name, "change style of own") && module._isOwnContent)) {
            hasOneIcon = true;
            module.$style = $("<div class='pinion-backend-icon-style'></div>")
                .click(function() {
                    if(module.$style.hasClass("pinion-active")) {
                        FrontendBox.hide();
                    } else {
                        FrontendBox.show(module.$style, pinion.frontend.StyleList.show(module), {steps: 80});
                    }
                })
                .appendTo($iconBar);
            
            pinion.registerHelp(module.$style, "Change styling");
        }
        
        if(hasAddModuleButton) {
            if(hasOneIcon) {
                // SEPERATOR
                $("<div class='pinion-Module-iconBar-separator'></div>").appendTo($iconBar);
            }
            hasOneIcon = true;
            
            // ADD ICON
            module.$add = $("<div class='pinion-backend-icon-add'></div>")
                .click(function() {
                    if(module.$add.hasClass("pinion-active")) {
                        FrontendBox.hide();
                    } else {
                        FrontendBox.show(module.$add, pinion.frontend.$moduleMenu, {steps: 80});
                    }
                })
                .appendTo($iconBar);

            pinion.registerHelp(module.$add, "Add a new content");
        }
        
        if(hasOneIcon) {
            // MINIMIZE BUTTON OF THE ICONBAR
            var $minimizeIconbarButton = $("<div class='pinion-Module-iconBar-arrow'></div>")
                .click(pinion.Module.prototype.getMinimizeHandler.call($minimizeIconbarButton))
                .appendTo($iconBar);

            pinion.registerHelp($minimizeIconbarButton, "minimize/maximize this bar");
            
            // iconBarWrapper (for animation purposes)
            $iconBarWrapper
                .append($iconBar)
                .appendTo(module.$contentElement);
        }
    };   
    
    constr.prototype = {
        constructor: pinion.Module,
        getMinimizeHandler: function() {
            // closure variables
            var minimized = false,
                buttonWidth,
                barWidth,
                doAnimation = false;

            return function() {
                // hide FrontendBox
                FrontendBox.hide();
                
                var $this = $(this),
                    $bar = $(this).parent();

                if(buttonWidth === undefined) {
                    buttonWidth = $this.outerWidth();
                    barWidth = $bar.outerWidth();
                }
                if(doAnimation) {
                    return false;
                }
                doAnimation = true;

                if(minimized) {
                    $bar.animate({
                        "margin-left": 0
                    }, 300, function() {
                        $bar.removeClass("pinion-minimized");
                        doAnimation = false;
                    });
                } else {
                    $bar.animate({
                        "margin-left": - barWidth + buttonWidth
                    }, 300, function() {
                        $bar.addClass("pinion-minimized");
                        doAnimation = false;
                    });
                }
                minimized = !minimized;
                return false;
            };
        }
    };
    
    return constr;
    
}(jQuery));







pinion.namespace("backend.module.defaultModule");

pinion.backend.module.defaultModule = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div>");
        
        // set events
        settings.events = [{
            event: settings._isNew ? "add" : "edit",
            module: settings.moduleName,
            info: settings._isNew ? {identifier: settings.identifier} : {id: settings.moduleId}
        }];
        
        // group events of module
        settings.groupEvents = true;
    };
    
    constr.prototype = {
        constructor: pinion.backend.module.defaultModule,
        init: function() {
            var settings = this.settings;
            
            this.addChild({
                name: "AjaxSection",
                type: "group",
                data: {
                    event: "defineEditor",
                    module: settings.moduleName,
                    info: {
                        settings: settings
                    }
                },
                validate: "all",
                groupEvents: true
            });
        }
    };
    
    return constr;
    
}(jQuery));





