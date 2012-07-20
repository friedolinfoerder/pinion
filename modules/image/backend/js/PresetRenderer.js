

pinion.backend.renderer.PresetRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        this.$element = $("<div class='pinion-backend-renderer-PresetRenderer'></div>");
        
        settings.groupEvents = true;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.PresetRenderer,
        init: function() {
            var _this = this,
                data = this.settings.data,
                functions = data.functions,
                isNew = data.isNew,
                editAllowed = (isNew || pinion.hasPermission("image", "edit preset"));

            this.events = this.settings.events;
            
            
            // PRESET NAME WRAPPER
            var $presetNameWrapper = $("<div class='pinion-presetname'></div>").appendTo(this.$element);
            
            if(editAllowed) {
                // UPDATE TEXTBOX (FOR PRESET NAME)
                this.addChild({
                    type: "input",
                    name: "UpdateTextbox",
                    value: data.name,
                    infoKey: "newName",
                    events: this.events,
                    dirty: isNew ? "always" : "change"
                }).$element.appendTo($presetNameWrapper);
            } else {
                $presetNameWrapper.append("<div class='pinion-preset-name'>"+data.name+"</div>");
            }
            
            
            // FUNCTION LIST
            var functionWrapper = this.addChild({
                name: "Wrapper",
                type: "group",
                $element: $("<div class='pinion-image-actions'></div>").appendTo(this.$element),
                events: this.events
            });
            functionWrapper.$dirtyFlag.hide();
            this.functionWrapper = functionWrapper;


            // ADD SELECTION LIST
            this.addList = this.addChild({
                name: "Selector",
                type: "list",
                data: [
                    {id: "crop"},
                    {id: "resize"},
                    {id: "asGrayscale"},
                    {id: "rotate"}
                ]
            });
            
            
            if(editAllowed) {
                // ADD BUTTON
                $("<div class='pinion-backend-icon-add'></div>")
                    .click(function() {
                        var addList = _this.addList;
                        if(addList.isDirty()) {
                            // hide all children
                            pinion.$(_this.editorWrapper.children).hide();
                            // remove active class
                            functionWrapper.$element.children().removeClass("pinion-active");

                            var func = addList.settings.value;
                            _this.addEditor(func, true)
                                .show()
                                .children[0]
                                    .on("addPresetFunction", function(data) {
                                        _this.addFunction(data.func, true);

                                        this.parent.remove();

                                        _this.checkFunctions();
                                    });


                            addList.resetElement();
                        }
                    })
                    .appendTo(this.$element);
            }
            


            // EDITOR WRAPPER
            this.editorWrapper = this.addChild({
                name: "Wrapper",
                type: "group",
                $element: $("<div class='pinion-image-editorWrapper'></div>").appendTo(this.$element),
                groupEvents: "values"
            });


            // FUNCTIONS
            for(var i = 0, length = data.functions.length; i < length; i++) {
                var func = functions[i];

                this.addFunction(func, false);
            }


            if(editAllowed) {
                // FUNCTION WRAPPER
                functionWrapper.$element
                    .sortable({
                        items: ".pinion-image-action",
                        containment: "parent",
                        stop: function(event, ui) {
                            _this.checkFunctions();
                        }
                    });
            }
            

            if(pinion.hasPermission("image", "delete preset")) {
                // DELETE BUTTON
                pinion.data.Bar.call(this, [
                    pinion.data.Delete.call(this, data, function() {
                        data.deleted = true;
                        _this.fadeOut(300, function() {
                            if(isNew) {
                                _this.remove();
                            } else {
                                _this.setDirty();
                            }
                        });
                    })
                ]);
            }
            

            // CLOCK
            if(data.created && data.updated) {
                pinion.data.Info.call(this, ["Revision", "User", "Time"], data);
            }

            this.info = this.settings.data;
        },
        reset: function() {
            this.functionWrapper.$element.children().remove();
            this.editorWrapper.removeChildren();
            
            var functions = this.settings.data.functions;
            
            for(var i = 0, length = functions.length; i < length; i++) {
                var func = functions[i];
                
                this.addFunction(func);
            }
            
            this.info.deleted = undefined;
        },
        checkFunctions: function() {
            var functions = [],
                functionWrapper = this.functionWrapper,
                hasHiddenFunctions = false;
            
            functionWrapper.$element.children(".pinion-image-action").each(function() {
                var data = $(this).data("data"),
                    newInfo = (data.newId !== undefined) ? {newId: data.newId, name: data.name} : {id: data.id};
                
                if($(this).is(":hidden")) {
                    newInfo.deleted = true;
                    hasHiddenFunctions = true;
                }
                
                functions.push(newInfo);
            });
            this.data.functions = functions;
            
            // if the editor Wrapper is clean, 
            // check if the positions are the same as at the beginning
            // if not -> set dirty
            var initFunctions = this.initSettings.data.functions,
                initFunctionsLength = initFunctions.length;

            if(hasHiddenFunctions || initFunctionsLength !== functions.length) {
                functionWrapper.setDirty();
            } else {
                var dirty = false;
                for(var i = 0; i < initFunctionsLength; i++) {
                    if(initFunctions[i].id != functions[i].id) {
                        dirty = true;
                        break;
                    }
                }
                if(dirty) {
                    functionWrapper.setDirty();
                } else {
                    functionWrapper.setClean();
                }
            }
        },
        addEditor: function(data, withAddButton, isNew) {
            if(typeof data === "string") {
                data = {
                    name: data,
                    values: []
                };
            }
            
            var settings = this.settings,
                name = data.name,
                editor = this.editorWrapper.addChild({
                    name: "Section",
                    type: "group",
                    classes: "pinion-image-action-edit",
                    groupEvents: true
                }).hide();
            
            $("<div class='pinion-image-action-edit-title'><div class='pinion-image-action-icon pinion-icon-"+name+"'></div><div class='pinion-image-action-text'>"+pinion.translate(name)+"</div></div>").appendTo(editor.$element);
            
            editor.addChild({
                name: name.substr(0, 1).toUpperCase()+name.substr(1),
                type: "image",
                data: data,
                withAddButton: (withAddButton === true),
                isNew: isNew,
                newId: isNew ? data.newId : null,
                events: this.events
            });
            editor.info.deleted = undefined;
            
            return editor;
        },
        removeEditor: function(editor) {
            
            // hide all children
            pinion.$(this.editorWrapper.children).hide();
            // remove active class
            this.functionWrapper.$element.children().removeClass("pinion-active");
            
            // hide function element
            var data = editor.settings.data,
                prop = (data.id !== undefined) ? "id" : "newId",
                propFunction = (prop == "id") ? "hide" : "remove";
            
            // delete the function element if it's new and hide it if it's old
            this.functionWrapper.$element.children("[data-"+prop+"="+data[prop]+"]")[propFunction]();
            
            this.checkFunctions();
            
            // remove editor
            editor.parent.remove();
        },
        addFunction: function(data, isNew) {
            var funcName = data.name,
                editor = this.addEditor(data, false, isNew),
                prop = (data.id !== undefined) ? "id" : "newId";
            
            $("<div class='pinion-image-action' data-"+prop+"='"+data[prop]+"'><div class='pinion-image-action-icon40px pinion-icon-"+funcName+"-40px'></div><div class='pinion-image-action-text'>"+pinion.translate(funcName)+"</div></div>")
                .data("editor", editor)
                .data("data", data)
                .click(function() {
                    var $this = $(this),
                        $element = $this.data("editor").$element;
                    
                    // hide all siblings
                    $element.siblings().hide();
                    // remove active class
                    $this.siblings().removeClass("pinion-active");
                    
                    // toggle visibility of editor
                    $element.toggle();
                    // toggle active class
                    $this.toggleClass("pinion-active");
                })
                .appendTo(this.functionWrapper.$element);
        }
    };
    
    return constr;
    
}(jQuery));


