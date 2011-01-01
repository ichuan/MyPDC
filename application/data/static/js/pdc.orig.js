// jQuery plugins: Cookie, Autocomplete

// Cookie plugin
// Copyright (c) 2006 Klaus Hartl (stilbuero.de)
// http://plugins.jquery.com/files/jquery.cookie.js.txt
jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') {
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString();
        }
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else {
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};

// Autocomplete plugin
// Copyright (c) 2007 Dylan Verheul, Dan G. Switzer, Anjesh Tuladhar, Jörn Zaefferer
// Revision: $Id: jquery.autocomplete.js 5785 2008-07-12 10:37:33Z joern.zaefferer $
(function($) {
    
$.fn.extend({
    autocomplete: function(urlOrData, options) {
        var isUrl = typeof urlOrData == "string";
        options = $.extend({}, $.Autocompleter.defaults, {
            url: isUrl ? urlOrData : null,
            data: isUrl ? null : urlOrData,
            delay: isUrl ? $.Autocompleter.defaults.delay : 10,
            max: options && !options.scroll ? 10 : 150
        }, options);
        
        // if highlight is set to false, replace it with a do-nothing function
        options.highlight = options.highlight || function(value) { return value; };
        
        // if the formatMatch option is not specified, then use formatItem for backwards compatibility
        options.formatMatch = options.formatMatch || options.formatItem;
        
        return this.each(function() {
            new $.Autocompleter(this, options);
        });
    },
    result: function(handler) {
        return this.bind("result", handler);
    },
    search: function(handler) {
        return this.trigger("search", [handler]);
    },
    flushCache: function() {
        return this.trigger("flushCache");
    },
    setOptions: function(options){
        return this.trigger("setOptions", [options]);
    },
    unautocomplete: function() {
        return this.trigger("unautocomplete");
    }
});

$.Autocompleter = function(input, options) {

    var KEY = {
        UP: 38,
        DOWN: 40,
        DEL: 46,
        TAB: 9,
        RETURN: 13,
        ESC: 27,
        COMMA: 188,
        PAGEUP: 33,
        PAGEDOWN: 34,
        BACKSPACE: 8
    };

    // Create $ object for input element
    var $input = $(input).attr("autocomplete", "off").addClass(options.inputClass);

    var timeout;
    var previousValue = "";
    var cache = $.Autocompleter.Cache(options);
    var hasFocus = 0;
    var lastKeyPressCode;
    var config = {
        mouseDownOnSelect: false
    };
    var select = $.Autocompleter.Select(options, input, selectCurrent, config);
    
    var blockSubmit;
    
    // prevent form submit in opera when selecting with return key
    $.browser.opera && $(input.form).bind("submit.autocomplete", function() {
        if (blockSubmit) {
            blockSubmit = false;
            return false;
        }
    });
    
    // only opera doesn't trigger keydown multiple times while pressed, others don't work with keypress at all
    $input.bind(($.browser.opera ? "keypress" : "keydown") + ".autocomplete", function(event) {
        // track last key pressed
        lastKeyPressCode = event.keyCode;
        switch(event.keyCode) {
        
            case KEY.UP:
                event.preventDefault();
                if ( select.visible() ) {
                    select.prev();
                } else {
                    onChange(0, true);
                }
                break;
                
            case KEY.DOWN:
                event.preventDefault();
                if ( select.visible() ) {
                    select.next();
                } else {
                    onChange(0, true);
                }
                break;
                
            case KEY.PAGEUP:
                event.preventDefault();
                if ( select.visible() ) {
                    select.pageUp();
                } else {
                    onChange(0, true);
                }
                break;
                
            case KEY.PAGEDOWN:
                event.preventDefault();
                if ( select.visible() ) {
                    select.pageDown();
                } else {
                    onChange(0, true);
                }
                break;
            
            // matches also semicolon
            case options.multiple && $.trim(options.multipleSeparator) == "," && KEY.COMMA:
            case KEY.TAB:
            case KEY.RETURN:
                if( selectCurrent() ) {
                    // stop default to prevent a form submit, Opera needs special handling
                    event.preventDefault();
                    blockSubmit = true;
                    return false;
                }
                break;
                
            case KEY.ESC:
                select.hide();
                break;
                
            default:
                clearTimeout(timeout);
                timeout = setTimeout(onChange, options.delay);
                break;
        }
    }).focus(function(){
        // track whether the field has focus, we shouldn't process any
        // results if the field no longer has focus
        hasFocus++;
    }).blur(function() {
        hasFocus = 0;
        if (!config.mouseDownOnSelect) {
            hideResults();
        }
    }).click(function() {
        // show select when clicking in a focused field
        if ( hasFocus++ > 1 && !select.visible() ) {
            onChange(0, true);
        }
    }).bind("search", function() {
        // TODO why not just specifying both arguments?
        var fn = (arguments.length > 1) ? arguments[1] : null;
        function findValueCallback(q, data) {
            var result;
            if( data && data.length ) {
                for (var i=0; i < data.length; i++) {
                    if( data[i].result.toLowerCase() == q.toLowerCase() ) {
                        result = data[i];
                        break;
                    }
                }
            }
            if( typeof fn == "function" ) fn(result);
            else $input.trigger("result", result && [result.data, result.value]);
        }
        $.each(trimWords($input.val()), function(i, value) {
            request(value, findValueCallback, findValueCallback);
        });
    }).bind("flushCache", function() {
        cache.flush();
    }).bind("setOptions", function() {
        $.extend(options, arguments[1]);
        // if we've updated the data, repopulate
        if ( "data" in arguments[1] )
            cache.populate();
    }).bind("unautocomplete", function() {
        select.unbind();
        $input.unbind();
        $(input.form).unbind(".autocomplete");
    });
    
    
    function selectCurrent() {
        var selected = select.selected();
        if( !selected )
            return false;
        
        var v = selected.result;
        previousValue = v;
        
        if ( options.multiple ) {
            var words = trimWords($input.val());
            if ( words.length > 1 ) {
                v = words.slice(0, words.length - 1).join( options.multipleSeparator ) + options.multipleSeparator + v;
            }
            v += options.multipleSeparator;
        }
        
        $input.val(v);
        hideResultsNow();
        $input.trigger("result", [selected.data, selected.value]);
        return true;
    }
    
    function onChange(crap, skipPrevCheck) {
        if( lastKeyPressCode == KEY.DEL ) {
            select.hide();
            return;
        }
        
        var currentValue = $input.val();
        
        if ( !skipPrevCheck && currentValue == previousValue )
            return;
        
        previousValue = currentValue;
        
        currentValue = lastWord(currentValue);
        if ( currentValue.length >= options.minChars) {
            $input.addClass(options.loadingClass);
            if (!options.matchCase)
                currentValue = currentValue.toLowerCase();
            request(currentValue, receiveData, hideResultsNow);
        } else {
            stopLoading();
            select.hide();
        }
    };
    
    function trimWords(value) {
        if ( !value ) {
            return [""];
        }
        var words = value.split( options.multipleSeparator );
        var result = [];
        $.each(words, function(i, value) {
            if ( $.trim(value) )
                result[i] = $.trim(value);
        });
        return result;
    }
    
    function lastWord(value) {
        if ( !options.multiple )
            return value;
        var words = trimWords(value);
        return words[words.length - 1];
    }
    
    // fills in the input box w/the first match (assumed to be the best match)
    // q: the term entered
    // sValue: the first matching result
    function autoFill(q, sValue){
        // autofill in the complete box w/the first match as long as the user hasn't entered in more data
        // if the last user key pressed was backspace, don't autofill
        if( options.autoFill && (lastWord($input.val()).toLowerCase() == q.toLowerCase()) && lastKeyPressCode != KEY.BACKSPACE ) {
            // fill in the value (keep the case the user has typed)
            $input.val($input.val() + sValue.substring(lastWord(previousValue).length));
            // select the portion of the value not typed by the user (so the next character will erase)
            $.Autocompleter.Selection(input, previousValue.length, previousValue.length + sValue.length);
        }
    };

    function hideResults() {
        clearTimeout(timeout);
        timeout = setTimeout(hideResultsNow, 200);
    };

    function hideResultsNow() {
        var wasVisible = select.visible();
        select.hide();
        clearTimeout(timeout);
        stopLoading();
        if (options.mustMatch) {
            // call search and run callback
            $input.search(
                function (result){
                    // if no value found, clear the input box
                    if( !result ) {
                        if (options.multiple) {
                            var words = trimWords($input.val()).slice(0, -1);
                            $input.val( words.join(options.multipleSeparator) + (words.length ? options.multipleSeparator : "") );
                        }
                        else
                            $input.val( "" );
                    }
                }
            );
        }
        if (wasVisible)
            // position cursor at end of input field
            $.Autocompleter.Selection(input, input.value.length, input.value.length);
    };

    function receiveData(q, data) {
        if ( data && data.length && hasFocus ) {
            stopLoading();
            select.display(data, q);
            autoFill(q, data[0].value);
            select.show();
        } else {
            hideResultsNow();
        }
    };

    function request(term, success, failure) {
        if (!options.matchCase)
            term = term.toLowerCase();
        var data = cache.load(term);
        // recieve the cached data
        if (data && data.length) {
            success(term, data);
        // if an AJAX url has been supplied, try loading the data now
        } else if( (typeof options.url == "string") && (options.url.length > 0) ){
            
            var extraParams = {
                timestamp: +new Date()
            };
            $.each(options.extraParams, function(key, param) {
                extraParams[key] = typeof param == "function" ? param() : param;
            });
            
            $.ajax({
                // try to leverage ajaxQueue plugin to abort previous requests
                mode: "abort",
                // limit abortion to this input
                port: "autocomplete" + input.name,
                dataType: options.dataType,
                url: options.url,
                data: $.extend({
                    q: lastWord(term),
                    limit: options.max
                }, extraParams),
                success: function(data) {
                    var parsed = options.parse && options.parse(data) || parse(data);
                    cache.add(term, parsed);
                    success(term, parsed);
                }
            });
        } else {
            // if we have a failure, we need to empty the list -- this prevents the the [TAB] key from selecting the last successful match
            select.emptyList();
            failure(term);
        }
    };
    
    function parse(data) {
        var parsed = [];
        var rows = data.split("\n");
        for (var i=0; i < rows.length; i++) {
            var row = $.trim(rows[i]);
            if (row) {
                row = row.split("|");
                parsed[parsed.length] = {
                    data: row,
                    value: row[0],
                    result: options.formatResult && options.formatResult(row, row[0]) || row[0]
                };
            }
        }
        return parsed;
    };

    function stopLoading() {
        $input.removeClass(options.loadingClass);
    };

};

$.Autocompleter.defaults = {
    inputClass: "ac_input",
    resultsClass: "ac_results",
    loadingClass: "ac_loading",
    minChars: 1,
    delay: 400,
    matchCase: false,
    matchSubset: true,
    matchContains: false,
    cacheLength: 10,
    max: 100,
    mustMatch: false,
    extraParams: {},
    selectFirst: true,
    formatItem: function(row) { return row[0]; },
    formatMatch: null,
    autoFill: false,
    width: 0,
    multiple: false,
    multipleSeparator: ", ",
    highlight: function(value, term) {
        return value.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + term.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
    },
    scroll: true,
    scrollHeight: 180
};

$.Autocompleter.Cache = function(options) {

    var data = {};
    var length = 0;
    
    function matchSubset(s, sub) {
        if (!options.matchCase) 
            s = s.toLowerCase();
        var i = s.indexOf(sub);
        if (options.matchContains == "word"){
            i = s.toLowerCase().search("\\b" + sub.toLowerCase());
        }
        if (i == -1) return false;
        return i == 0 || options.matchContains;
    };
    
    function add(q, value) {
        if (length > options.cacheLength){
            flush();
        }
        if (!data[q]){ 
            length++;
        }
        data[q] = value;
    }
    
    function populate(){
        if( !options.data ) return false;
        // track the matches
        var stMatchSets = {},
            nullData = 0;

        // no url was specified, we need to adjust the cache length to make sure it fits the local data store
        if( !options.url ) options.cacheLength = 1;
        
        // track all options for minChars = 0
        stMatchSets[""] = [];
        
        // loop through the array and create a lookup structure
        for ( var i = 0, ol = options.data.length; i < ol; i++ ) {
            var rawValue = options.data[i];
            // if rawValue is a string, make an array otherwise just reference the array
            rawValue = (typeof rawValue == "string") ? [rawValue] : rawValue;
            
            var value = options.formatMatch(rawValue, i+1, options.data.length);
            if ( value === false )
                continue;
                
            var firstChar = value.charAt(0).toLowerCase();
            // if no lookup array for this character exists, look it up now
            if( !stMatchSets[firstChar] ) 
                stMatchSets[firstChar] = [];

            // if the match is a string
            var row = {
                value: value,
                data: rawValue,
                result: options.formatResult && options.formatResult(rawValue) || value
            };
            
            // push the current match into the set list
            stMatchSets[firstChar].push(row);

            // keep track of minChars zero items
            if ( nullData++ < options.max ) {
                stMatchSets[""].push(row);
            }
        };

        // add the data items to the cache
        $.each(stMatchSets, function(i, value) {
            // increase the cache size
            options.cacheLength++;
            // add to the cache
            add(i, value);
        });
    }
    
    // populate any existing data
    setTimeout(populate, 25);
    
    function flush(){
        data = {};
        length = 0;
    }
    
    return {
        flush: flush,
        add: add,
        populate: populate,
        load: function(q) {
            if (!options.cacheLength || !length)
                return null;
            /* 
             * if dealing w/local data and matchContains than we must make sure
             * to loop through all the data collections looking for matches
             */
            if( !options.url && options.matchContains ){
                // track all matches
                var csub = [];
                // loop through all the data grids for matches
                for( var k in data ){
                    // don't search through the stMatchSets[""] (minChars: 0) cache
                    // this prevents duplicates
                    if( k.length > 0 ){
                        var c = data[k];
                        $.each(c, function(i, x) {
                            // if we've got a match, add it to the array
                            if (matchSubset(x.value, q)) {
                                csub.push(x);
                            }
                        });
                    }
                }               
                return csub;
            } else 
            // if the exact item exists, use it
            if (data[q]){
                return data[q];
            } else
            if (options.matchSubset) {
                for (var i = q.length - 1; i >= options.minChars; i--) {
                    var c = data[q.substr(0, i)];
                    if (c) {
                        var csub = [];
                        $.each(c, function(i, x) {
                            if (matchSubset(x.value, q)) {
                                csub[csub.length] = x;
                            }
                        });
                        return csub;
                    }
                }
            }
            return null;
        }
    };
};

$.Autocompleter.Select = function (options, input, select, config) {
    var CLASSES = {
        ACTIVE: "ac_over"
    };
    
    var listItems,
        active = -1,
        data,
        term = "",
        needsInit = true,
        element,
        list;
    
    // Create results
    function init() {
        if (!needsInit)
            return;
        element = $("<div/>")
        .hide()
        .addClass(options.resultsClass)
        .css("position", "absolute")
        .appendTo(document.body);
    
        list = $("<ul/>").appendTo(element).mouseover( function(event) {
            if(target(event).nodeName && target(event).nodeName.toUpperCase() == 'LI') {
                active = $("li", list).removeClass(CLASSES.ACTIVE).index(target(event));
                $(target(event)).addClass(CLASSES.ACTIVE);            
            }
        }).click(function(event) {
            $(target(event)).addClass(CLASSES.ACTIVE);
            select();
            // TODO provide option to avoid setting focus again after selection? useful for cleanup-on-focus
            input.focus();
            return false;
        }).mousedown(function() {
            config.mouseDownOnSelect = true;
        }).mouseup(function() {
            config.mouseDownOnSelect = false;
        });
        
        if( options.width > 0 )
            element.css("width", options.width);
            
        needsInit = false;
    } 
    
    function target(event) {
        var element = event.target;
        while(element && element.tagName != "LI")
            element = element.parentNode;
        // more fun with IE, sometimes event.target is empty, just ignore it then
        if(!element)
            return [];
        return element;
    }

    function moveSelect(step) {
        listItems.slice(active, active + 1).removeClass(CLASSES.ACTIVE);
        movePosition(step);
        var activeItem = listItems.slice(active, active + 1).addClass(CLASSES.ACTIVE);
        if(options.scroll) {
            var offset = 0;
            listItems.slice(0, active).each(function() {
                offset += this.offsetHeight;
            });
            list.scrollTop = list.scrollTop || function(){};//yc@2010-10-1
            if((offset + activeItem[0].offsetHeight - list.scrollTop()) > list[0].clientHeight) {
                list.scrollTop(offset + activeItem[0].offsetHeight - list.innerHeight());
            } else if(offset < list.scrollTop()) {
                list.scrollTop(offset);
            }
        }
    };
    
    function movePosition(step) {
        active += step;
        if (active < 0) {
            active = listItems.size() - 1;
        } else if (active >= listItems.size()) {
            active = 0;
        }
    }
    
    function limitNumberOfItems(available) {
        return options.max && options.max < available
            ? options.max
            : available;
    }
    
    function fillList() {
        list.empty();
        var max = limitNumberOfItems(data.length);
        for (var i=0; i < max; i++) {
            if (!data[i])
                continue;
            var formatted = options.formatItem(data[i].data, i+1, max, data[i].value, term);
            if ( formatted === false )
                continue;
            var li = $("<li/>").html( options.highlight(formatted, term) ).addClass(i%2 == 0 ? "ac_even" : "ac_odd").appendTo(list)[0];
            $.data(li, "ac_data", data[i]);
        }
        listItems = list.find("li");
        if ( options.selectFirst ) {
            listItems.slice(0, 1).addClass(CLASSES.ACTIVE);
            active = 0;
        }
        // apply bgiframe if available
        if ( $.fn.bgiframe )
            list.bgiframe();
    }
    
    return {
        display: function(d, q) {
            init();
            data = d;
            term = q;
            fillList();
        },
        next: function() {
            moveSelect(1);
        },
        prev: function() {
            moveSelect(-1);
        },
        pageUp: function() {
            if (active != 0 && active - 8 < 0) {
                moveSelect( -active );
            } else {
                moveSelect(-8);
            }
        },
        pageDown: function() {
            if (active != listItems.size() - 1 && active + 8 > listItems.size()) {
                moveSelect( listItems.size() - 1 - active );
            } else {
                moveSelect(8);
            }
        },
        hide: function() {
            element && element.hide();
            listItems && listItems.removeClass(CLASSES.ACTIVE);
            active = -1;
        },
        visible : function() {
            return element && element.is(":visible");
        },
        current: function() {
            return this.visible() && (listItems.filter("." + CLASSES.ACTIVE)[0] || options.selectFirst && listItems[0]);
        },
        show: function() {
            var offset = $(input).offset();
            element.css({
                width: typeof options.width == "string" || options.width > 0 ? options.width : $(input).width(),
                top: offset.top + input.offsetHeight,
                left: offset.left
            }).show();
            if(options.scroll) {
                //list.scrollTop(0);
                list.css({
                    maxHeight: options.scrollHeight,
                    overflow: 'auto'
                });
                
                if($.browser.msie && typeof document.body.style.maxHeight === "undefined") {
                    var listHeight = 0;
                    listItems.each(function() {
                        listHeight += this.offsetHeight;
                    });
                    var scrollbarsVisible = listHeight > options.scrollHeight;
                    list.css('height', scrollbarsVisible ? options.scrollHeight : listHeight );
                    if (!scrollbarsVisible) {
                        // IE doesn't recalculate width when scrollbar disappears
                        listItems.width( list.width() - parseInt(listItems.css("padding-left")) - parseInt(listItems.css("padding-right")) );
                    }
                }
                
            }
        },
        selected: function() {
            var selected = listItems && listItems.filter("." + CLASSES.ACTIVE).removeClass(CLASSES.ACTIVE);
            return selected && selected.length && $.data(selected[0], "ac_data");
        },
        emptyList: function (){
            list && list.empty();
        },
        unbind: function() {
            element && element.remove();
        }
    };
};

$.Autocompleter.Selection = function(field, start, end) {
    if( field.createTextRange ){
        var selRange = field.createTextRange();
        selRange.collapse(true);
        selRange.moveStart("character", start);
        selRange.moveEnd("character", end);
        selRange.select();
    } else if( field.setSelectionRange ){
        field.setSelectionRange(start, end);
    } else {
        if( field.selectionStart ){
            field.selectionStart = start;
            field.selectionEnd = end;
        }
    }
    field.focus();
};

})(jQuery);

