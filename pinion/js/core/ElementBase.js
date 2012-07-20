

pinion.namespace("backend.ElementBase");

pinion.backend.ElementBase = (function($) {
    
    var constr,
        usePlugins = pinion.backend.usePlugins;
    
    constr = function() {};
    
    
    // PUBLIC STATIC FUNCTIONS
    
    /**
     * Change a parent identifier of elements with parent null
     * 
     * @param elements An array with element settings
     * @param parent   The identifier of the new parent
     */
    constr.changeParent = function(elements, parent) {
        var id = parent.identifier;
        for(var i = elements.length; i--; ) {
            if(elements[i].parent == null) {
                elements[i].parent = id;
            }
        }
        return elements;
    };
    
    /**
     * Finds all elements of an element
     * 
     * @param element  An element
     * @param elements An empty object, in which the elements
     *                 will be added 
     */
    constr.getElements = function(element, elements) {
        elements[element.identifier] = element;
        var children = element.children;
        for(var i = children.length; i--; ) {
            constr.getDirtyElements(children[i], elements);
        }
    };
    
    /**
     * Finds all dirty elements of an element
     * 
     * @param element        An element, which got the function isDirty
     * @param dirtyElements  An empty object, in which the dirty elements
     *                       will be added 
     */
    constr.getDirtyElements = function(element, dirtyElements) {
        if(element.isDirty()) {
            dirtyElements[element.identifier] = element;
            var children = element.children;
            for(var i = children.length; i--; ) {
                constr.getDirtyElements(children[i], dirtyElements);
            }
        }
    };
    
    constr.prototype = {
        constructor: pinion.backend.ElementBase,
        globalElementSettings: {
            validate: "dirty",
            groupEvents: false,
            groupMode: "array",
            validators: {},
            dirty: "change",
            events: [],
            info: {},
            listener: {},
            help: null,
            fullHelp: null,
            style: {},
            classes: "",
            lazy: false,
            plugins: [],
            fire: null,
            eval: null
        },
        isDirty: function(element) {
            if(element === undefined) {
                element = this;
            }
            
            return element._isDirty;
        },
        setDirty: function(element) {
            if(element === undefined) {
                element = this;
            }
            
            var isDirty = true,
                parent = element.parent;

            element._isChanged = true;
            element.fire("change", element);
            element.fire("afterChange", element);

            if(element.settings.dirty == "never" || element.fire("dirty") === false) {
                isDirty = false;
            }

            if(element.validate instanceof Function) {
                element.validate();
            }
            
            if(isDirty) {
                element._isDirty = true;
                this.dirtyElements[element.identifier] = element;

                if(parent) {
                    if(!parent.isDirty(parent)) {
                        parent.setDirty(parent);
                    } else {
                        this.setChanged(parent);
                    }
                }

                element.$element.addClass("dirty");
                if(element.$dirtyFlag) {
                    element.$dirtyFlag.addClass("dirty");
                }
                
                
            } else if(parent) {
                this.setChanged(parent);
            }
        },
        setClean: function(element, withoutValidation) {
            if(element === undefined) {
                element = this;
            }
            
            var isClean = true,
                parent = element.parent;

            element._isChanged = false;
            element.fire("change", element);
            element.fire("afterChange", element);

            if(element.settings.dirty == "always" || element.fire("clean", element) === false) {
                isClean = false;
            }

            if(isClean) {
                element._isDirty = false;
                
                if(this.dirtyElements[element.identifier] !== undefined) {
                    delete this.dirtyElements[element.identifier];
                }

                if(parent) {
                    var children = parent.children;
                    var allChildrenClean = true;
                    for(var i = 0, length = children.length; i < length; i++) {
                        if(this.isDirty(children[i])) {
                            allChildrenClean = false;
                            break;
                        }
                    }
                    
                    if(allChildrenClean) {
                        // recursive call
                        parent.setClean(parent);
                        // if there are all children clean, they are also valid
                        for(i = 0; i < length; i++) {
                            this.setValid(children[i]);
                        }
                    } else if(parent.settings.validate == "all") {
                        if(element.validate instanceof Function && withoutValidation !== true) {
                            element.validate();
                        }
                        parent.setChanged(parent);
                    } else {
                        // if this element is clean, it is also valid (if it shouldn't validate all)
                        parent.setValid(element);
                        parent.setChanged(parent);
                    }
                }
                
                element.$element.removeClass("dirty");
                if(element.$dirtyFlag) {
                    element.$dirtyFlag.removeClass("dirty");
                }

            } else if(parent) {
                parent.setChanged(parent);
            }
        },
        setChanged: function(element) {
            if(element === undefined) {
                element = this;
            }
            
            element.fire("change", element);
            element.fire("afterChange");

            var parent = element.parent;
            if(parent) {
                parent.setChanged(parent);
            }
        },
        isInvalid: function(element) {
            if(element === undefined) {
                element = this;
            }
            
            return (element._isValid !== true);
        },
        setValid: function(element) {
            if(element === undefined) {
                element = this;
            }
            
            var _this = this,
                length,
                i;
            
            if(this.validations[element.identifier] !== undefined) {
                delete this.validations[element.identifier];
            }
            
            element._isValid = true;
            
            element.fire("valid");
            element.$element.removeClass("invalid");
            if(element.$dirtyFlag) {
                element.$dirtyFlag.removeClass("invalid");
            }
            

            var parent = element.parent;
            if(parent) {
                var children = parent.children;
                var allChildrenValid = true;
                for(i = 0, length = children.length; i < length; i++) {
                    if(children[i]._isValid === false) {
                        allChildrenValid = false;
                        break;
                    }
                }
                if(allChildrenValid) {
                    // recursive call
                    parent.setValid(parent);
                }
            }   

            if(element.$warning) {
                element.$warning.slideUp(300);
            }
            
            if(pinion.isEmpty(this.validations)) {
                
                if(this.canSave) {
                    var events = this._collectEvents(this.dirtyElements);
                    
                    if(this.mode == "backend") {
                        events.push({
                            event: "backend",
                            module: this.name,
                            info: {}
                        });
                    }
                    
                    _this.fire("saveStart", {events: events});
                    
                    pinion.ajax(events, function() {
                        _this.fire("saveDone");
                    });

                    this.canSave = false;
                } else {
                    if(!pinion.isEmpty(this.dirtyElements)) {
                        this.fire("savePossible");
                    }
                }
            }
        },
        _collectEvents: function(dirtyElements) {
            var events = [],
                _infos = {},
                dirtyElement;

            // loop through dirty elements
            for(var index in dirtyElements) {

                dirtyElement = dirtyElements[index];

                var newEvent,
                    _info,
                    settings = dirtyElement.settings;

                if(dirtyElement.fire("save") === false) continue;

                // loop through events
                for(var i = 0, length = settings.events.length; i < length; i++) {
                    newEvent = settings.events[i];

                    _info = this._getInfo(events, newEvent, dirtyElement, _infos);
                    
                    // add the info to the info object
                    $.extend(_info, newEvent.info, dirtyElement.info);
                }
            }
            return events;
        },
        _hasInfo: function(event, element, infos) {
            var identifier = element.identifier;

            if(infos[identifier] === undefined) {
                infos[identifier] = {};
            }
            if(infos[identifier][event.module] === undefined) {
                infos[identifier][event.module] = {};
            }
            if(infos[identifier][event.module][event.event] === undefined) {
                // if there is no info object, create the info object
                infos[identifier][event.module][event.event] = {};
                return false;
            } else {
                return true;
            }
        },
        _getInfo: function(events, event, element, infos) {
            var identifier = element.identifier,
                module = event.module,
                evt = event.event;

            // if this element has got an info return from this function
            if(this._hasInfo(event, element, infos)) {
                // get the info and return it
                return infos[identifier][module][evt];
            }

            // get the info
            var currentInfo = infos[identifier][module][evt];


            // set group parent and group parent settings
            var groupParent = element.parent,
                groupParentSettings = groupParent ? groupParent.settings : null;

            // if there is a group parent and this group parent does grouping
            // of events, get the info via the groupParent
            if(groupParent && groupParentSettings.groupEvents) {
                // has group parent

                var path = [];

                // find all parents which got no info
                do {
                    var _hasInfo = this._hasInfo(event, groupParent, infos);
                    // get the info and set as current info
                    currentInfo = infos[groupParent.identifier][module][evt];

                    // if there is already an info, set the new
                    // current info and break
                    if(_hasInfo) {

                        if(typeof groupParentSettings.groupEvents === "string") {
                            if(groupParentSettings.groupMode == "array") {
                                // if the group mode is "array", push an
                                // element to the current info and set the
                                // current info to this element
                                var infoPart = {};
                                if(currentInfo[groupParentSettings.groupEvents] === undefined) {
                                    // if the namespace doesn't exist, create an array
                                    currentInfo[groupParentSettings.groupEvents] = [];
                                }
                                currentInfo[groupParentSettings.groupEvents].push(infoPart);
                                // set the current info to the element in the array
                                currentInfo = infoPart;
                            } else {

                                if(currentInfo[groupParentSettings.groupEvents] === undefined) {
                                    // if the namespace doesn't exist, create an object
                                    currentInfo[groupParentSettings.groupEvents] = {};
                                }
                                currentInfo = currentInfo[groupParentSettings.groupEvents];
                            }
                        }

                        break;
                    }
                    // there is no info, so add the element to the path
                    path.push({
                        elem: groupParent,
                        info: currentInfo,
                        namespace: groupParentSettings.groupEvents,
                        mode: groupParentSettings.groupMode
                    });



                    // again: set group parent and group parent settings
                    groupParent = groupParent.parent;
                    groupParentSettings = groupParent ? groupParent.settings : null;



                    // if there is a parent but no grouping of events,
                    // break the loop
                    if(!(groupParent && groupParentSettings.groupEvents)) {
                        // this element has no grouping parents
                        // so add it to the events

                        // make a copy of the event
                        event = $.extend(true, {}, event);
                        // add the info to the event
                        event.info = currentInfo;

                        events.push(event);

                        break;
                    }
                } while(true); 

                // go through all elements in the path and set the info
                for(var i = path.length; i--; ) {
                    var p  = path[i],
                        namespace = p.namespace,
                        mode = p.mode;

                    // set info to current info
                    infos[p.elem.identifier][module][evt] = currentInfo;

                    // if there is a namespace, the current info is changing
                    if(namespace !== true) {
                        if(mode == "array") {
                            currentInfo = currentInfo[namespace] = [{}];
                            currentInfo = currentInfo[0];
                        } else if(mode == "object") {
                            currentInfo = currentInfo[namespace] = {};
                        }
                    }
                }
            } else {
                // this element has no grouping parents
                // so add it to the events

                // make a copy of the event
                event = $.extend(true, {}, event);
                // add the info to the event
                event.info = currentInfo;

                events.push(event);
            }
            // set current info
            infos[identifier][module][evt] = currentInfo;

            // return current info
            return currentInfo;
        },
        setInvalid: function(element, warningText) {
            if(typeof element === "string") {
                warningText = element;
                element = this;
            }
            
            
            
            this.canSave = false;
            this.validations[element.identifier] = element;
            element._isValid = false;

            element.fire("invalid")
            element.$element.addClass("invalid");
            if(element.$dirtyFlag) {
                element.$dirtyFlag.addClass("invalid");
            }
            

            var parent = element.parent;
            if(parent) {
                if(parent._isValid !== false) {
                    // recursive call
                    parent.backend.setInvalid(parent);
                }
            }

            if(warningText && element.$warning) {
                element.$warning
                    .html(warningText)
                    .slideDown(300);
            }
        },
        resetElement: function(element, afterSave) {
            if(element._isDirty || element._isChanged) {

                for(var length = element.children.length, i = length - 1; i >= 0; i--) {
                    var child = element.children[i];
                    // recursive call (children first)
                    this.resetElement(child, afterSave);
                }

                // set the element clean
                if(element._isDirty && element.settings.dirty != "always") {
                    this.setClean(element, true);
                }

                if(afterSave) {
                    // copy the current settings to the initialize settings
                    element.initSettings = $.extend(true, {}, element.settings);
                } else {
                    // copy the initialize settings to the current settings
                    element.settings = $.extend(true, {}, element.initSettings);
                }

                if(element.reset instanceof Function) {
                    // call the individual reset function of the element
                    element.reset(afterSave);
                }
            }
        },
        _inizializeElements: function(instance) {
            var children = instance.children;
            for(var i = 0, length = children.length; i < length; i++) {
                var child = children[i];

                // recursive call (children first)
                this._inizializeElements(child);

                // call init function (if exists)
                if(child.init instanceof Function) {
                    if(!child._inizialized) {
                        child.init();
                        child._inizialized = true;
                        
                        // use plugins
                        usePlugins.call(child);
                    }
                    
                }
                if(child.$warning) {
                    child.$warning.hide();
                }
            }
        },
        addElements: function(elements, start) {
            if(start === undefined) {
                start = this;
            }
            var currentLazyElement,
                currentLazyElementIdentifier;
            for(var i = 0, length = elements.length; i < length; i++) {
                var settings = elements[i];
                
                if(settings.lazy) {
                    // if there is no current lazy element, set the lazy element
                    // The lazy element is the element with the identifier,
                    // which is the same as the property settings.lazy 
                    if(!currentLazyElement) {
                        currentLazyElementIdentifier = settings.lazy;
                        currentLazyElement = this.elements[currentLazyElementIdentifier];
                    }
                    // if the current lazy identifier is the same as
                    // the identifier of the element, delete the lazy setting
                    if(currentLazyElementIdentifier == settings.lazy) {
                        settings.lazy = false;
                    }
                    // add the element to the lazy-loading-elements of the
                    // current lazy element, so it can be loaded, when it's
                    // needed
                    currentLazyElement.elements.push(settings);
                } else {
                    // if there is no lazy property, load the element in the
                    // common way
                    this.doInitialization = false;
                    this.addElement(settings);
                    this.doInitialization = true;
                    
                    // reset the lazy element
                    currentLazyElement = null;
                }
            }
            
            // initialize elements
            this._inizializeElements(start);
        },
        addElement: function(settings, parent) {
            if(settings.namespace === undefined) {
                settings.namespace = "backend";
            }
            var func = pinion[settings.namespace][settings.type][settings.name];
            if(func === undefined) {
                if(settings.namespace == "backend" && settings.type == "module") {
                    func = pinion.backend.module.defaultModule;
                } else {
                    throw Error("pinion."+settings.namespace+"."+settings.type+"."+settings.name+" is undefined!!");
                }
                
            }
            var defaultSettings = func.prototype.defaultSettings || {};

            // prototype functions
            $.extend(func.prototype, pinion.EventDispatcher.prototype);
            
            
            func.prototype.backend = this;
            func.prototype.isDirty = function() {
                return this._isDirty;
            };
            func.prototype.setDirty = function() {
                this.backend.setDirty(this);
                return this;
            };
            func.prototype.setClean = function() {
                if(this.backend == this) return;
                
                this.backend.setClean(this);
                return this;
            };
            func.prototype.setChanged = function() {
                this.backend.setChanged(this);
                return this;
            }
            func.prototype.isInvalid = function() {
                return this.backend.isInvalid(this);
            };
            func.prototype.setValid = function() {
                if(this.backend == this) return;

                this.backend.setValid(this);
                return this;
            };
            
            
            func.prototype.setInvalid = function(msg) {
                this.backend.setInvalid(this, msg);
                return this;
            };
            func.prototype.resetElement = function() {
                this.backend.resetElement(this);
                return this;
            };
            func.prototype.replaceBy = function(settings, parent) {
                if(settings.parent === undefined && parent === undefined) {
                    parent = this.parent;
                }
                return this.backend.replaceElement(this, settings, parent);
            };
            func.prototype.asParentFor = function(elements) {
                constr.changeParent(elements, this);
                return this;
            },
            func.prototype.addElements = function(settingsArray, start) {
                if(start === undefined) {
                    start = this;
                }
                this.backend.addElements(settingsArray, start);
                
                return this;
            };
            func.prototype.addChild = function(settings) {
                return this.backend.addElement(settings, this);
            };
            func.prototype.remove = function() {
                return this.backend.removeElement(this);
            };
            func.prototype.removeChildren = function() {
                if(this.children === undefined) return;

                while(this.children.length > 0) {
                    this.children[0].remove();
                }
            };
            func.prototype.getElement = function(identifier) {
                return this.backend.elements[identifier];
            };
            func.prototype.getChildIndex = function() {
                return this.parent.children.indexOf(this);
            };
            func.prototype.siblings = function() {
                var children = this.parent.children;
                var toReturn = [];
                for(var i = 0, length = children.length; i < length; i++) {
                    var child = children[i];
                    
                    if(child != this) {
                        toReturn.push(child);
                    }
                }
                return toReturn;
            };
            
            // functions to walk through the elements
            func.prototype.next = function() {
                var index = this.getChildIndex(),
                    elements = this.parent.children;
                
                if(index < elements.length - 1) {
                    return elements[index+1];
                }
                return null;
            };
            func.prototype.prev = function() {
                var index = this.getChildIndex();
                    
                if(index > 0) {
                    var elements = this.parent.children;
                    return elements[index-1];
                }
                return null;
            };
            func.prototype.find = function(index) {
                return (this.backend.indices[index] || null);
            };
            
            // jQuery function wrapper
            func.prototype.hide = function() {
                this.$element.hide.apply(this.$element, arguments);
                this.fire("hide");
                return this;
            };
            func.prototype.show = function() {
                this.$element.show.apply(this.$element, arguments);
                this.fire("show");
                return this;
            }
            func.prototype.fadeOut = function(speed, callback) {
                var _this = this;
                this.$element.fadeOut.call(this.$element, speed, function() {
                    callback();
                    _this.fire("hide");
                });
                return this;
            };
            func.prototype.fadeIn = function(speed, callback) {
                var _this = this;
                this.$element.fadeIn.call(this.$element, speed, function() {
                    callback();
                    _this.fire("show");
                });
                return this;
            };
            func.prototype.appendTo = function(element) {
                this.$element.appendTo(element.$element);
                return this;
            };
            func.prototype.append = function(element) {
                this.$element.append(element.$element);
                return this;
            };
            func.prototype.after = function(element) {
                this.$element.after(element.$element);
                return this;
            };
            func.prototype.before = function(element) {
                this.$element.before(element.$element);
                return this;
            };
            func.prototype.insertAfter = function(element) {
                this.$element.insertAfter(element.$element);
                return this;
            };
            func.prototype.insertBefore = function(element) {
                this.$element.insertBefore(element.$element);
                return this;
            };
            
            // merge settings
            settings = $.extend(true, {}, this.globalElementSettings, defaultSettings, settings);
            
            // set identifier
            if(settings.identifier === undefined) {
                settings.identifier = settings.name+"_"+pinion.getId();
            }
            
            // CREATE ELEMENT WITH CONSTRUCTOR
            var element = new func(settings, this);
            
            element.backend = this;
            element.handlers = {};

            element._isDirty = false;
            element._isChanged = false;
            element._isValid = null;
            // save a copy of this settings in the prototype, so you can
            // see what is different (and dirty) at every time
            element.initSettings = $.extend(true, {}, settings);

            // set settings if they are not already set
            if(element.settings === undefined) {
                element.settings = settings;
            }
            // set info
            if(element.info === undefined) {
                element.info = settings.info;
            }
            // set validators
            if(element.validators === undefined) {
                element.validators = settings.validators;
            }
            element.identifier = settings.identifier;

            if(settings.type == "renderer") {
                element.data = settings.data;
            }

            this.elements[element.identifier] = element;
            if(settings.index) {
                this.indices[settings.index] = element;
            }

            // children container
            if(element.$element === undefined && settings.$element) {
                element.$element = settings.$element;
            }
            element.$element.data("element", element);
            
            if(element.$childrenContainer === undefined) {
                element.$childrenContainer = element.$element;
            }
            element.$childrenContainer.data("element", element);

            // add element to parent
            if(parent === undefined) {
                parent = element.parent = (settings.parent === undefined) ? this : this.elements[settings.parent];
            } else {
                if(parent.$childrenContainer === undefined) {
                    parent.$childrenContainer = parent.$element;
                }
                if(parent.children === undefined) {
                    parent.children = [];
                }

                element.parent = parent;
                if(parent.settings !== undefined) {
                    element.settings.parent = parent.settings.identifier;
                }
            }
            if(parent.handlers === undefined) {
                parent.handlers = {};
            }
            if(element.children === undefined) {
                element.children = [];
            }
            element.parent.children.push(element);

            element.$dirtyFlag = new pinion.Resetter(element);

            element.$element
                .css(settings.style)
                .addClass(settings.classes)
                .append(element.$dirtyFlag);
            
            // add to childrenContainer, but only if it's not the same as the
            // element
            if(!element.$element.is(element.parent.$childrenContainer)) {
                element.$element.appendTo(element.parent.$childrenContainer);
            }
            
            // register context and full help
            pinion.registerHelp(element.$element, element.settings.help, element.settings.fullHelp);

            if(this.doInitialization !== false && element.init instanceof Function) {
                element.init();
                element._inizialized = true;
                
                // use plugins
                usePlugins.call(element);
            }

            if(element.$warning) {
                element.$warning.hide();
            }
            
            if(settings.dirty == "always") {
                element.setDirty();
            }
            
            parent.fire("childAdded", {element: element});
            
            // This is an event for elements, which are both in backend and frontend
            pinion.fire("create."+element.identifier, {element: element});
            
            // This is an event for elements, which are only in frontend or
            // backend
            pinion.fire("create."+this.name+"."+element.identifier, {element: element});
            
            var fire = settings.fire;
            if(fire) {
                var type = typeof fire;
                if(type === "object") {
                    pinion.fire(fire.module+"."+fire.event, {element: element});
                } else if(type === "string") {
                    pinion.fire(fire, {element: element});
                }
            }
            
            var eval = settings.eval;
            if(typeof eval === "string") {
                var evalFunction = new Function(eval);
                evalFunction.call(element);
            }
            
            return element;
        },
        removeElement: function(element) {
            this._removeElement(element);
            
            element.$element.remove();
        },
        _removeElement: function(element) {
            element.fire("remove");
            element.off();
            
            // recursive (children first)
            var children = element.children;
            for(var i = children.length; i--; ) {
                this._removeElement(children[i]);
            }
            
            var parentChildren = element.parent.children,
                childIndex = parentChildren.indexOf(element);
            parentChildren.splice(childIndex, 1);
            
            if(element.index) {
                delete this.indices[element.index];
            }
            delete this.dirtyElements[element.identifier];
            delete this.elements[element.identifier];
            
            element = null;
        },
        replaceElement: function(oldElement, settingsOrElement, parent) {
            var newElement;
            if(settingsOrElement.settings === undefined) {
                if(settingsOrElement.identifier === undefined) {
                    settingsOrElement.identifier = oldElement.identifier;
                }
                newElement = this.addElement(settingsOrElement, parent);
            } else {
                newElement = settingsOrElement;
            }
            
            oldElement.$element.after(newElement.$element);
            this.removeElement(oldElement);

            return newElement;
        },
        save: function() {
            this.canSave = true;
            
            
            
            for(var i in this.dirtyElements) {
                var element = this.dirtyElements[i];
                
                var validateAllCheck = function(elem) {
                    if(elem.settings.validate == "all") {
                        var children = elem.children;
                        for(var j = 0, length = children.length; j < length; j++) {
                            var child = children[j];
                            if(child.validate instanceof Function) {
                                this.validations[child.identifier] = child;
                            }
                            validateAllCheck.call(this, child);
                        }
                    }
                };
                validateAllCheck.call(this, element);

                if(element.validate instanceof Function) {
                    this.validations[element.identifier] = element;
                }
            }
            
            var atLeastOneValidation = false;
            for(var validation in this.validations) {
                atLeastOneValidation = true;
                this.validations[validation].validate();
            }
            if(!atLeastOneValidation) {
                this.setValid(this);
            }
        }
    };
    
    return constr;
    
}(jQuery));