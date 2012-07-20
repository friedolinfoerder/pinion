
pinion.namespace("pinion.data");

pinion.data.Timeinfo = function(data, clazz) {
    
    var now = new Date().getTime();
    var created = pinion.data.formatDate(data.created, now);
    var updated = pinion.data.formatDate(data.updated, now);

    if(created.type == "time") {
        created = pinion.translate(
            false,
            "created %s %s ago", "<span class='pinion-timeSpecification'>"+created.val+" ",
            pinion.translate(created.unit)+"</span>"
        );
    } else {
        created = pinion.translate(
            false,
            "created on %s", "<span class='pinion-timeSpecification'>"+created.val+"</span>"
        );
    }

    if(updated.type == "time") {
        updated = pinion.translate(
            false,
            "updated %s %s ago", "<span class='pinion-timeSpecification'>"+updated.val+" ",
            pinion.translate(updated.unit)+"</span>"
        );
    } else {
        updated = pinion.translate(
            false,
            "updated on %s", "<span class='pinion-timeSpecification'>"+updated.val+"</span>"
        );
    }
    
    clazz = clazz ? " "+clazz : "";
    
    jQuery("<div class='pinion-timeinfo"+clazz+"'></div>")
        .append("<div class='pinion-backend-icon-clock'></div>")
        .append("<div class='pinion-text'>"+created+", "+updated+"</div>")
        .appendTo(this.$element);
};


pinion.data.formatDate = function(timeStamp, now) {
    var diff = now - timeStamp*1000;
    var diffRounded;
    var unit;
    diff = diff / 1000 / 60; // diff in minutes
    diffRounded = Math.round(diff);

    if(diffRounded < 60) {
        unit = "minutes";
        if(diffRounded < 1) {
            diffRounded = 1;
        }
        if(diffRounded == 1) {
            unit = "minute";
        }
        return {
            type: "time",
            unit: unit,
            val: diffRounded
        };
    }

    diff = diff / 60; // diff in hours
    diffRounded = Math.round(diff);

    if(diff < 24) {
        unit = "hours";
        if(diffRounded == 1) {
            unit = "hour";
        }
        return {
            type: "time",
            unit: unit,
            val: diffRounded
        }
    }

    diff = diff / 24; // diff in days
    diffRounded = Math.round(diff);

    if(diff < 10) {
        unit = "days";
        if(diffRounded == 1) {
            unit = "day";
        }
        return {
            type: "time",
            unit: unit,
            val: diffRounded
        }
    } 

    var date = new Date(timeStamp*1000);
    return {
        type: "date",
        val: date.getDate()+"."+date.getMonth()+"."+date.getFullYear()
    };
};


pinion.data.Userinfo = function(data, clazz) {
    clazz = clazz ? " "+clazz : "";
    
    jQuery("<div class='pinion-authorinfo"+clazz+"'><div class='pinion-backend-icon-user12px-grey'></div></div>")
        .append(jQuery("<div class='pinion-text'></div>").append(pinion.$link(data.user, "permissions", "user")))
        .appendTo(this.$element);
};

pinion.data.Revisioninfo = function(data, clazz) {
    clazz = clazz ? " "+clazz : "";
    jQuery("<div class='pinion-revisioninfo"+clazz+"'><div class='pinion-backend-icon-revision12px-grey'></div><div class='pinion-text'>"+data.revision+"</div></div>").appendTo(this.$element);
};

pinion.data.Info = function(infos, data) {
    var dataNamespace = pinion.data;
    for(var i = 0, length = infos.length; i < length; i++) {
        dataNamespace[infos[i]+"info"].call(this, data, "pinion-"+length+"info");
    }
};

pinion.data.Bar = function(elements) {
    var $bar = jQuery("<div class='pinion-renderer-bar'>").appendTo(this.$element);
    
    for(var i = 0, length = elements.length; i < length; i++) {
        $bar.append(elements[i]);
    }
};

pinion.data.Delete = function(data, callback, clazz) {
    var _this = this,
        clb = (callback instanceof Function) ? callback : function() {
            data.deleted = true;
            _this.fadeOut(300, function() {
                _this.setDirty();
            });
        };
        
    clazz = clazz ? " "+clazz : "";
    
    return jQuery("<div class='pinion-delete"+clazz+"'><div class='pinion-icon'></div><div class='pinion-text'>"+pinion.translate("delete")+"</div></div>")
        .click(clb);
};