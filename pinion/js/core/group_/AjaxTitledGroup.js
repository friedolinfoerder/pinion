


pinion.backend.group.AjaxTitledGroup = (function() {
    
    // create TitledGroup
    var constr;
    
    constr = function(settings) {
        settings.open = false;
        
        this.build(settings)
        
        this.hasLoaded = false;
    };
    
    // inherit from TitledGroup
    pinion.inherit(constr, pinion.backend.group.TitledGroup);
    
    constr.prototype.open = function() {
        if(!this.hasLoaded) {
            this.hasLoaded = true;
            
            this.load(true);
        } else {
            constr.uber.open.call(this);
        }
    };
    constr.prototype.load = function(open) {
        var _this = this,
            loader = new pinion.Loader("darkblue");

        loader.$element.appendTo(this.$h2);

        pinion.ajax(this.settings.data, function(data) {
            var elements = data.elements;
            _this.asParentFor(elements).addElements(elements);

            if(open) {
                // wait a little bit, because the building of the elements
                // needs time
                setTimeout(function() {
                    loader.remove();
                    constr.uber.open.call(_this);
                }, 250);
            } else {
                loader.remove();
            }
        });
    },
    constr.prototype.reload = function() {
        if(this.hasLoaded) {
            constr.uber.close.call(this);
            this.removeChildren();
            this.load(false);
        }
    };
    
    
    return constr;
    
}());


