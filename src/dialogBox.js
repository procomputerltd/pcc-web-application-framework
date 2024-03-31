/**
 * Pops up a dialog box using the supplied source (element) and provider to display as a 
 * lightbox-style popup box. Default provider is 'magnificPopup'.
 * This does not modify the contents of the wrapper. That is done by the caller.
 *
 * @param {Object} options  Constructor options.
 * 
 * @see https://dimsemenov.com/plugins/magnific-popup
 * @see https://www.jquerymodal.com/
 *
 * @returns {dialogBox}
 */
dialogBox = function(options) { 
    // Hereafter, refer to 'base' instead of 'this' to avoid JS scope issues.
    var base = this;
    base.functionName = dialogBox.name;
    base.options = {}; // Constructor options.
    base.container = null;
    
    /**
     * Displays modal popup message box.
     * @param {Object|string} source   Source element or jQuery element selector.
     * @returns {Boolean}
     */
    base.open = function(source) {
        var provider = base.getProvider(base.provider);
        if(! provider) {
            return false;
        }
        var container = base.getSourceContainer(source);
        if(! container) {
            return false;
        }
        base.container = container;
        if('function' === typeof base.callback) {
            base.callback(base);
        }
        if('object' === typeof provider) {
            // Assume magnificPopup hereafter.
            if(undefined === provider.open) {
                return false;
            }
            var providerOptions = base.providerOptions;
            if(Array.isArray(providerOptions) || 'object' !== typeof providerOptions) {
                providerOptions = {};
            }
            if(undefined === providerOptions.items || undefined === providerOptions.items.src || base.isBlank(providerOptions.items.src)) {
                providerOptions.items = {src: container};
            }
            if(undefined === providerOptions.type || base.isBlank(providerOptions.type)) {
                providerOptions.type = 'inline';
            }
            if(undefined === providerOptions.closeOnBgClick) {
                providerOptions.closeOnBgClick = false;
            }
            provider.open(providerOptions);
            return true;
        }
        if('dialog' === provider) {
            return container[0].showModal();
        }
        return container.modal(base.providerOptions);
    };

    /**
     * Determines the popup dialog provider name or object.
     * @param {Object|string} provider   Source element or jQuery element selector.
     * @returns {String|Object|Boolean} Returns a provider name, object or false if not found.
     */
    base.getProvider = function(provider) {
        // Use the provider object presumably a 'magnificPopup' instance.
        if('object' === typeof provider) {
            return provider;
        }
        var providers = [];
        let elm = $('<dialog> </dialog>');
        if(elm.length && undefined !== elm[0].showModal && 'function' === typeof elm[0].showModal) {
            providers.push('dialog');
        }
        if(undefined !== $.magnificPopup) {
            providers.push('magnificPopup');
        }
        if(undefined !== $('<div> </div>').modal) {
            providers.push('modal');
        }
        if(! providers.length) {
            return false;
        }
        var providerName = providers[0]; // Default like 'dialog'
        if('string' === typeof provider) {
            var str = provider.trim().toLowerCase();
            if(str.length && 'auto' !== str) {
                for(var pName of providers) {
                    if(str.includes(pName.toLowerCase())) {
                        providerName = pName;
                        break;
                    }
                }
            }
        }
        return ('magnificPopup' === providerName) ? $[providerName] : providerName;
    };
    
    /**
     * Resolves a jQuery element.
     * @param {Object|string} source   Source element or jQuery element selector.
     * @returns {Object|Boolean} Returns a jQuery element or false if not found.
     */
    base.getSourceContainer = function(source) {
        if(base.isBlank(source)) {
            source = base.source;
        }
        if(base.isBlank(source)) {
            return false;
        }
        var container = '';
        if('string' === typeof source) {
            container = $(source);
            if(! container.length) {
                container = $('#' + source);
            }
        }
        else if('object' === typeof source && undefined !== container.length) {
            container = $(source);
        }
        return container.length ? container : false;
    };
    
    /**
     * Sets element html within the dialg container.
     * @param {String|Object|Array} selectors
     * @param {String}              html
     * @returns {dialogBox} Returns this.
     */
    base.setElementHtml = function(selectors, html) {
        var container = base.getSourceContainer();
        if(container) {
            var elms = [];
            if('string' === typeof selectors || 'object' === typeof selectors) {
                elms.push(selectors);
            }
            else if(Array.isArray(selectors)) {
                elms = selectors;
            }
            for(var elm of elms) {
                var elm = container.find(elm);
                if(elm && elm.length) {
                    elm.html(html);
                }
            }
        }
        return base;
    };
    
    /**
     * Determines that a variable is undefined, blank string, empty array or object.
     * @param   {Mixed}   variable
     * @returns {Boolean} Return TRUE if blank or empty else FALSE.
     */
    base.isBlank = function(variable) {
        if(variable === undefined || variable === null) {
            return true;
        }
        var varType = typeof variable;
        if('string' === varType) {
            return variable.trim().length ? false : true;
        }
        if('number' === varType || 'boolean' === varType) {
            return false;
        }
        if(Array.isArray(variable)) {
            return variable.length ? false : true;
        }
        return $.isEmptyObject(variable);
    };

    /**
     * Validate the options.
     * @param   {Object}   options  Function options.
     * @returns {Boolean}  Returns TRUE if valid else FALSE.
     * @throws  {Object}   Throws an exception when invalid options are specified.
     */
    base.validOptions = function(options) {
        // jQuery.extend argument proto:
        //   extend(target, object1 [, objectN])
        //   extend([deep], target, object1 [, objectN])
        options = ('object' === typeof options) ? $.extend({}, dialogBox.defaultOptions, options) : dialogBox.defaultOptions;
        base.options = options;
        for(var i in options) {
            base[i] = options[i];
        }
        return true;
    };

    /**
     * Plugin initialization.
     * @param {Object} options Option startup property name:value pairs.
     * @returns {Boolean} Returns TRUE if success, else FALSE.
     */
    base.init = function(options) {
        /**
         * Inspect to ensure validity then save options.
         */
        return base.validOptions(options) ? true : false;
    };

    /**
     * Hides a modal popup message box.
     * @param {Object|string} source   Source element or jQuery element selector.
     * @returns {Boolean}
     */
    base.close = function(source) {
        var container = base.getSourceContainer(source);
        if(! container) {
            return false;
        }
        var provider = base.getProvider(base.provider);
        if(! provider) {
            return false;
        }
        base.container = container;
        if('object' === typeof provider) {
            // Assume magnificPopup hereafter.
            if(undefined === provider.close) {
                return false;
            }
            provider.close();
        }
        else {
            container.hide();
        }
        return true;
    };

    base.init(options);

    return this;
};

/**
 * Available plugin options:defaults.
 */
dialogBox.defaultOptions = {
    callback        : false,  // Function called before dialog is rendered/displayed.
    debug           : 1,      // Determines action when debugging or when an error occurs.
    source          : '',     // An element ID, jQuery selector, jQuery element or javascript element.
    provider        : 'auto', // Lightbox or popup provider.
    providerOptions : ''      // Options passed to the provider.
};