// my own functions 
var params = {tag:[], language:[], title:''};
var langs = [[1,'4cs'],[2,'6502acme'],[3,'6502kickass'],[4,'6502tasm'],[5,'68000devpac'],[6,'abap'],[7,'actionscript'],[8,'actionscript3'],[9,'ada'],[10,'algol68'],[11,'apache'],[12,'applescript'],[13,'apt_sources'],[14,'asm'],[15,'asp'],[16,'autoconf'],[17,'autohotkey'],[18,'autoit'],[19,'avisynth'],[20,'awk'],[21,'bash'],[22,'basic4gl'],[23,'bf'],[24,'bibtex'],[25,'blitzbasic'],[26,'bnf'],[27,'boo'],[28,'c'],[29,'c_mac'],[30,'caddcl'],[31,'cadlisp'],[32,'cfdg'],[33,'cfm'],[34,'chaiscript'],[35,'cil'],[36,'clojure'],[37,'cmake'],[38,'cobol'],[39,'cpp'],[40,'cpp-qt'],[41,'csharp'],[42,'css'],[43,'cuesheet'],[44,'d'],[45,'dcs'],[46,'delphi'],[47,'diff'],[48,'div'],[49,'dos'],[50,'dot'],[51,'e'],[52,'ecmascript'],[53,'eiffel'],[54,'email'],[55,'erlang'],[56,'f1'],[57,'fo'],[58,'fortran'],[59,'freebasic'],[60,'fsharp'],[61,'gambas'],[62,'gdb'],[63,'genero'],[64,'genie'],[65,'gettext'],[66,'glsl'],[67,'gml'],[68,'gnuplot'],[69,'go'],[70,'groovy'],[71,'gwbasic'],[72,'haskell'],[73,'hicest'],[74,'hq9plus'],[75,'html4strict'],[76,'icon'],[77,'idl'],[78,'ini'],[79,'inno'],[80,'intercal'],[81,'io'],[82,'j'],[83,'java'],[84,'java5'],[85,'javascript'],[86,'jquery'],[87,'kixtart'],[88,'klonec'],[89,'klonecpp'],[90,'latex'],[91,'lb'],[92,'lisp'],[93,'locobasic'],[94,'logtalk'],[95,'lolcode'],[96,'lotusformulas'],[97,'lotusscript'],[98,'lscript'],[99,'lsl2'],[100,'lua'],[101,'m68k'],[102,'magiksf'],[103,'make'],[104,'mapbasic'],[105,'matlab'],[106,'mirc'],[107,'mmix'],[108,'modula2'],[109,'modula3'],[110,'mpasm'],[111,'mxml'],[112,'mysql'],[113,'newlisp'],[114,'nsis'],[115,'oberon2'],[116,'objc'],[117,'objeck'],[118,'ocaml'],[119,'ocaml-brief'],[120,'oobas'],[121,'oracle11'],[122,'oracle8'],[123,'oxygene'],[124,'oz'],[125,'pascal'],[126,'pcre'],[127,'per'],[128,'perl'],[129,'perl6'],[130,'pf'],[131,'php'],[132,'php-brief'],[133,'pic16'],[134,'pike'],[135,'pixelbender'],[136,'plsql'],[137,'postgresql'],[138,'povray'],[139,'powerbuilder'],[140,'powershell'],[141,'progress'],[142,'prolog'],[143,'properties'],[144,'providex'],[145,'purebasic'],[146,'python'],[147,'q'],[148,'qbasic'],[149,'rails'],[150,'rebol'],[151,'reg'],[152,'robots'],[153,'rpmspec'],[154,'rsplus'],[155,'ruby'],[156,'sas'],[157,'scala'],[158,'scheme'],[159,'scilab'],[160,'sdlbasic'],[161,'smalltalk'],[162,'smarty'],[163,'sql'],[164,'systemverilog'],[165,'tcl'],[166,'teraterm'],[167,'text'],[168,'thinbasic'],[169,'tsql'],[170,'typoscript'],[171,'unicon'],[172,'vala'],[173,'vb'],[174,'vbnet'],[175,'verilog'],[176,'vhdl'],[177,'vim'],[178,'visualfoxpro'],[179,'visualprolog'],[180,'whitespace'],[181,'whois'],[182,'winbatch'],[183,'xbasic'],[184,'xml'],[185,'xorg_conf'],[186,'xpp'],[187,'z80'],[188,'zxbasic']];
var __confirmDelete = ($.cookie('lang') == 'zh') ? "\u786e\u5b9e\u8981\u5220\u9664\u5417\uff1f" : 'Are you sure to delete?',
    __check = ($.cookie('lang') == 'zh') ? "\u6807\u8bb0" : 'Check',
    __uncheck = ($.cookie('lang') == 'zh') ? "\u53d6\u6d88\u6807\u8bb0" : 'Uncheck',
    __pin= ($.cookie('lang') == 'zh') ? "\u7f6e\u9876" : 'Pin',
    __unpin= ($.cookie('lang') == 'zh') ? "\u53d6\u6d88\u7f6e\u9876" : 'Unpin',
    __edit = ($.cookie('lang') == 'zh') ? "\u7f16\u8f91" : 'Edit',
    __delete = ($.cookie('lang') == 'zh') ? "\u5220\u9664" : 'Delete',
    __justnow = ($.cookie('lang') == 'zh') ? "\u521a\u521a" : 'Seconds ago';
