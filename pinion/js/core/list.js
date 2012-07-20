

// create namespace
pinion.namespace("backend.list");




pinion.backend.list.Finder = (function($) {
        
    // private vars
    var constr,
        loadingString = pinion.translate("loading"),
        sortColumns = function(of, desc) {
            var $this = $(this);
            var dataColumn = $(this).parent().parent().attr("data-column");
            of.$childrenContainer.sortChildren(function(a, b) {
                var valueA = $(a).children().children(".column-"+dataColumn).text().toLowerCase();
                var valueB = $(b).children().children(".column-"+dataColumn).text().toLowerCase();
                
                if(desc) {
                    return valueA < valueB ? 1 : -1;
                } else {
                    return valueA > valueB ? 1 : -1;
                }
            });

            $this
                .closest(".pinion-backend-list-Finder-labels")
                    .find(".pinion-sortedBy")
                        .removeClass("pinion-sortedBy");

            $this.addClass("pinion-sortedBy");
        }
    
    // public API -- constructor
    constr = function(settings, backend) {
        
        // settings
        var _this = this;
        
        settings.positions = [];
        
        this.dragElements = [];
        this.values = [];
        
        
        this.$childrenContainer = $("<ol class='pinion-backend-list-Finder-elements'></ol>");
        this.$dragElement = this.$childrenContainer;
        this.$element = $("<div class='pinion-backend-list-Finder'></div>");
        
        
        this.$floatContainer = $("<div class='pinion-backend-list-floatContainer'></div>").appendTo(this.$element);
        
        this.$scrollContainer = $("<div class='pinion-backend-list-Finder-scroll'></div>")
            .append(this.$childrenContainer)
            .appendTo(this.$floatContainer);
              
        if(settings.label) {
            this.$floatContainer.prepend("<label class='pinion-label'>"+settings.label+"</label>");
            
            this.$inputWrapper = $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$floatContainer);
            
            this.$inputWrapper.append(this.$scrollContainer);
        }
        
        
        
        if(typeof settings.renderer === "object" && settings.renderer.name === undefined) {
            settings.renderer.name = "DataRenderer";
        } else if(typeof settings.renderer === "string") {
            settings.renderer = {name: settings.renderer};
        } else if(settings.renderer === undefined) {
            settings.renderer = {name: "DataRenderer"};
        }
        
        // as default the infoKey is the same as the label
        if(settings.infoKey === undefined) {
            if(settings.label != "") {
                settings.infoKey = settings.label;
            } else {
                settings.infoKey = "value";
            }   
        }
        
        var selectable = settings.selectable;
        if(selectable === true) {
            settings.selectable = {};
        }
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.list.Finder,
        defaultSettings: {
            data: [],
            validators: {},
            selectable: false,
            scrollable: false,
            multiple: false,
            recursive: false
        },
        pushData: function(dataSet) {
            this.addRenderer(dataSet);
            
            this.settings.data = this.settings.data.concat(dataSet);
            this.info.data = this.settings.data;
        },
        addData: function(dataSet) {
            this.setData(this.settings.data.concat(dataSet));
            
            this.setDirty();
        },
        setData: function(dataSet) {
            var _this = this;
            
            this.clearChildrenContainer();
            
            this.settings.data = $.extend([], dataSet);
            this.info.data = this.settings.data;
            
            
            if(dataSet !== undefined && dataSet.length > 0) {
                this.$element.removeClass("pinion-empty");
                
                if(typeof this.settings.recursive === "string") {
                    var startDataSet = [];
                    for(var i = 0, length = dataSet.length; i < length; i++) {
                        var data = dataSet[i];
                        
                        // if the data is deleted continue with next
                        if(data.deleted) continue;
                        
                        if(data[this.settings.recursive] == null) {
                            startDataSet.push(data);
                        } else {
                            if(this.settings.recursiveData[data[this.settings.recursive]] === undefined) {
                                this.settings.recursiveData[data[this.settings.recursive]] = [data];
                            } else {
                                this.settings.recursiveData[data[this.settings.recursive]].push(data);
                            }
                        }
                    }
                    dataSet = startDataSet;
                }
                
                this.$labels = $("<div class='pinion-backend-list-Finder-labels clearfix'>&nbsp;</div>").insertBefore(this.$scrollContainer);
                if(this.settings.renderer.name == "DataRenderer") {
                    this.$labels.html("");
                    
                    this.columnKeys = [];
                    this.$element.addClass("default");
                    this.dataColumns = {};
                    for(var key in dataSet[0]) {
                        if(key == "id") continue;
                        if(key == this.settings.recursive) continue;

                        this.columnKeys.push(key);
                        var labeledKey = key;
                        if(this.settings.label !== undefined && this.settings.label[key] !== undefined) {
                            labeledKey = this.settings.label[key];
                        }

                        var $label = $("<div class='column-"+key+"' data-column='"+key+"'></div>").appendTo(this.$labels);

                        var $sortIcons = $("<div class='pinion-sortIcons'></div>")
                            .appendTo($label);

                        $("<div class='pinion-backend-icon-arrowUp8-grey'></div>")
                            .click(function() {
                                sortColumns.call(this, _this);
                            })
                            .appendTo($sortIcons);

                        $("<div class='pinion-backend-icon-arrowDown8-grey'></div>")
                            .click(function() {
                                sortColumns.call(this, _this, true);
                            })
                            .appendTo($sortIcons);


                        $("<div class='pinion-text'>"+labeledKey+"</div>").appendTo($label);


                        this.dataColumns[key] = [$label];
                    }
                }
                
                this.addRenderer(dataSet);
            } else {
                this.$element.addClass("pinion-empty");
            }
        },
        clearChildrenContainer: function() {
            this.dataColumns = undefined;
            this.settings.recursiveData = {};
            
            // remove old data
            this.$childrenContainer.hide().children("li").remove();
            this.removeChildren();
            
            if(this.$labels) {
                this.$labels.remove();
            }
        },
        addRenderer: function(dataSet, $list) {
            var $loadingStatusbar = $("<div class='pinion-loading-info-statusbar'></div>"),
                $loadingInfo = $("<div class='pinion-loading-info'><div class='pinion-loading-info-text'>"+loadingString+"</div></div>")
                    .append($loadingStatusbar)
                    .appendTo(this.$element);
            
            
            pinion.processArray(dataSet, function(data) {
                
                // if the data is deleted continue with next
                if(data.deleted) return;
                
                if(pinion.backend.renderer[this.settings.renderer.name] !== undefined) {
                    
                    var element = this.addChild($.extend({
                        recursive: this.settings.recursive,
                        type: "renderer",
                        data: data
                    }, this.settings.renderer));
                    
                    if($list === undefined) {
                        element.$element
                            .wrap("<li class='pinion-list-item'></li>")
                    } else {
                        $("<li class='pinion-list-item'></li>")
                            .append(element.$element)
                            .appendTo($list);
                    }
                    
                    this.initRenderer(element);
                    
                } else {
                    $("<div>No Renderer '"+this.settings.renderer.name+"' available!</div>").appendTo(this.$childrenContainer);
                }
                
            }, this, function(status) {
                $loadingStatusbar.css("width", status+"%");
            }, function() {
                var _this = this;
                
                this.$childrenContainer.show();
                this.alignData();
                
                // scrollable
                if(this.$scrollContainer.height() > 600 && this.settings.scrollable) {
                
                    this.$scrollContainer
                        .addClass("scrollContainer")
                        .height(400)
                        .bind('jsp-initialised', function(event, isScrollable) {
                            if(isScrollable) {
                                _this.$element
                                    .addClass("scrollable")
                                    .removeClass("notScrollable");
                            }
                        })
                        .jScrollPane({
                            verticalGutter: -8,
                            showArrows: true
                        });


                } else {
                    this.$element.addClass("notScrollable");
                }
                
                var selectable = this.settings.selectable;
                if(Array.isArray(selectable.items)) {
                    var $children = pinion.$(this.children)
                    for(var i = selectable.items.length; i--; ) {
                        $children.filter("[data-id='"+selectable.items[i]+"']").addClass("ui-selected");
                    }
                }
                
                $loadingInfo.remove();
                this.settings.positions = this.getData();
                
                this.fire("afterDataSet");
                this.validate();
            });
        },
        initRenderer: function(element) {
            var data = element.data;
            
            element.$element.addClass("pinion-list-renderer");

            if(data.id) {
                element.$element.attr("data-id", data.id)
            }

            if(this.settings.recursiveData[data.id] != null) {
                var $ol = $("<ol></ol>");
                this.addRenderer(this.settings.recursiveData[data.id], $ol);
                $ol.insertAfter(element.$element);
            }

            // drag support
            var draggable = {};
            if(this.settings.renderer.draggable instanceof Object) {
                $.extend(draggable, this.settings.renderer.draggable);
            }
            if(data.draggable instanceof Object) {
                $.extend(draggable, data.draggable);
            }

            if(this.settings.renderer.draggable || data.draggable) {
                element.$dragElement = element.$dragElement ? element.$dragElement : element.$element;

                this.dragElements.push({
                    element: element,
                    options: draggable
                });
            }


            if(this.settings.renderer.name == "DataRenderer") {
                var counter = 0;
                var $columns = element.$element.children();
                for(var column in this.dataColumns) {
                    this.dataColumns[column].push($columns.eq(counter));
                    counter++;
                }
            }
            
            element.on("remove", "_domRemove", this);
            element.on("hide", "_domHide", this);
        },
        alignData: function() {
            var maxWidths = {},
                rowWidth = 0;
                
            if(this.dataColumns !== undefined) {
                for(var column in this.dataColumns) {
                    maxWidths[column] = 0;
                    for(var i = 0, length = this.dataColumns[column].length; i < length; i++) {
                        var width = this.dataColumns[column][i].width();
                        if(width > maxWidths[column]) {
                            maxWidths[column] = width;
                        }
                    }
                    for(i = 0; i < length; i++) {
                        this.dataColumns[column][i].width(maxWidths[column]);
                    }
                    rowWidth += this.dataColumns[column][0].outerWidth();
                }
                this.$labels.width(rowWidth);
            }
            
            // add the labels on top of the list (before the top control)
            if(this.$labels != undefined) {
                this.$labels.insertBefore(this.$controlTop);
            }
            
            this.$element.find(".scrollContainer").each(function() {
                $(this).data("jsp").reinitialise({
                    verticalGutter: -8,
                    showArrows: true
                });
            });
        },
        updateScrolling: function() {
            var _this = this;
            
            this.$element
                .removeClass("scrollable")
                .removeClass("notScrollable");
                
            var jsp = this.$scrollContainer.data("jsp");
            
            if(jsp) {
                jsp.destroy();
                
                this.$scrollContainer = this.$floatContainer.find(".scrollContainer")
                    .bind('jsp-initialised', function(event, isScrollable) {
                        if(isScrollable) {
                            _this.$element
                                .addClass("scrollable")
                                .removeClass("notScrollable");
                        } else {
                            _this.$element
                                .addClass("notScrollable");
                        }
                    })
                    .jScrollPane({
                        verticalGutter: -8
                    });
            } else {
                this.$element.addClass("notScrollable");
            }
        },
        init: function() {
            var _this = this,
                length,
                i;
                
            this
                .on("save", function() {
                    this.getData();
                })
                .on("update", function(event) {
                    _this.setData(event.ajaxData);
                    _this.fire("afterUpdate");
                })
                .on("reset", function() {
                    _this.setData(this.initSettings.data);
                    _this.fire("afterReset");
                })
                .on("removeDuplicates", function(info) {
                    var dataNames = info.duplicate.split(",");
                    
                    for(var i = 0, length = dataNames.length; i < length; i++) {
                        dataNames[i] = $.trim(dataNames[i]);
                    }

                    var toRemove = [];

                    for(i = 0, length = info.relation.children.length; i < length; i++) {
                        var relationData = this.children[i].settings.data;


                        for(var j = 0, elementsLength = this.children.length; j < elementsLength; j++) {
                            var elementData = _this.children[j].settings.data;

                            var equal = true;
                            for(var k = 0, duplicatesLength = dataNames.length; k < duplicatesLength; k++) {
                                var dataName = dataNames[k];

                                if($.toJSON(relationData[dataName]) != $.toJSON(elementData[dataName])) {
                                    equal = false;
                                    break;
                                }
                            }
                            if(equal) {
                                // do not remove the children within the loop, do it later in a seperate loop
                                toRemove.push(this.children[j]);
                            }
                        } 
                    }
                    // remove children
                    for(i = 0, length = toRemove.length; i < length; i++) {
                        this.removeRenderer(toRemove[i]);
                    }
                    this.fire("afterRemoveDuplicates");
                });
            
            // add the dirtyFlag to the floatContainer
            this.$dirtyFlag.appendTo(this.$floatContainer);
            
            this.setData(this.settings.data);
            
            
            
            // listener
            for(i in this.settings.listener) {
                var listenerEvent = this.settings.listener[i];
                
                var listenerInfo = i.split(".");
                var source = this.backend.elements[listenerInfo[0]];
                
                var eventFunction;
                if(typeof listenerEvent === "object") {
                    if(listenerEvent.events !== undefined) {
                        eventFunction = function(event, data) {
                            var events = pinion.addInfo(data.triggerEvent.events, data.source.info);
                            pinion.ajax(events, function(data) {
                                for(var evt in data) {
                                    _this.fire(evt, {ajaxData: data[evt]});
                                }
                            });
                        };
                    } else if(listenerEvent.type !== undefined) {
                        eventFunction = function(event, data) {
                            data.triggerEvent.relation = data.source;
                            _this.fire(data.triggerEvent.type, data.triggerEvent);
                        };
                    }
                } else {
                    eventFunction = function(event, data) {
                        _this.fire(data.triggerEvent, data);
                    };
                    
                }
                
                source.on(listenerInfo[1], {source: source, triggerEvent: listenerEvent}, eventFunction);
            }
            
            
                    
            
            // add controls for scrolling
            this.$controlTop = $("<div class='pinion-backend-finder-control'><div class='pinion-backend-icon-arrowUp'></div></div>")
                .mousedown(function() {
                    _this.scrollUp = true;
                    var scrollApi = _this.$scrollContainer.data("jsp");
                    var triggerMousewheel = function() {
                        scrollApi.scrollBy(0, -10);
                        setTimeout(function() {
                            if(_this.scrollUp) {
                                triggerMousewheel();
                            }
                        }, 40);
                    };
                    triggerMousewheel();
                })
                .mouseup(function() {
                    _this.scrollUp = false;
                })
                .insertBefore(this.$scrollContainer);

            $("<div class='pinion-backend-finder-control'><div class='pinion-backend-icon-arrowDown'></div></div>")
                .mousedown(function() {
                    _this.scrollDown = true;
                    var scrollApi = _this.$scrollContainer.data("jsp");
                    var triggerMousewheel = function() {
                        scrollApi.scrollBy(0, 10);
                        setTimeout(function() {
                            if(_this.scrollDown) {
                                triggerMousewheel();
                            }
                        }, 40);
                    };
                    triggerMousewheel();
                })
                .mouseup(function() {
                    _this.scrollDown = false;
                })
                .insertAfter(this.$scrollContainer);
            
            
            
            this.$warning = $("<div class='pinion-backend-warning'></div>")
                .appendTo(this.$floatContainer);
            
            
            if(this.settings.draggable) {
                this.setDraggable(this, this.settings.draggable);
            }
            
            // make draggable
            for(i = 0, length = this.dragElements.length; i < length; i++) {
                this.setDraggable(this.dragElements[i].element, this.dragElements[i].options);
            }
            
            if(this.settings.selectable) {
                // if it's selectable, save the ids in the info-object with the
                // infoKey as key
                if(this.settings.multiple === false) {
                    this.info[this.settings.infoKey] = null;
                } else {
                    this.info[this.settings.infoKey] = [];
                }
                
                var options = {
                    filter: ".pinion-list-renderer",
                    stop: function() {
                        var $selected = pinion.$(_this.children).filter(".ui-selected");
                        // set current values
                        _this.info[_this.settings.infoKey] = [];
                        $selected.each(function() {
                            if(_this.settings.multiple === false) {
                                if($selected.length > 0) {
                                    _this.info[_this.settings.infoKey] = $(this).attr("data-id");
                                } else {
                                    _this.info[_this.settings.infoKey] = null;
                                }
                            } else {
                                _this.info[_this.settings.infoKey].push($(this).attr("data-id"));
                            }
                        });
                        
                        
                        if($selected.length > 0) {
                            _this.setDirty();
                        } else {
                            _this.setClean();
                        }
                    }
                };
                if(!this.settings.multiple) {
                    options.selected = function(event, ui) {
                        $(ui.selected)
                            .siblings(".ui-selected")
                                .removeClass("ui-selected");
                    };
                }
                
                this.$childrenContainer.selectable(options);
            }
        },
        getData: function() {
            var ids = [],
                newData = [],
                _this = this;
            
            var addItems = function($element, parentId) {
                $element.children(".pinion-list-item").each(function(i) {
                    var $this = $(this),
                        $renderer = $this.children(".pinion-list-renderer"),
                        renderer = $renderer.data("element"),
                        id = renderer.data.id;
                        
                    newData.push(renderer.data);
                    
                    if(_this.settings.recursive) {
                        renderer.data[_this.settings.recursive] = parentId;
                        if(renderer.data.position !== undefined) {
                            renderer.data.position = i;
                        }
                        ids.push({
                            id: id,
                            position: i,
                            parent: parentId
                        });
                    } else {
                        if(renderer.data.position !== undefined) {
                            renderer.data.position = i;
                        }
                        ids.push(id);
                    }
                    var $childrenList = $this.children("ol");
                    if($childrenList.length > 0) {
                        addItems($childrenList, id);
                    }
                });
            };
            addItems(this.$childrenContainer, null);
            
            this.settings.data = newData;

            _this.info.data = _this.settings.data;
            
            return ids;
        },
        setDraggable: function(element, options) {
            var _this = this;
            
            var fn;
            if(this.settings.recursive !== false) {
                fn = element.$dragElement.nestedSortable;
            } else {
                fn = element.$dragElement.sortable;
            }
            
            element.$dragElement
                .addClass("pinion-draggable")
                .addClass("pinion-draggable-"+this.settings.identifier);
               
            fn.call(element.$dragElement, {
                toleranceElement: "> .pinion-list-renderer",
                tolerance: 'pointer',
                distance: 20,
                cursor: "move",
                helper: "clone",
                handle: '.pinion-list-renderer',
                items: "li.pinion-list-item",
                placeholder: 'pinion-drag-placeholder',
                forcePlaceholderSize: true,
                forceHelperSize: true,
                connectWith: (pinion.drag.connections[this.identifier] !== undefined) ? pinion.drag.connections[this.identifier] : false,
                appendTo: _this.backend.$outerContainer,
                start: function(event, ui) {
                    pinion.drag.active = true;
                    pinion.drag.index = ui.item.index();
                    
                    ui.helper
                        .mousewheel(function(event, delta, deltaX, deltaY) {
                            _this.backend.$childrenContainer.trigger("mousewheel", [delta, deltaX, deltaY]);
                            return false;
                        })
                        .addClass("pinion-drag");
                },
                beforeStop: function(event, ui) {

                    ui.helper
                        .removeClass("pinion-drag");
                },
                stop: function(event, ui) {
                    pinion.drag.active = false;

//                    _this.$element.find(".scrollContainer").each(function() {
//                        $(this).data("jsp").reinitialise({
//                            verticalGutter: -8,
//                            showArrows: true
//                        });
//                    });
                },
                update: function(event, ui) {
                    
                    _this.updateScrolling();
                    
                    var oldPositions = $.toJSON(_this.settings.positions);
                    
                    var positions = _this.getData();
                    
                    var newPositions = $.toJSON(positions);
                    
                    if(oldPositions != newPositions) {
                        _this.setDirty();
                    } else {
                        _this.setClean();
                    }
                },
                sort: function(event, ui) {
                    
                    if(ui.position.left < 0) {
                        ui.helper.css("margin-left", -ui.position.left);
                    } else {
                        ui.helper.css("margin-left", 0);
                    }
                },
                receive: function(event, ui) {
                    
                    var sendList = ui.sender.data("element");
                    
                    if(ui.item.parent().attr("data-dragMode") == "copy") {
                        ui.sender.eq(pinion.drag.index).before(ui.item.clone(true));
                    } else {
                        ui.sender.find(".scrollContainer").each(function() {
                            $(this).data("jsp").reinitialise({
                                verticalGutter: -8,
                                showArrows: true
                            });
                        });
                        
                        if(ui.sender.children(".pinion-list-item").length == 0) {
                            sendList.$element.addClass("pinion-empty");
                        }
                    }
                    
                    // convert the received renderer into the renderer of this list
                    var receivedElement = ui.item.children(".pinion-list-renderer").data("element");
                    
                    
                    _this.$element.removeClass("pinion-empty");
                    
                    if(_this.settings.renderer.name != receivedElement.settings.name) {
                        
                        receivedElement.fire("convert");
                        
                        receivedElement = receivedElement
                            .replaceBy($.extend({
                                type: "renderer",
                                data: receivedElement.settings.data
                            }, _this.settings.renderer), _this);
                        
                        _this.initRenderer(receivedElement);
                            
                        receivedElement.setDirty();
                        
                        receivedElement.fire("afterConvert");

                    } else {
                        receivedElement.setDirty();
                    }
                    
                    _this.alignData();
                }
            });

            if(typeof options.accept === "string") {
                options.accept = [options.accept];
            }

            if(Array.isArray(options.accept)) {

                for(var j = 0, droppableLength = options.accept.length; j < droppableLength; j++) {
                    var dragAccept = options.accept[j];
                    var $draggables = $(".pinion-draggable-"+dragAccept);
                    if(this.backend.elements[dragAccept] !== undefined && $draggables.length > 0) {
                        
                        var connectionValue = $draggables.first().sortable("option", "connectWith");
                        if(connectionValue === false) {
                            $draggables.sortable("option", "connectWith", ".pinion-draggable-"+this.settings.identifier);
                        } else {
                            $draggables.sortable("option", "connectWith", connectionValue+", .pinion-draggable-"+this.settings.identifier);
                        }
                    } else {
                        if(pinion.drag.connections[dragAccept] !== undefined) {
                            pinion.drag.connections[dragAccept] += ", .pinion-draggable-"+this.settings.identifier;
                        } else {
                            pinion.drag.connections[dragAccept] = ".pinion-draggable-"+this.settings.identifier;
                        }
                        
                    }
                }
            }
            if(options.copy == 0) {
                element.$dragElement.attr("data-dragMode", "move");
            } else {
                element.$dragElement.attr("data-dragMode", "copy");
            }
        },
        removeRenderer: function(renderer) {
            //renderer.$element.parent().remove();
            renderer.remove();
        },
        hideRenderer: function(renderer) {
            renderer.hide();
        },
        _domRemove: function(event) {
            var data = this.settings.data,
                sender = event.sender,
                index = data.indexOf(sender);
                
            // remove li
            sender.$element.parent().remove();
            
            if(index != -1) {
                data.splice(index, 1);
            }
            
            this._domChange();
        },
        _domHide: function(event) {
            event.sender.data.deleted = true;
            
            // hide li
            event.sender.$element.parent().hide();
            
            this._domChange();
        },
        _domChange: function() {
            if(this.children.length == 0) {
                this.$element.addClass("pinion-empty");
            }
            
            if(this.settings.draggable) {
                this.$dragElement.sortable("refresh");
            }
        },
        validate: function() {
            if(this.validators.minOne !== undefined) {
                window.childCon = this.$childrenContainer;
                if(this.children.length == 0) {
                    return this.setInvalid("There must be at least one element");
                }
            }
            if(this.validators.oneSelected !== undefined) {
                if(!(pinion.$(this.children).filter(".ui-selected").length == 1)) {
                    return this.setInvalid("Not exactly one element selected");
                }
            }
            if(this.validators.minOneSelected !== undefined) {
                if(!(pinion.$(this.children).filter(".ui-selected").length > 0)) {
                    return this.setInvalid("Not at least one element selected");
                }
            }
            this.setValid();
        },
        reset: function() {
            this.setData(this.initSettings.data);
        }
    }
    return constr;
    
}(jQuery));





