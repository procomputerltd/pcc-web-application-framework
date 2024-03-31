    function pccMsgbox(message, title, close, element, options) {
        var consoleObj = (undefined !== window && undefined !== window.console) ? window.console : false;
        
        var container = '';
        if(element) {
            if(element instanceof jQuery) {
                container = element;
            }
            else if('string' === typeof element) {
                element = element.trim();
                if(element.length) {
                    if(! element.match(/^[.,\/#!$%\^&\*;:{}=\-_`~()]/)) {
                        element = '#' + element;
                    }
                    container = $(element);
                }
            }
        }
        if(! container.length) {
            container = $('#pcc-message-box');
        }
        if(! container.length) {
            if(consoleObj) {
                consoleObj.error('cannot resolve a message box containing element.');
            }
            return false;
        }
        
        if(undefined === container.modal) {
            if(consoleObj) {
                consoleObj.error('jQuery.modal is not a function.');
            }
            return false;
        }
        
        if('string' === typeof title) {
            var elmTitle = container.find('.modal-title');
            if(elmTitle.length) {
                if(title.length) {
                    elmTitle.html(title);
                }
                else {
                    elmTitle.hide();
                }
            }
        }
        if('string' === typeof message) {
            var elmMessage = container.find('.modal-body');
            if(elmMessage.length) {
                elmMessage.html(message);
            }
        }
        if('string' === typeof close) {
            var elmClose = container.find('.modal-close');
            if(elmClose.length) {
                elmClose.html(close);
            }
        }
        container.modal('show');
        return true;
    }