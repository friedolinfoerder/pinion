

pinion.backend.list.DataPager = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.currentSite = 0;
        this.finder = [];
        this.dataLength = 0;
        this.hasAllData = false;
        
        this.$element = $("<div class='pinion-backend-list-DataPager'></div>");
    };
    
    constr.prototype = {
        constructor: pinion.backend.list.DataPager,
        defaultSettings: {
            data: [],
            dataPerSite: 10,
            group: "SelectGroup",
            display: {}
        },
        init: function() {
            var _this = this,
                settings = this.settings,
                data = settings.data;
            
            
            if(Array.isArray(data)) {
                this.hasAllData = true;
                this.dataLength = data.length;
                if(this.dataLength > 0) {
                    this.buildGroup();
                    this.finder[this.currentSite] = true;
                    this.buildFinder(data.slice(0, settings.dataPerSite));
                }
            } else {
                data.info = data.info || {};
                data.info.start = 0;
                data.info.end = settings.dataPerSite;
                // if it's an array, get the data with an ajax request
                pinion.ajax(data, function(d) {
                    if(d.dataLength !== undefined) {
                        _this.dataLength = parseInt(d.dataLength, 10);
                    } else {
                        this.hasAllData = true; 
                        _this.dataLength = d.data.length;
                    }
                    if(_this.dataLength > 0) {
                        _this.buildGroup();
                        _this.finder[_this.currentSite] = true;
                        _this.buildFinder(d.data.slice(0, settings.dataPerSite));
                    }
                });
            }
        },
        showSite: function() {
            var index = this.currentSite;
            
            if(this.finder[index] == null) {
                this.finder[index] = true;
                
                var settings = this.settings,
                    dataPerSite = settings.dataPerSite,
                    data;
                
                if(this.hasAllData) {
                    data = settings.data.slice(index*dataPerSite, (index+1)*dataPerSite);
                    this.finder[index] = this.buildFinder(data);
                } else {
                    var _this = this,
                        event = this.settings.data;
                    
                    event.info.start = index*dataPerSite;
                    event.info.end = (index+1)*dataPerSite;
                    
                    var loader = new pinion.Loader("darkblue-40px", 40);
                    loader.$element.appendTo(this.group.children[this.currentSite].$childrenContainer);
                    pinion.ajax(event, function(d) {
                        loader.remove();
                        _this.finder[index] = _this.buildFinder(d.data);
                    });
                }
            }
        },
        buildFinder: function(data) {
            var settings = this.settings,
                display = settings.display;
                
            
            display = this.group.children[this.currentSite].addChild($.extend({}, {
                name: "Finder",
                type: "list",
                data: data
            }, display));
            
            this.fire("addDisplay", {
                display: display
            });
            
            return display;
        },
        buildGroup: function() {
            this.backend.doInitialization = false;
            this.group = this.addChild({
                name: this.settings.group,
                type: "group",
                label: "site",
                groupEvents: !!this.settings.groupEvents
            });
            this.backend.doInitialization = true;
            
            var count = 0;
            for(var i = 0, length = this.dataLength; i < length; i += this.settings.dataPerSite) {
                this.finder.push(null);
                this.group.addChild({
                    name: "TitledSection",
                    type: "group",
                    title: ++count,
                    groupEvents: !!this.settings.groupEvents
                });
            }
            
            // init group
            this.group.init();
            
            // add event listener
            this.group.on("changeContent", function(data) {
                this.currentSite = parseInt(data.headline, 10) - 1;
                this.showSite();
            }, this);
        }
    };
    
    return constr;
    
}(jQuery));