pinion.backend.list.Selector = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        
        this.changeDefaultValues(settings);
        
        this.$element = $("<div class='pinion-backend-list-Selector'></div>");
        this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;
        this.$select = $("<select></select>");
    };
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.list.Selector,
        defaultSettings: {
            value: null,
            noEmptyValue: false,
            data: [],
            description: null,
            idAsDescription: true,
            translateValues: true,
            bigger: false
        },
        changeDefaultValues: function(settings) {
            
            // as default the infoKey is the same as the label
            if(settings.infoKey === undefined) {
                if(settings.label != "") {
                    settings.infoKey = settings.label;
                } else {
                    settings.infoKey = "value";
                }   
            }

            if(settings.value == null && settings.noEmptyValue) {
                var data = settings.data;
                if(data.length > 0) {
                    settings.value = settings.data[0].id;
                }
            }
        },
        init: function() {
            var _this = this,
                settings = this.settings,
                data = settings.data;
            
            if(settings.label) {
                this.$label = $("<label class='pinion-label'>"+pinion.translate(settings.label)+"</label>").prependTo(this.$element);
            }
            
            this.$select
                .change(function() {
                    var value = $(this).children(":selected").attr("data-id");
                    value = (value === undefined) ? null : value;

                    _this.info[_this.settings.infoKey] = _this.settings.value = value;

                    if(value == _this.initSettings.value) {
                        _this.setClean();
                    } else {
                        _this.setDirty();
                    }
                })
                .appendTo(this.$inputWrapper);
            
            this.setData(data);
            
            if(this.settings.bigger) {
                this.$element.addClass("pinion-biggerInput");
            }
        },
        val: function() {
            return this.$select.val();
        },
        validate: function() {
            if(this.validators.notEmpty) {
                if(this.$select.children(":selected").attr("data-id") === this.initSettings.value) {
                    return this.setInvalid("This field is required");
                }
            }
            return this.backend.setValid(this);
        },
        reset: function() {
            var $selected;
            if(this.settings.value == null) {
                $selected = this.$select.children(":first").attr('selected', 'selected');
            } else {
                $selected = this.$select.children("[data-id='"+this.settings.value+"']").attr('selected', 'selected');
            }
            this.info[this.settings.infoKey] = this.settings.value = $selected.attr("data-id");
            
            if(this.settings.dirty == "always") {
                this.setDirty();
            }
        },
        setData: function(data) {
            var _this = this;
            
            if(!Array.isArray(data)) {
                pinion.ajax(data, function(data) {
                    _this.setData(data.data);
                });
                return;
            }
            
            this.settings.data = this.initSettings.data = data;
            
            this.$select.html("");
            
            if(!this.settings.noEmptyValue) {
                this.$select.append("<option></option>");
            }

            this.$warning = $("<div class='pinion-backend-warning'></div>").appendTo(this.$inputWrapper);
            
            
            var length = data.length;
            if(length > 0) {
                
                var description = null;
                if(this.settings.idAsDescription) {
                    description = "id";
                }
                if(this.settings.description) {
                    description = this.settings.description;
                } else {
                    for(var name in data[0]) {
                        if(name == "id") continue;
                        description = name;
                        break;
                    }
                }
                
            
                for(var i = 0; i < length; i++) {

                    var dataRow = data[i];
                    var $option = $("<option></option>").appendTo(this.$select);

                    var id = dataRow.id;
                    // select right value
                    if(id == this.settings.value) {
                        $option.attr("selected", "selected");
                    }
                    $option.attr("data-id", id);
                    
                    if(description) {
                        var value = dataRow[description];
                        if(this.settings.translateValues) {
                            value = pinion.translate(value);
                        }
                        $option.text(value);
                    }
                    
                }
                
            }
            this.setClean();
            this.reset();
        }
    };
    
    return constr;
    
}(jQuery));






