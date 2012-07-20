

pinion.namespace("pinion.backend.module.Page");

pinion.backend.module.Page = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div></div>");
        
        settings.data.areas = {};
        
        this.initialized = false;
        // add all dirty properties in an object (properties: areas, deleted, visibility, belonging)
        this.dirtyProperties = {};
        // add all dirty areas in an object
        this.dirtyAreas = {};
        this.deletedContents = {};
        this.visibilityContents = {};
        this.belongingContents = {};
        
        this.$areas = {};
        
        // initialize immediately after creation
        pinion.one("createModule", function() {
            if(!this._initialized) {
                this._inizializeElements(this);
                this.init();
                this._initialized = true;
            }
        }, this);
        
        settings.groupEvents = true;
    };
    
    constr.prototype = {
        constructor: pinion.backend.module.Page,
        init: function() {
            var _this = this,
                settings = this.settings,
                $areas = this.$areas;
            
            this.headline = this.addChild({
                name: "Headline",
                type: "text",
                text: settings.vars.title_translated
            });
                
            this.urlInput = this.addChild({
                name: "Textbox",
                type: "input",
                label: "url",
                value: settings.vars.url,
                events: [{
                    event: "edit",
                    module: "page",
                    info: {id: settings.moduleId}
                }]
            });

            this.titleInput = this.addChild({
                name: "TranslationTextbox",
                type: "input",
                label: "title",
                value: settings.vars.title || "",
                events: [{
                    event: "edit",
                    module: "page",
                    info: {id: settings.moduleId}
                }]
            });
            
            // PAGE VARIABLES                                
            this.variablesGroup = this.addChild({
                name: "AjaxTitledGroup",
                type: "group",
                title: "variables",
                groupEvents: true,
                data: {
                    event: "newVariablesEditor",
                    module: "page",
                    info: {}
                }
            });
            
            
            pinion
                .on("createModule", function(module) {
                    
                    var content = module.settings.content,
                        id = content.id ? content.id : module.identifier,
                        areaname = content.areaname;
                        
                    if(areaname) { 
                        if($areas[areaname] === undefined) {
                            var $contentElement = module.$contentElement,
                                $currentAreaParent;
                            $currentAreaParent = $areas[areaname] = $contentElement.parent();
                            
                            $currentAreaParent
                                .data("contentIndex", $currentAreaParent.children().index($contentElement))
                                .data("area", areaname);
                        }
                        
                        if(this.initialized) {
                            this.updateAreaPositions(areaname);
                        }
                    } else {
                        areaname = module.$contentElement.parent().data("area");
                        content.areaname = areaname;
                    }
                
                    if(this.initialized) {
                        this.updateAreaPositions(areaname);
                    }
                    
                }, this)
                .on("createArea", function(data) {
                    var $contentElement = data.$placeholder,
                        $currentAreaParent;
                    $currentAreaParent = $areas[data.area] = $contentElement.parent();

                    $currentAreaParent
                        .data("contentIndex", $currentAreaParent.children().index($contentElement))
                        .data("area", data.area);
                })
                .on("removeModule", function(module) {
                    var areas = this.settings.data.areas,
                        content = module.settings.content,
                        id = content.id,
                        areaname = content.areaname,
                        $area = $areas[areaname],
                        area = areas[areaname];
                        
                    if(area) {
                        this.deletedContents[id] = id;
                        this.dirtyProperties.deleted = true;
                        this.setDirty();
                        this.updateAreaPositions(areaname);
                        
                        if(this.visibilityContents[id] !== undefined) {
                            delete this.visibilityContents[id];
                            if(pinion.isEmpty(this.visibilityContents)) {
                                if(this.dirtyProperties.visibility !== undefined) {
                                    delete this.dirtyProperties.visibility;
                                }
                            }
                        }
                        
                        if(this.belongingContents[id] !== undefined) {
                            delete this.belongingContents[id];
                            if(pinion.isEmpty(this.belongingContents)) {
                                if(this.dirtyProperties.belonging !== undefined) {
                                    delete this.dirtyProperties.belonging;
                                }
                            }
                        }
                        
                        if(pinion.isEmpty(areas[areaname])) {
                            var $placeholderContent = $("<div class='cms cms-areaPlaceholder cms-area-"+areaname+"'></div>");
                            
                            new pinion.frontend.AreaPlaceholder($placeholderContent, areaname);
                            
                            var contentIndex = $area.data("contentIndex"),
                                $beforeContentElement = contentIndex > 0 ? $area.children(":eq("+(contentIndex-1)+")") : false;

                            if($beforeContentElement === false) {
                                $area.prepend($placeholderContent)
                            } else {
                                $beforeContentElement.after($placeholderContent);
                            }
                        }
                    } else if(this.identifier == module.identifier) {
                        pinion.Frontend.instance.deletePage = this.settings.data.id;
                        this.setDirty();
                    }
                }, this)
                .on("changeVisibility", function(module) {
                    
                    var content = module.content,
                        visible = !content.visible;
                        
                    if(!visible) {
                        module.$contentElement.addClass("pinion-invisible");
                    } else {
                        module.$contentElement.removeClass("pinion-invisible");
                    }
                    
                    if(module.initSettings.content.visible != visible) {
                        this.dirtyProperties.visibility = true;
                        this.visibilityContents[content.id] = visible;
                        this.setDirty();
                    } else {
                        delete this.visibilityContents[content.id];
                        if(pinion.isEmpty(this.visibilityContents)) {
                            if(this.dirtyProperties.visibility !== undefined) {
                                delete this.dirtyProperties.visibility;
                            }
                            if(pinion.isEmpty(this.dirtyProperties)) {
                                this.setClean();
                            }
                        }
                        
                    }
                    content.visible = visible;
                }, this)
                .on("changeBelonging", function(module) {
                    var content = module.content;
                    
                    module.$contentElement.toggleClass("pinion-areaContent");
                    
                    if(this.belongingContents[content.id] !== undefined) {
                        delete this.belongingContents[content.id];
                        if(pinion.isEmpty(this.belongingContents)) {
                            if(this.dirtyProperties.belonging !== undefined) {
                                delete this.dirtyProperties.belonging;
                            }
                            if(pinion.isEmpty(this.dirtyProperties)) {
                                this.setClean();
                            }
                        }
                    } else {
                        this.dirtyProperties.belonging = true;
                        this.belongingContents[content.id] = content.id;
                        this.setDirty();
                    }
                }, this)
                .on("frontendDone", function() {
                    
                    // REMOVE ADD BUTTON
                    var $iconBarWrapper = this.$contentElement.children(".pinion-Module-iconBarWrapper");
                    if($iconBarWrapper.length) {
                        var $addButton = $iconBarWrapper.find(".pinion-backend-icon-add");
                        
                        if($addButton.length) {
                            var $siblings = $addButton.siblings();

                            if($siblings.length == 0) {
                                // there is only the add button -> delete the buttons
                                $iconBarWrapper.remove();
                            } else {
                                // there are more buttons -> delete the add button and the seperator
                                var $seperator = $addButton.prev();
                                $addButton.remove();
                                $seperator.remove();
                            }
                        }
                    }
                    
                    // REMOVE REFRESH BUTTON
                    this.$element.find(".pinion-backend-icon-refresh").remove();
                    

                    
                    var areas = this.settings.data.areas = {};
                    var initAreas = this.initSettings.data.areas = {};
                    
                    for(var areaname in this.$areas) {
                        var area = areas[areaname] = [];
                        var initArea = initAreas[areaname] = [];
                        
                        this.$areas[areaname].children(".cms-content:visible").each(function() {
                            var dataOfElement = $(this).data("element");
                            if(dataOfElement) {
                                area.push(dataOfElement.content.id);
                                initArea.push(dataOfElement.content.id);
                            }
                        });
                    }
                    
                    
                    this.initialized = true;
                    
                    var $areas = this.$areas,
                        $area;
                        
                    if(pinion.hasPermission("page", "sort contents")) {
                        for(areaname in $areas) {
                            $area = $areas[areaname];

                            $area
                                .addClass("cms-area-parent")
                                .sortable({
                                    cancel: "input,button,.pinion-editMode", // drag not in editMode
                                    distance: 15,
                                    items: "> .cms-content",
                                    cursor: "move",
                                    placeholder: "pinion-drag-placeholder",
                                    helper: "clone",
                                    connectWith: ".cms-area-parent",
                                    start: function(event, ui) {
                                        ui.item.data("element").fire("startDrag");
                                    },
                                    stop: function(event, ui) {
                                        ui.item.data("element").fire("stopDrag");
                                        ui.item.data("element").fire("positionChanged");
                                    },
                                    update: function(event, ui) {
                                        var areaname = ui.item.data("element").content.areaname;

                                        _this.updateAreaPositions(areaname);
                                    },
                                    receive: function(event, ui) {
                                        var areas = _this.settings.data.areas,
                                            $senderArea = ui.sender,
                                            senderAreaname = $senderArea.data("area"),
                                            $area = $(this),
                                            $placeholder = $area.children(".cms-areaPlaceholder"),
                                            areaname = $area.data("area");

                                        ui.item.data("element").content.areaname = areaname;

                                        _this.updateAreaPositions(senderAreaname);
                                        _this.updateAreaPositions(areaname);

                                        if(pinion.isEmpty(areas[senderAreaname])) {

                                            var $placeholderContent = $("<div class='cms cms-areaPlaceholder cms-area-"+senderAreaname+"'></div>");

                                            new pinion.frontend.AreaPlaceholder($placeholderContent, senderAreaname);

                                            var contentIndex = $senderArea.data("contentIndex"),
                                                length = $senderArea.children(".cms-content").length,
                                                $beforeContentElement = contentIndex > 0 ? $senderArea.children(":eq("+(contentIndex-1)+")") : false;

                                            if($beforeContentElement === false) {
                                                $senderArea.prepend($placeholderContent)
                                            } else {
                                                $beforeContentElement.after($placeholderContent);
                                            }

                                        }
                                        if($placeholder.length > 0) {
                                            $placeholder.remove();
                                        }
                                    }

                                });
                        }
                    }
                }, this);
                    
                
            this.backend
                .off("saveStart") // <- do not render the content again
                .on("saveStart", function(data) {
                    var areas = {},
                        dirtyProperties = this.dirtyProperties;
                    
                    if(dirtyProperties.areas) {
                        var currentAreaOrder = this.settings.data.areas;
                        for(var area in this.dirtyAreas) {
                            areas[area] = currentAreaOrder[area];
                        }
                        
                        data.events.push({
                            module: "page",
                            event: "sort",
                            info: {
                                areas: areas
                            }
                        });
                    }
                    
                    if(dirtyProperties.deleted) {
                        data.events.push({
                            module: "page",
                            event: "deleteContent",
                            info: {
                                contents: this.deletedContents
                            }
                        });
                    }
                    
                    if(dirtyProperties.visibility) {
                        var visibilityContents = this.visibilityContents;
                        data.events.push({
                            module: "page",
                            event: "changeVisibility",
                            info: {
                                contents: visibilityContents
                            }
                        });
                        for(var i in visibilityContents) {
                            var visible = visibilityContents[i],
                                content = pinion.Frontend.getContent(i);
                                
                            content.initSettings.content.visible = visible;
                        }
                    }
                    
                    if(dirtyProperties.belonging) {
                        data.events.push({
                            module: "page",
                            event: "changeBelonging",
                            info: {
                                contents: this.belongingContents
                            }
                        });
                    }
                    
                }, this);
        },
        updateAreaPositions: function(areaname) {
            var ids = [];
            
            this.$areas[areaname].children(".cms-content:visible").each(function() {
                var module = $(this).data("element"),
                    id = module.content.id;
                if(id) {
                    ids.push(id);
                } else {
                    ids.push(module.identifier);
                }
            });
            this.settings.data.areas[areaname] = ids;
            
            this.checkForAreaChanges(areaname, ids);
        },
        checkForAreaChanges: function(areaname, ids) {
            var dirty = false,
                initArea = this.initSettings.data.areas[areaname];
                
            for(var i = 0, length = ids.length; i < length; i++) {
                if(initArea[i] === undefined) {
                    dirty = true;
                    break;
                }
                if(initArea[i] != ids[i]) {
                    dirty = true;
                    break;
                }
            }
            
            if(dirty) {
                this.dirtyProperties.areas = true;
                this.dirtyAreas[areaname] = areaname;
                this.setDirty();
            } else {
                if(this.dirtyAreas[areaname] !== undefined) {
                    delete this.dirtyAreas[areaname];
                }
                if(pinion.isEmpty(this.dirtyAreas)) {
                    if(this.dirtyProperties.areas !== undefined) {
                        delete this.dirtyProperties.areas;
                    }
                    if(pinion.isEmpty(this.dirtyProperties)) {
                        this.setClean();
                    }
                }
            }
        },
        reset: function(afterSave) {
            // go through all dirty areas and set the positions as it were at
            // the beginning
            var $areas = this.$areas,
                initAreas = this.initSettings.data.areas,
                areas = this.settings.data.areas,
                areaname;
            
            if(afterSave) { // AFTER SAVING
                // update the area order after the save
                for(areaname in $areas) {
                    var initArea = areas[areaname] = [];
                    $areas[areaname].children(".cms-content")
                        .each(function() {
                            var dataOfElement = $(this).data("element");
                            if(dataOfElement) {
                                initArea.push(dataOfElement.content.id);
                            }
                        });    
                    initAreas[areaname] = areas[areaname] = initArea;
                }
                // update title
                var title = this.titleInput.$element.find("input:first").val();
                jQuery("title").text(title);
                this.headline.text(title);
                // reload variables
                this.variablesGroup.reload();
            } else { // ONLY RESET
                // reset the visibility
                for(i in this.visibilityContents) {
                    content = pinion.Frontend.getContent(i);

                    pinion.fire("changeVisibility", content);
                }
                
                pinion.Frontend.instance.deletePage = undefined;
                this.$contentElement.show();
            }
            
            // BOTH RESET AND AFTER SAVE
            for(areaname in $areas) {
                var area = areas[areaname],
                    $area = $areas[areaname],
                    contentIndex = $area.data("contentIndex"),
                    $beforeContentElement = contentIndex > 0 ? $area.children(":eq("+(contentIndex-1)+")") : false,
                    elementsLength = area.length;
                
                if(elementsLength > 0) {
                    
                    // remove area placeholder
                    $area.children(".cms-areaPlaceholder").remove();
                    
                    for(var i = elementsLength; i--; ) { // high performance loop
                        var content = pinion.Frontend.getContent(area[i]);
                        
                        // set area name
                        content.content.areaname = areaname;
                        
                        if($beforeContentElement === false) {
                            $area.prepend(content.$contentElement.show());
                        } else {
                            $beforeContentElement.after(content.$contentElement.show());
                        }
                        content.fire("positionChanged");
                    }
                } else if($area.children(".cms-areaPlaceholder").length == 0) {
                    
                    // add area placeholder
                    var $placeholderContent = $("<div class='cms cms-areaPlaceholder cms-area-"+areaname+"'></div>");

                    new pinion.frontend.AreaPlaceholder($placeholderContent, areaname);

                    if($beforeContentElement === false) {
                        $area.prepend($placeholderContent);
                    } else {
                        $beforeContentElement.after($placeholderContent);
                    }
                }
            }
            
            this.dirtyProperties = {};
            this.dirtyAreas = {};
            this.visibilityContents = {};
            this.belongingContents = {};
            this.deletedContents = {};
        }
    };
    
    return constr;
    
}(jQuery));