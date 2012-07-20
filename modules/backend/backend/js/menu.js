


jQuery(function($) {
    
    var hoverClass = "keyHovered",
        liHeight = 35, //$("#pinion-backend-menu ul li:first").outerHeight(),
        liWidth = 260, //$("#pinion-backend-menu ul li:first").outerWidth(),
        $backendMenu = $("#pinion-backend-menu"),
        $menuHeadline = $backendMenu.children(".menuHeadline"),
        $clickStopper = $("#pinion-backend-menuClickStopper"),
        doAnimation = false,
        time = 0,
        isOpen = false,
        $hoverElement = $([]),
        hoverTime,
        currentHover;
        
        
    // most recent elements
    var cookieName = "mostRecentElements_"+pinion.php.userid,
        mostRecentElements = $.cookie(cookieName);
    mostRecentElements = mostRecentElements ? mostRecentElements.split(",") : [];
    
    var $mostRecentList = $("<li><a><span class='text'>"+pinion.translate("most recent elements")+"</span><span class='pinion-backend-icon-arrowRight'></span></a><div class='pinion-backend-menu-list'><div class='windowContainer'><ul></ul></div></div></li>")
        .appendTo($menuHeadline.next().children(".windowContainer").children("ul"))
        .find("ul");
    
    var mreCounter = 0,
        modules = pinion.php.modules;
    
    var addMostRecentElement = function(module, href, title) {
        $("<li><a class='withIcon' href='pinion/modules/"+href+"'><span class='icon'><img src='"+module.icon+"' width='25px' /></span><span class='text'>"+title+"</span></a></li>")
            .appendTo($mostRecentList)
            .children("a")
                .click(clickOnMenuItem)
                .textRunner();
    };
    
    var setMostRecent = function($element) {
        var href = $element.attr("href");
        var dataText = $element.attr("data-text");
        dataText ? dataText : $element.text();
        mostRecentElements.push(href.split("/").pop()+";"+dataText);
        while(mostRecentElements.length > 50) {
            mostRecentElements.shift();
        }
        
        var $mostRecentExist = $mostRecentList.children().children("a[href='"+href+"']");
        if($mostRecentExist.length) {
            $mostRecentList.prepend($mostRecentExist.parent());
        } else {
            $mostRecentList
                .prepend($element.removeClass("keyHovered").parent().clone(true))
                .children(":gt(9)")
                    .remove();
        }
        
        
        $.cookie(cookieName, mostRecentElements.join(","), {expires: 30});
    };
    
    
    var createdElements = {};    
    for(var i = mostRecentElements.length; i--; ) {
        var mostRecentElement = mostRecentElements[i],
            data = mostRecentElement.split(";"),
            href = data[0],
            info = href.split("#"),
            module = modules[info[0]];
            
        if(module !== undefined && createdElements[href] === undefined) {
            createdElements[href] = true;
            addMostRecentElement(module, href, data[1]);
            mreCounter++;
        }
        
        if(mreCounter >= 10) {
            break;
        }
    }
    
    
        
        
    
    var clickOnMenuItem = function(event, triggered) {
        var $this = $(this);
        var maxOffsetTop = 37;
        var href = $this.attr("href");
        if(href === undefined) {
            var topPosition = $this.position().top;
            if((topPosition % liHeight) !== 0) {
                topPosition = maxOffsetTop;
            }
            if($this.is($menuHeadline) && topPosition < maxOffsetTop) {
                topPosition = maxOffsetTop;
            }
            
            var $div = $this.next();
            
            if(triggered === undefined) {
                // reset search
                $div
                    .children(".pinion-backend-menu-search")
                        .removeClass("search-active")
                        .children("input")
                            .val("")
                            .keyup();
            }
            
            var $windowContainer = $div.children(".windowContainer").height("");
            var $ul = $windowContainer
                .children("ul")
                    .css("margin-top", 0);
            var $controls = $div
                .toggle()
                .css("top", topPosition)
                .find(".pinion-backend-menu-control")
                    .hide();

            var $search = $div.children(".pinion-backend-menu-search").css("bottom", "");

            if(!$this.hasClass("active")) {
                if($this.is($menuHeadline)) {
                    isOpen = true;
                    pinion.FrontendBox.hide();
                    $clickStopper.show();
                    $this.next().children(".pinion-backend-menu-search").children("input").focus();
                }
                
                $this
                    .addClass("active")
                    .parent()
                        .siblings()
                            .find(".active")
                                .click();
                            
                var ulHeight = $ul.height();
                var ulOffsetTop = $ul.offset().top - pinion.scroll.top;
                var shiftToTop = 0;
                var maxMenuHeight = $(window).height() - maxOffsetTop - 2*liHeight;
                if(ulHeight > maxMenuHeight) {
                    maxMenuHeight -= liHeight;
                    shiftToTop = (ulOffsetTop - maxOffsetTop - liHeight);


                    var windowContainerHeight = maxMenuHeight - (maxMenuHeight % liHeight);
                    var pagesCount = Math.ceil(ulHeight / windowContainerHeight);
                    $windowContainer
                        .height(windowContainerHeight);

                    // add back and forth controls
                    $search
                        .css("bottom", -(20+26));
                    $controls
                        .show()
                        .children(".pager")
                            .children(".current")
                                .text(1)
                                .end()
                            .children(".total")
                                .text(pagesCount)
                } else {
                    var overlap = - $(window).height() + 2*liHeight + ulOffsetTop + ulHeight;
                    if(overlap > 0) {
                        shiftToTop = Math.ceil(overlap / liHeight) * liHeight;
                    }
                }
                $div.css("top", topPosition - shiftToTop);
            } else {
                if($this.is($menuHeadline)) {
                    isOpen = false;
                    $clickStopper.hide();
                }
                
                $this.removeClass("active");
                
                $div
                    .find(".active")
                        .removeClass("active")
                        .end()
                    .find(".pinion-backend-menu-list")
                        .hide()
                        .end();
            }
        } else {
            // set to most recent
            setMostRecent($this);
            
            // set the loader image and save the original image source
            var $icon = $this.children(".icon"),
                loader = new pinion.Loader();
                
            $icon.hide().after(loader.$element);
            
            // make an ajax request
            setTimeout(function() {
                pinion.page(href, function() {
                    // set the original image
                    $icon.show();
                    loader.remove();
                    // close the menu
                    if($menuHeadline.is(":visible")) {
                        $menuHeadline.click();
                    }
                });
            }, 25);
            
        }
        return false;
    };
    
    var updateMenuSizes = function() {
        $backendMenu
            .find(".active")
                .reverse()
                .click()
                .reverse()
                .click();
    };
    
    pinion.$window
        .resize(function() {
            var myTime = new Date().getTime();
            time = myTime;
            setTimeout(function() {
                if(time == myTime && $clickStopper.is(":visible")) {
                    updateMenuSizes();
                }
            }, 50);
        })
        .keyup(function(event) {
            if(event.which == 77) {
                
            }
        });
    
    $backendMenu
        .on("mouseenter", "a", function() {
            $hoverElement.removeClass(hoverClass);
            var $thisHoverElement = $(this);
            $hoverElement = $(this);
            hoverTime = (+new Date());
            var time = hoverTime;
            if(!$hoverElement.is($menuHeadline)) {
                if(!$hoverElement.hasClass("withIcon")) {
                    setTimeout(function() {
                        if(hoverTime == time && $thisHoverElement.is($hoverElement) && !$hoverElement.hasClass("active")) {
                            $hoverElement.click();
                        }
                    }, 150);
                } else {
                    setTimeout(function() {
                        if(hoverTime == time && $thisHoverElement.is($hoverElement)) {
                            $hoverElement.parent().siblings().children(".active").click();
                        }
                    }, 150);
                }
            }
            
            $hoverElement.addClass(hoverClass);
        })
        .on("mouseleave", "a", function() {
            hoverTime = (+new Date());
            $hoverElement.removeClass(hoverClass);
            $hoverElement = $([]);
        })
        .find(".pinion-backend-menu-list")
            .hide()
            .prepend("<div class='pinion-backend-menu-control top'><div class='pinion-backend-icon-arrowUp'></div></div>")
            .append("<div class='pinion-backend-menu-control bottom'><div class='pinion-backend-icon-arrowDown'></div><div class='pager'><span class='current'></span>/<span class='total'></span></div></div>")
            .append("<div class='pinion-backend-menu-search'><input type='text' /><div class='pinion-backend-icon-search'></div></div>")
            .end()
        .find(".pinion-backend-menu-search")
            .children("input")
                .keyup(function(event) {
                    
                    var code = event.which,
                        down = 40,
                        up = 38,
                        enter = 13,
                        right = 39,
                        left = 37;
                    
                    if(code == left || code == right) {
                        event.stopPropagation();
                    }
                    if(code == down || code == up || code == left || code == right || code == enter) return false;
                    
                    var $input = $(this),
                        mySearchTime = new Date().getTime();
                    
                    time = mySearchTime;
                    setTimeout(function() {
                        if(time == mySearchTime) {
                            var val = $input.val();
                            
                            var $windowContainer = $input.closest(".pinion-backend-menu-list").children(".windowContainer");
                            $windowContainer
                                .find("li.search")
                                    .remove()
                                    .end()
                                .find("a.active")
                                    .reverse()
                                    .click();
                            
                            if(val == "") {
                                $windowContainer.find("li").css("display", "block");
                                $input.parent().removeClass("search-active");
                            } else {
                                $input.parent().addClass("search-active");
                                
                                var searchResults = {};
                                var hasResults = false;
                                
                                $windowContainer
                                    .find("li")
                                        .hide()
                                        .each(function() {
                                            var $original = $(this);
                                            var $this = $original.clone(true);
                                            var $a = $this.children("a");
                                            var text = $a.text();
                                            $a.attr("data-text", text);
                                            var index = text.toLowerCase().indexOf(val.toLowerCase());
                                            if(index !== -1) {
                                                var textLength = val.length;
                                                var $textWrapper = $a.children(".textWrapper");
                                                var $textSpan = $textWrapper.children(".text");
                                                $textSpan.html(text.substr(0, index)+"<span class='searchResult'>"+text.substr(index, textLength)+"</span>"+text.substr(index+textLength));
                                                
                                                var path = "<span class='pinion-backend-menu-path'>";
                                                
                                                var $pathElements = $original.parentsUntil("#pinion-backend-menu", "li");
                                                
                                                if($pathElements.length > 0) {
                                                    $pathElements.each(function() {
                                                        path += "<span class='pinion-backend-icon-arrowLeft8'></span>" + $(this).children("a").text();
                                                    });
                                                    path += "</span>";
                                                
                                                    $textSpan.append(path);
                                                }
                                                
                                                
                                                searchResults[text] = $this;
                                                hasResults = true;
                                            }
                                        });
                                
                                
                                if(hasResults) {
                                    var firstItem = true;
                                    for(var index in searchResults) {
                                        var $searchResult = searchResults[index];
                                        $searchResult
                                            .find("li")
                                                .css("display", "block")
                                                .end()
                                            .css("display", "block")
                                            .addClass("search")
                                            .appendTo($windowContainer.children("ul"));
                                        
                                        if(firstItem) {
                                            // select first item
                                            firstItem = false;
                                            $hoverElement = $searchResult.children("a");
                                            $hoverElement.addClass("keyHovered");
                                        }
                                        
                                        // rearrange the width of the text
                                        $searchResult
                                            .children("a")
                                                .children(".textWrapper")
                                                    .css("right", "0")
                                                    .children(".text")
                                                        .css("width", "auto");
                                    }
                                } else {
                                    $hoverElement = $([]);
                                    $("<li class='search noResults'>no results</li>")
                                        .appendTo($windowContainer.children("ul"));
                                }
                            }
                            if($windowContainer.is(":visible")) {
                                // update list size
                                $windowContainer.parent().prev()
                                    .trigger("click", true)
                                    .trigger("click", true);
                            }
                        }
                    }, 250) // set search update time
                })
                .end()
            .end()
        .find(".pinion-backend-menu-control.top")
            .click(function() {
                if(doAnimation) return;
                
                var $window = $(this).next();
                var $ul = $window.children();
                var marginTop = parseInt($ul.css("margin-top"), 10);
                if(marginTop < -1) {
                    doAnimation = true;
                    $ul.animate({
                        "margin-top": marginTop + $window.height() + "px"
                    }, 500, function() {
                        doAnimation = false;
                    });
                    var $siteIndexSpan = $window.next().children(".pager").children(".current");
                    var siteIndex = parseInt($siteIndexSpan.text(), 10);
                    $siteIndexSpan.text(siteIndex-1);
                }
            })
            .end()
        .find(".pinion-backend-menu-control.bottom")
            .click(function() {
                if(doAnimation) return;
                
                var $this = $(this);
                var $window = $this.prev();
                var $ul = $window.children();
                var windowHeight = $window.height();
                var marginTop = parseInt($ul.css("margin-top"), 10);
                if(marginTop > -($ul.height() - $window.height() - 1)) {
                    doAnimation = true;
                    $ul.animate({
                        "margin-top": marginTop - windowHeight + "px"
                    }, 500, function() {
                        doAnimation = false;
                    });
                    var $siteIndexSpan = $this.children(".pager").children(".current");
                    var siteIndex = parseInt($siteIndexSpan.text(), 10);
                    $siteIndexSpan.text(siteIndex+1);
                }
                
            })
            .end()
        .find("a")
            .click(clickOnMenuItem)
            .filter(":not(.menuHeadline)")
                .textRunner()
                .end()
            .end()
        .find(".windowContainer")
            .mousewheel(function(event, delta) {
                var $elementToClick;
                if(delta > 0) {
                    $elementToClick = $(event.currentTarget).prev();
                } else {
                    $elementToClick = $(event.currentTarget).next();
                }
                if($elementToClick.is(":visible")) {
                    $elementToClick.click();
                }
                return false;
            })
            .end();
    
    $clickStopper
        .click(function() {
            $(this).hide();
            if($menuHeadline.hasClass("active")) {
                $menuHeadline.click();
            }
            pinion.FrontendBox.hide();
        });
    
    pinion.closeMenu = function() {
        if($clickStopper.is(":visible")) {
            $clickStopper.click();
        }
    };
    
    
    // NAVIGATE THROUGH THE MENU WITH THE KEYBOARD 
    pinion.$document.keydown(function(event) {
        var code = event.which,
            m = 77,
            down = 40,
            up = 38,
            right = 39,
            left = 37,
            enter = 13;
            
        if(event.ctrlKey && code == m) {
            $menuHeadline.click();
            return false;
        } else if(isOpen) {
            if(code == down) {

                if(!$hoverElement.length || $hoverElement.is(":hidden")) {
                    $hoverElement = $backendMenu.find(".pinion-backend-menu-list.first a:first");
                    $hoverElement.addClass(hoverClass);
                } else {
                    var $parent = $hoverElement.parent(),
                        $next = $parent.next(":visible");
                    $next = $next.length ? $next.children("a") : $parent.siblings("li:visible:first").children("a");
                    if($next.length) {
                        $next.addClass(hoverClass);
                        $hoverElement.removeClass(hoverClass);
                        $hoverElement = $next;
                    }
                }
                return false;
            } else if(code == up) {

                if(!$hoverElement.length || $hoverElement.is(":hidden")) {
                    $hoverElement = $backendMenu.find(".pinion-backend-menu-list.first a:first");
                    $hoverElement.addClass(hoverClass);
                } else {
                    var $parent = $hoverElement.parent(),
                        $prev = $parent.prev(":visible");
                    $prev = $prev.length ? $prev.children("a") : $parent.siblings("li:visible:last").children("a");
                    if($prev.length) {
                        $prev.addClass(hoverClass);
                        $hoverElement.removeClass(hoverClass);
                        $hoverElement = $prev;
                    }
                }
                return false;
            } else if(code == right) {

                if($hoverElement.length && $hoverElement.is(":visible")) {
                    var $ul = $hoverElement.next();
                    if($ul.length) {
                        if($ul.is(":hidden")) {
                            $hoverElement.click();
                        }
                        $next = $ul.find("li:first:visible").children("a");
                        if($next.length) {
                            $next.addClass(hoverClass);
                            $hoverElement.removeClass(hoverClass);
                            $hoverElement = $next;
                            return false;
                        }
                    }
                }
            } else if(code == left) {

                if($hoverElement.length && $hoverElement.is(":visible")) {
                    var $a = $hoverElement.closest(".pinion-backend-menu-list").prev();
                    if($a.is(".menuHeadline")) {
                        // do nothing
                    } else {
                        $hoverElement.removeClass(hoverClass);
                        $a.click().addClass(hoverClass);
                        $hoverElement = $a;
                        return false;
                    }

                }

            } else if(code == enter) {
                
                if($hoverElement.length && $hoverElement.is(":visible")) {
                    var $ul = $hoverElement.next();
                    if($ul.length) {
                        if($ul.is(":hidden")) {
                            $hoverElement.click();
                        }
                        $next = $ul.find("li:first:visible").children("a");
                        if($next.length) {
                            $next.addClass(hoverClass);
                            $hoverElement.removeClass(hoverClass);
                            $hoverElement = $next;
                        }
                    } else {
                        $hoverElement.click();
                    }
                    return false;
                }
                
            }
        }
    });
});