pinion.backend.list.UpdateSelector = (function($) {
    
    var constr;
    
    constr = function(settings) {
        var _this = this;
        
        this.changeDefaultValues(settings);
        
        this.$element = $("<div class='pinion-backend-list-UpdateSelector'></div>");
        this.$select = $("<select></select>")
            .on("change", function(event) {
                $(this).hide();
                _this.$display.show();
                var val = _this.$select.val();
                if($.trim(val) == "") {
                    val = "<i>"+pinion.translate("empty string")+"</i>";
                } else if(settings.password) {
                    var valLength = val.length;
                    val = "";
                    while(valLength) {
                        val += "*";
                        valLength--;
                    }
                }
                _this.$displayText.html(val);
            })
            .hide();
        this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;
        
        this.$display = $("<div class='pinion-textContainer'></div>")
            .click(function() {
                $(this).hide();
                _this.$iconPen.hide();
                _this.$select
                    .show()
                    .focus();
            })
            .hover(function() {
                _this.$iconPen.show();
            }, function() {
                _this.$iconPen.hide();
            })
            .appendTo(this.$inputWrapper);
        
        this.$displayText = $("<span class='pinion-text'></span>")
    };
    
    return constr;
    
}(jQuery));

pinion.inherit(pinion.backend.list.UpdateSelector, pinion.backend.list.Selector);

pinion.backend.list.UpdateSelector.prototype.reset = function() {
    pinion.backend.list.UpdateSelector.uber.reset.call(this);
    
    var val = this.$select.children(":selected").val();
    this.$displayText.html(jQuery.trim(val) != "" ? val : "<i>"+pinion.translate("empty string")+"</i>");
}

pinion.backend.list.UpdateSelector.prototype.init = function() {
    pinion.backend.list.UpdateSelector.uber.init.call(this);
    
    var val = this.$select.children(":selected").val();
    if(jQuery.trim(val) == "") {
        val = "<i>"+pinion.translate("empty string")+"</i>";
    } else if(this.settings.password) {
        var valLength = val.length;
        val = "";
        while(valLength) {
            val += "*";
            valLength--;
        }
    }
    
    this.$displayText
        .html(val)
        .appendTo(this.$display);

    this.$iconPen = jQuery("<div class='pinion-backend-icon-pen'></div>")
        .appendTo(this.$display)
        .hide();
}