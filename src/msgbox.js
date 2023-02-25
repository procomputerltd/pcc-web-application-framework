/* 
 * Copyright (C) 2023 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */
/**
 * PCC Message box popup javascript companion for msgbox.phtml
 * @param {String} message  Message box content message.
 * @param {String} title    (optional) Message box title.
 * @param {String} close    (optional) Message box close button text.
 * @param {Object} commands (optional) Extra message box command buttons.
 * @returns {undefined}
 */
function pccMsgbox(message, title, close, commands) {
    var elm = $('#pccMsgBox');
    if(! elm.length) {
        return;
    }
    var elmTitle = $('#pccMsgBoxTitle');
    var elmMessage = $('#pccMsgBoxBody');
    var elmClose = $('#pccMsgBoxCloseText');
    if(title && elmTitle.length) {
        elmTitle.html(title);
    }
    else {
        elmTitle.hide();
    }
    if(message && elmMessage.length) {
        elmMessage.html(message);
    }
    if(close && elmClose.length) {
        elmClose.html(close);
    }
    elm.modal('show');
}