var checkTpl = '<a onclick="return dealCheck(event)" class="check" href="#check">'+__check+'</a>',
    uncheckTpl = '<a onclick="return dealUnCheck(event)" class="uncheck" href="#uncheck">'+__uncheck+'</a>',
    unpinTpl = '<a onclick="return dealUnPin(event)" class="unpin" href="#unpin">'+__unpin+'</a>',
    pinTpl = '<a onclick="return dealPin(event)" class="pin" href="#pin">'+__pin+'</a>',
    deleteTpl = '<a class="del" onclick="return dealDelete(event)" href="#delete">'+__delete+'</a>',
    editTpl= '<a class="edit" onclick="return dealEdit(event)" href="#delete">'+__edit+'</a>',
    timeTpl = '<span class="time">{TIME}</span>',
    tagsTpl = '<span class="tags">{TAGS}</span>';
var memoTpl = '<div class="note" noteid="{ID}"><div onclick="return dealClick(event)" class="title">{TITLE}</div><div class="meta"><div class="right hide">'+checkTpl+pinTpl+editTpl+deleteTpl+'</div>'+timeTpl+tagsTpl+'</div></div>';
$(function(){
    if (typeof noAutoFocus == 'undefined')
        $('input[type=text]:eq(0)').focus(); // focus first input
    $('a.confirm').click(function(){ // rewrite the onclick function
        return confirm($(this).attr('rel'));
    });
    $('a.ajax').click(function(){
        var ele = $(this);
        $.get(ele.attr('href'), function(data){
            if (data == 'ok' && ele.hasClass('remove'))
                ele.parent('tr').fadeOut();
        });
        return false;
    });
    $('a.disablelink').click(function(){ return false; });
    $('.filter sup').click(function(){
        var k = $(this).attr('rel');
        var i = $.inArray($(this).prev().text(), params[k]);
        if (i !== -1){
            //delete params[k][i];
            params[k].splice(i, 1);
            reloadPage();
        }
    });
    $('.newfilter').click(function(){
        var v = $.trim($(this).prev().val()),
            k = $(this).attr('rel');
        if (v == '')
            return false;
        if (typeof params[k] == 'string'){
            params[k] = [v];
            reloadPage();
        } else if ($.inArray(v, params[k]) === -1){
            params[k].push(v);
            reloadPage();
        }
        return false;
    });
    $('a[href="#top"]').click(function(){
        $('html,body').animate({scrollTop: 0}); // competable with FF,IE,Chrome
        return false;
    });
});
function reloadPage()
{
    var q = [];
    params['page'] = -1;
    $.each(params, function(k, v){
        if (typeof v == 'string' && $.trim(v) != '')
            q.push(k + '=' + encodeURIComponent(v));
        else if (v.length)
            q.push(k + '=' + encodeURIComponent(v.join(',')));
    });
    var q = q.length ? '?' + q.join('&') : '';
    if (typeof baseUrl != 'undefined')
        location.href = baseUrl + q;
    else
        location.search = q;
}
function resetLang(lang)
{
    $.cookie('lang', lang, {expires: 365, path: '/'});
    location.reload();
    return false;
}
function dealClick(event)
{
    var ele = _getEventElement(event);
    while (!ele.hasClass('title'))
        ele = ele.parent();
    if (ele.data('pending'))
        return false;
    ele.data('pending', true);
    var nextDiv = ele.next();
    if (!nextDiv.hasClass('content')){
        ele.addClass('loading-bg');
        $.get('/ajax/note/content/' + ele.parent().attr('noteid'), function(data){
            nextDiv = $('<div class="content" />').hide().html(data).insertAfter(ele);
            nextDiv.slideDown('fast');
            ele.removeClass('loading-bg').data('pending', false);
        });
    } else {
        ele.data('pending', false);
        $(nextDiv).slideToggle('fast');
    }
    return false;
}
function dealActions(action, ele, callback)
{
    var noteid = ele.parent().parent().parent().attr('noteid');
    if (ele.data('pending'))
        return false;
    ele.data('pending', true);
    $.getJSON('/ajax/note/' + action + '/' + noteid, function(data){
        ele.data('pending', false);
        callback(data);
    });
}
function _getEventElement(event)
{
    event = event || window.event;
    event.cancelBubble = true;
    if (event.stopPropagation)
        event.stopPropagation();
    ele = event.target || event.srcElement;
    if (ele.nodeType == 3)
        ele = ele.parentNode;
    if (ele.nodeName == 'IMG')
        ele = ele.parentNode;
    return $(ele);
}
function dealCheck(event)
{
    var ele = _getEventElement(event);
    var d = ele.parent().parent().parent().children('.title');
    d.addClass('loading-bg');
    dealActions('check', ele, function(data){
        if (data.result){
            d.html('<del class="gray">' + d.text().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;") + '</del>');
            ele.replaceWith(uncheckTpl);
        }
        d.removeClass('loading-bg');
    });
    return false;
}
function dealUnCheck(event)
{
    var ele = _getEventElement(event);
    var d = ele.parent().parent().parent().children('.title');
    d.addClass('loading-bg');
    dealActions('uncheck', ele, function(data){
        if (data.result){
            d.html(d.text().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;"));
            ele.replaceWith(checkTpl);
        }
        d.removeClass('loading-bg');
    });
    return false;
}
function dealPin(event)
{
    var ele = _getEventElement(event);
    var d = ele.parent().parent().parent().children('.title');
    d.addClass('loading-bg');
    dealActions('pin', ele, function(data){
        d.removeClass('loading-bg');
        if (data.result){
            ele.replaceWith(unpinTpl);
            $(d.parent()).find('.meta .right').addClass('hide');
            d.parent().addClass('top').slideDown('fast').prependTo($('#notes'));
        }
    });
    return false;
}
function dealUnPin(event)
{
    var ele = _getEventElement(event);
    var d = ele.parent().parent().parent().children('.title');
    d.addClass('loading-bg');
    dealActions('unpin', ele, function(data){
        d.removeClass('loading-bg');
        if (data.result){
            d.parent().removeClass('top');
            ele.replaceWith(pinTpl);
        }
    });
    return false;
}
function _insertNote(data)
{
    $('#memoSidebar').hide();
    $('#normalSidbar').show();
    $('#newmemo').data('new', false).slideUp('fast');
    $('#memoContent').css('height', '300px').val('');
    $('#memoTags').val('');
    $('#notes').show();
    var div = memoTpl.replace('{ID}', data.id).replace('{TITLE}', data.title).replace('{TIME}', data.time).replace('{TAGS}',
        $.map(data.tags, function(i){ return '<a href="/note/tag/'+encodeURIComponent(i)+'">'+i+'</a>'; }).join(''));
    if ($('#newmemo').data('noteid')){
        var i = $('#notes .note[noteid='+$('#newmemo').data('noteid')+']'), j = $(div).hover(noteMouseOver, noteMouseOut);
        if (i.hasClass('top'))
            j.addClass('top')
        i.replaceWith(j);
        $('#newmemo').data('noteid', false);
    } else
        $(div).hover(noteMouseOver, noteMouseOut).prependTo('#notes').slideDown('slow');
    $('$newmemo').data('noteid', false);
}
function dealDelete(event)
{
    var ele = _getEventElement(event);
    if (!confirm(__confirmDelete))
        return false;
    var d = ele.parent().parent().parent().children('.title');
    d.addClass('loading-bg');
    dealActions('delete', ele, function(data){
        if (data.result){
            d.removeClass('loading-bg');
            d.parent().slideUp('slow', function(){$(this).remove();});
        }
    });
    return false;
}
function dealEdit(event)
{
    var ele = _getEventElement(event);
    var d = ele.parent().parent().parent().children('.title');
    d.addClass('loading-bg');
    dealActions('edit', ele, function(data){
        if (data.id){
            d.removeClass('loading-bg');
            $('#newmemo').data('noteid', data.id);
            $('#memoContent').val(data.content);
            $('#memoTags').val(data.tags.join(','));
            newmemo();
        }
    });
    return false;
}
function newmemo()
{
    if ($('#newmemo').data('new'))
        return;
    $('#notes').hide();
    $('#newmemo').data('new', true).slideDown('normal');
    $('#normalSidbar').hide();
    $('#memoSidebar').show();
    setTimeout(function(){$('#memoContent').focus()}, 500);
}
function insertAtCursor(myField, myValue) {
    //IE support
    if (document.selection) {
        myField.focus();
        sel = document.selection.createRange();
        sel.text = myValue;
    } else if (myField.selectionStart || myField.selectionStart == '0') {
        var startPos = myField.selectionStart, endPos = myField.selectionEnd;
        myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
        myField.selectionStart = myField.selectionEnd = startPos + myValue.length;
    } else {
        myField.value += myValue;
    }
    $(myField).scrollTop(999999);
}
function noteMouseOver()
{
    $(this).find('.meta .right').removeClass('hide');
}
function noteMouseOut()
{
    $(this).find('.meta .right').addClass('hide');
}
