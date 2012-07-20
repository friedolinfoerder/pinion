

pinion.namespace("backend.text.Headline");

pinion.backend.text.Headline = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.$element = $("<h2 class='pinion-backend-text-Headline'>"+pinion.translate(settings.text)+"</h2>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.text.Headline,
        text: function(str) {
            if(str) {
                this.$element.text(str);
                return this;
            } else {
                return this.$element.text();
            }
        }
    }
    return constr;
    
}(jQuery));



pinion.namespace("backend.text.Code");

pinion.backend.text.Code = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        var output = "";
        if(settings.text !== undefined) {
            output = settings.text;
        } else if(settings.array !== undefined) {
            output = this.parseObject(settings.array, 0);
        }
        
        this.$element = $("<code>"+output+"</code>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.text.Code,
        parseObject: function(obj, level) {
            var output = "",
                margin = (function() {
                    var m = "";
                    var l = level
                    while(l) {
                        m += "    ";
                        l--;
                    }
                    return m;
                }());
            
            if(obj == null) {
                return "null";
            }
            
            switch(typeof obj) {
                case "object":
                    output += "array(\n";
                    var key,
                        length = pinion.length(obj),
                        counter = 0;
                        
                    if(Array.isArray(obj)) {
                        for(; counter < length; counter++) {
                            output += margin+"    "+this.parseObject(obj[counter], level+1);
                            if(counter+1 != length) {
                                output += ","
                            }
                            output += "\n";
                        }
                    } else {
                        for(key in obj) {
                            if(obj.hasOwnProperty(key)) {
                                counter++;
                                output += margin+"    \""+key+"\" => "+this.parseObject(obj[key], level+1);
                                if(counter != length) {
                                    output += ","
                                }
                                output += "\n";
                            }
                        }
                    }
                    
                    output += margin+")";
                    break;
                case "number":
                    output += obj;
                    break;
                case "string":
                    // no break!
                default:
                    output += "\""+obj+"\"";
                    break;
            }
            
            return output;
        }
    }
    return constr;
    
}(jQuery));






