pinion.FrontendBox = (function($) {
    
    var doAnimation = false,
        $clickStopper,
        $scrollLeft = $("<div class='pinion-FrontendBox-control'><span class='pinion-FrontendBox-icon-arrow'></span></div>")
            .mousedown(function() {
                if(args[2].steps !== undefined) {
                    if(doAnimation) return;
                    
                    
                    
                    if(currentPage > 0) {
                        
                        var $children = args[2].$children,
                            howMany = args[2].howMany,
                            start = (currentPage-1)*howMany,
                            end = start + howMany,
                            $newChildren = $children.slice(start, end).show();
                        
                        $contentInner.css("left", -(wrapperWidth - 10));
                        
                        doAnimation = true;
                        $contentInner.animate({
                            left: 0
                        }, 500, function() {
                            args[2].$shownChildren.hide();
                            args[2].$shownChildren = $newChildren;
                            
                            $currentPage.text(currentPage--);
                            doAnimation = false;
                        });
                    }
                    
                } else {
                    startScrolling(-1);
                    
                    pinion.$document.one("mouseup", function() {
                        stopScrolling();
                    });
                }
            }),
        $currentPage = $("<span class='current'></span>"),
        $totalPage = $("<span class='total'></span>"),
        $pager = $("<div class='pinion-FrontendBox-pager'></div>")
            .append($currentPage)
            .append("<span> / </span>")
            .append($totalPage),
        $scrollRight = $("<div class='pinion-FrontendBox-control'><span class='pinion-FrontendBox-icon-arrow'></span></div>")
            .mousedown(function() {
                if(args[2].steps !== undefined) {
                    if(doAnimation) return;
                    
                    if(currentPage < numPages - 1) {
                        doAnimation = true;
                        
                        var $children = args[2].$children,
                            howMany = args[2].howMany,
                            start = (currentPage+1)*howMany,
                            end = start + howMany,
                            $newChildren = $children.slice(start, end).show();
                        
                        $contentInner.animate({
                            left: -(wrapperWidth - 10)
                        }, 500, function() {
                            $contentInner.css("left", 0);
                            
                            args[2].$shownChildren.hide();
                            args[2].$shownChildren = $newChildren;
                            
                            currentPage++;
                            $currentPage.text(currentPage + 1);
                            doAnimation = false;
                        });
                    }
                } else {
                    startScrolling(1);

                    pinion.$document.one("mouseup", function() {
                        stopScrolling();
                    });
                }
            }),
        $contentInner = $("<div class='pinion-FrontendBox-content'></div>"),
        $contentWrapper = $("<div class='pinion-FrontendBox-contentWrapper'></div>")
            .mousewheel(function(event, delta) {
                if(delta < 0) {
                    $scrollRight.trigger("mousedown");
                } else {
                    $scrollLeft.trigger("mousedown");
                }
                pinion.$document.trigger("mouseup");
                return false;
            })
            .append($contentInner),
        $searchInput = $("<input type='text' />")
            .keyup((function() {
                // closure variables
                var time;
                
                return function() {
                    
                    var $this = $(this);
                
                    var mySearchTime = +new Date();
                    time = mySearchTime;
                    
                    setTimeout(function() {
                        if(time == mySearchTime) {
                            var val = $this.val(),
                                $content = args[1],
                                $lis = $content.children("li");
                                
                            // show all list items
                            $lis.show();
                            
                            if(val == "") {
                                $search.removeClass("search-active");
                                $lis.removeClass("pinion-rejected");
                            } else {
                                $search.addClass("search-active");
                                $lis.each(function() {
                                    var $this = $(this),
                                        index = $this.text().toLowerCase().indexOf(val.toLowerCase());
                                        
                                    if(index === -1) {
                                        $this
                                            .addClass("pinion-rejected")
                                            .hide();
                                    } else {
                                        $this
                                            .removeClass("pinion-rejected");
                                    }
                                });
                            }
                            update();
                        }
                    });
                };
                
                
            }())),
        $search = $("<div class='pinion-backend-menu-search'></div>")
            .append($searchInput)
            .append("<div class='pinion-backend-icon-search'></div>"),
        $frontendBox = $("<div id='pinion-FrontendBox'></div>")
            .append($scrollLeft)
            .append($contentWrapper)
            .append($scrollRight)
            .append($pager)
            .append($search),
        offset,
        height,
        width,
        wrapperWidth,
        marginLeft,
        maxWidth,
        parentWidth,
        parentHeight,
        parentCenter,
        args,
        numPages,
        currentPage,
        show = function($parent, $content, options) {
            if(options === undefined) {
                options = {};
            }
            if(args == null) {
                this.fire("show", {
                    $parent: $parent,
                    $content: $content,
                    options: options
                });
                $parent.addClass("pinion-active");
            } else if(args[0] != $parent) {
                this.fire("hide", {
                    $parent: args[0],
                    $content: args[1],
                    options: args[2]
                });
                this.fire("show", {
                    $parent: $parent,
                    $content: $content,
                    options: options
                });
                args[0].removeClass("pinion-active");
                resetSearch();
                $parent.addClass("pinion-active");
            }
            
            // show the children
            if(options.steps !== undefined) {
                var $children = $content.children(":not(.pinion-rejected)").show();
            }
            
            args = [$parent, $content, options];
            
            // reset and show
            $frontendBox
                .css("height", "")
                .removeClass("pinion-scrollable")
                .show(); 
            $contentWrapper.css("width", 10000); // set width temporarily to 10000
            $contentInner.css("width", "");
            
            
            stopScrolling();
            $contentInner.css("left", "0px");
            
            // set content
            $contentInner.children().detach();
            $contentInner.append($content);
            
            offset = $parent.offset();
            width  = $contentInner.outerWidth();
            marginLeft = 30;
            maxWidth = pinion.$window.width() - 2*marginLeft;
            parentWidth = $parent.outerWidth();
            parentCenter = offset.left + parentWidth/2;
            
            
            
            
            // set width of inner container
            $contentInner.css("width", width - 10);
            
            
            
            // check for scrolling
            var positionLeft;
            if(width > maxWidth) {
                $frontendBox.addClass("pinion-scrollable");
                
                wrapperWidth = maxWidth - 40;
                
                if(options.steps !== undefined) {
                    var numChildren = $children.hide().length,
                        effectiveWidth = wrapperWidth - 10,
                        tooMuch = effectiveWidth%options.steps,
                        realWidth = effectiveWidth-tooMuch,
                        howMany = realWidth/options.steps;
                    
                    args[2].howMany = howMany;
                    args[2].$children = $children;
                    
                    wrapperWidth -= tooMuch;
                    positionLeft = marginLeft + Math.floor(tooMuch/2);
                    numPages = Math.ceil(numChildren / howMany);
                    currentPage = 0;
                    
                    $currentPage.text(1);
                    $totalPage.text(numPages);
                    
                    // show the children (the first ones)
                    args[2].$shownChildren = $children.filter(":lt("+howMany+")").show();
                }
            } else {
                wrapperWidth = width;
                
                if(parentCenter - width/2 < marginLeft) {
                    positionLeft = marginLeft;
                } else if(parentCenter + width/2 > marginLeft + maxWidth) {
                    positionLeft = marginLeft + maxWidth - width;
                } else {
                    positionLeft = parentCenter - width/2;
                }
            }
            
            // set height of frontendbox
            height = $contentInner.outerHeight();
            parentHeight = $parent.outerHeight();
            $frontendBox.css("height", height);
            
            // set top position of frontendbox
            // 60, because the button is 30 pixel and the search bar is about 30 pixel
            if(offset.top - height - 60 > pinion.scroll.top) {
                $frontendBox
                    .removeClass("pinion-bottom")
                    .css("top", offset.top - height);
            } else {
                $frontendBox
                    .addClass("pinion-bottom")
                    .css("top", offset.top + parentHeight);
            }
            
            // set width
            $contentWrapper.css("width", wrapperWidth);
            
            // set left position
            $frontendBox.css("left", positionLeft);
            
            $clickStopper.show();
        },
        hide = function() {
            if(args) {
                resetSearch();
                this.fire("hide", {
                    $parent: args[0],
                    $content: args[1],
                    options: args[2]
                });
                args[0].removeClass("pinion-active");
                $frontendBox.hide();
                stopScrolling();
                args = null;
            }
            $clickStopper.hide();
        },
        resetSearch = function() {
            if($search.hasClass("search-active")) {
                $search.removeClass("search-active");
                $searchInput.val("");
                args[1].children("li").removeClass("pinion-rejected");
            }
        },
        scrollIntervalId = null,
        stopScrolling = function() {
            if(scrollIntervalId != null) {
                clearInterval(scrollIntervalId);
                scrollIntervalId = null;
            }
        },
        startScrolling = function(direction) {
            
            var scrollFunction,
                speed = 5,
                left = parseInt($contentInner.css("left"), 10);
            
            if(direction == 1) {
                scrollFunction = function() {
                    if(left < wrapperWidth - width + speed) {
                        left = wrapperWidth - width;
                        $contentInner.css("left", left);
                        stopScrolling();
                    } else if(width + left > wrapperWidth) {
                        left -= speed;
                        $contentInner.css("left", left);
                    }
                }
            } else {
                scrollFunction = function() {
                    if(left > -speed) {
                        left = 0;
                        $contentInner.css("left", left);
                        stopScrolling();
                    } else if(left < 0) {
                        left += speed;
                        $contentInner.css("left", left);
                    }
                }
            }
            
            scrollIntervalId = setInterval(scrollFunction, 40);
        },
        getParent = function() {
            return args ? args[0] : null;
        },
        update = function() {
            if(args) {
                show.apply(pinion.FrontendBox, args);
            }
        },
        loader = null,
        showLoader = function() {
            if(loader == null) {
                loader = new pinion.Loader("default-40px", 40);
                loader.$element.appendTo($contentWrapper);
            }
        },
        hideLoader = function() {
            loader.remove();
            loader = null;
        };
    
    $(function() {
        $("body").append($frontendBox);
        $clickStopper = $("#pinion-backend-menuClickStopper");
    });
    
    pinion.$window.bind("resize", function() {
        if(args) {
            show.apply(pinion.FrontendBox, args);
        }
    });
    
    return $.extend({
        handlers: {},
        show: show,
        hide: hide,
        update: update,
        getParent: getParent,
        showLoader: showLoader,
        hideLoader: hideLoader
    }, pinion.EventDispatcher.prototype);
    
}(jQuery));