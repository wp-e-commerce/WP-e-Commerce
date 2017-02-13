/*global WPSC_Pro_Pay_Checkout*/
var iFrameId = WPSC_Pro_Pay_Checkout.iframe_id;
var baseURI  = WPSC_Pro_Pay_Checkout.base_uri;
var TimeoutInterval = 30000; //in ms
//Get Timestamp for log
function GetTimestamp() {
    var date = new Date();
    var hour = date.getHours();
    var minutes = date.getMinutes();
    var seconds = date.getSeconds();
    var milliseconds = date.getMilliseconds();
    if ((minutes + '').length == 1) {
        minutes = '0' + minutes;
    }
    if ((seconds + '').length == 1) {
        seconds = '0' + seconds;
    }
    if ((milliseconds + '').length == 1) {
        milliseconds = '00' + milliseconds;
    }
    if ((milliseconds + '').length == 2) {
        milliseconds = '0' + milliseconds;
    }
    return hour + ':' + minutes + ':' + seconds + ':' + milliseconds;
}

//Echo Browser Events to Browser Log Window
function echoBrowserMessage(message) {
    var divMessage = document.getElementById('BrowserLog');
    var msg = divMessage.innerHTML;
    msg = '[' + GetTimestamp() + '] ' + message + '<br />' + msg;
    divMessage.innerHTML = msg;
}

//Echo SignalR Message and Data Events to Log Window
function echoSignalRMessage(message) {
    var divMessage = document.getElementById('MessageLog');
    var msg = divMessage.innerHTML;
    msg = '[' + GetTimestamp() + '] ' + message + '<br />' + msg;
    divMessage.innerHTML = msg;
}

//Echo SignalR Console Transport Connection Events to Log Window
function echoSignalRConsoleMessage(message) {
    var divMessage = document.getElementById('ConsoleLog');
    var msg = divMessage.innerHTML;
    msg = '<b>[' + GetTimestamp() + ']</b> ' + message + '<br />' + msg;
    divMessage.innerHTML = msg;
}

function fixMissingBrowserFunctionality() {

    // If 'String.trim()' is not defined (like on IE8), define it here
    if (typeof String.prototype.trim !== 'function') {
        String.prototype.trim = function () {
            return this.replace(/^\s+|\s+$/g, '');
        };
    }

    //Add additional Checks for Browsers you intend to support
}

// Invoked after a JavaScript error occurs on the page.
function window_OnError(errorMsg, url, lineNumber, column, errorObj) {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: red">Error Thrown: </b> window_OnError() <b>Raised</b>');
        echoBrowserMessage('<b style="color: red"> &#149; Type: </b>' + errorObj);
        echoBrowserMessage('<b style="color: red"> &#149; Message: </b>' + errorMsg);
        echoBrowserMessage('<b style="color: red"> &#149; File: </b>' + url);
        echoBrowserMessage('<b style="color: red"> &#149; Line Number: </b>' + lineNumber);
        echoBrowserMessage('<b style="color: red"> &#149; Column: </b>' + column);
        echoBrowserMessage('<b style="color: red">Error Details: </b>');
        return true;
    }
}

// Invoked before anything is sent over the connection.
function signalR_OnStarting(e, data) {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: blue">Establishing SignalR Connection Capability</span> signalR_OnStarting() <b>Raised</b>');
    }
}

// Invoked when the connection state changes.
function signalR_OnStateChanged(e, data) {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: blue">SignalR Connection State Change</span> signalR_OnStateChanged() <b>Raised()</b>');
        //var oldState = getStateName(e.oldState);
        //var newState = getStateName(e.newState);
        echoSignalRMessage('<b style="color: Blue" >Changing state </b> from <b>' + getStateName(e.oldState) + '</b> to <b>' + getStateName(e.newState) + '</b>');
    }
	jQuery( document.body ).trigger( 'pro-pay-connecting' );

}

// Invoked when the 'start' method is called and succeeds in connecting to the server.
function signalR_OnConnected(e, data) {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: Blue ">SignalR Connection Established</span> signalR_OnConnected() <b>Raised</b>');
        var msg = '<b style="color: Blue ">Connection Attributes</b>'
            // General Connection Stats
            + '<br> <b>&nbsp; &#149; Connection Protocol:</b> ' + e.protocol
            + '<br> <b>&nbsp; &#149; Connected Host:</b> ' + e.host
            + '<br> <b>&nbsp; &#149; Connected Host Resource URI:</b> ' + e.appRelativeUrl
            // SignalR Connection Stats
            + '<br> <b>&nbsp; &#149; SignalR Connection State:</b> ' + getStateName(e.state)
            + '<br> <b>&nbsp; &#149; SignalR Connection ID:</b> ' + e.id
            + '<br> <b>&nbsp; &#149; SignalR Connection Token:</b> ' + e.token
            + '<br> <b>&nbsp; &#149; SignalR Connection Timout:</b> ' + e.disconnectTimeout; + 'ms'
            + '<br> <b>&nbsp; &#149; SignalR Connection Timeout Threshold:</b> ' + ((parseInt(e.disconnectTimeout) * 2) / 3) + 'ms'
            // SignalR Transport Connection Stats
            + '<br> <b>&nbsp; &#149; Transport Connection Name:</b> ' + e.transport.name
            + '<br> <b>&nbsp; &#149; Transport Connection Reconnect Delay:</b> ' + e.reconnectDelay + 'ms'
            + '<br> <b>&nbsp; &#149; Transport Connection Reconnect Threshold:</b> ' + ((parseInt(e.reconnectDelay) * 2) / 3) + 'ms'
            + '<br> <b>&nbsp; &#149; Transport Connection Reconnect Delay:</b> ' + e.transport.reconnectDelay + 'ms'
            + '<br> <b>&nbsp; &#149; Transport Connection Reconnect Threshold:</b> ' + ((parseInt(e.transport.reconnectDelay) * 2) / 3) + 'ms'
            + '<br> <b>&nbsp; &#149; Logging SignalR Events to Console:</b> ' + e.logging;

        echoSignalRMessage(msg);
    }

	jQuery( document.body ).trigger( 'pro-pay-connected' );

}

// Invoked when the Connection.Start() method is called but fails to connect to the SignalR server.
function signalR_OnConnectionFailed(e, data) {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: red">Error Thrown: </b>Failed to connect to the server - Unknown, signalR_OnConnectionFailed() <b>Raised</b>');
        if (valueIsValid(e) === true) {
            echoSignalRMessage('<b style="color: red">Error Thrown: </b>Failed to connect to the server: ' + e.message)
            echoSignalRMessage('<b style="color: red">Error Thrown: </b>Stack: ' + e.stack);
        }
        else {
            echoSignalRMessage('<b style="color: red">Error Thrown: </b>Failed to connect to the server - Unknown, signalR_OnConnectionFailed() <b>Raised</b>');
        }
    }
	jQuery( document.body ).trigger( 'pro-pay-connection-failed' );
}

// Invoked when the client detects a slow connection.
function signalR_OnConnectionSlow(e, data) {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: blue">Slow Connection Detected - </b>Keep Alive Timout % Threshold Exceeded, signalR_OnConnectionSlow() <b>Raised</b>');
    }
	jQuery( document.body ).trigger( 'pro-pay-connection-slow' );

}

// Invoked when the underlying transport begins reconnecting.
function signalR_OnReconnecting(e, data) {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: red">Connection Loss Threshold - </span>Connection Lost OR Keep Alive Timout Exceede, signalR_OnReconnecting() <b>Raised</b>');
    }
	jQuery( document.body ).trigger( 'pro-pay-reconnecting' );
}

// Invoked when the underlying transport reconnects.
function signalR_OnReconnect(e, data) {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: green">Connection Re-established</span>signalR_OnReconnect() <b>Raised</b>');
    }

	jQuery( document.body ).trigger( 'pro-pay-reconnected' );

}

// Invoked when the client disconnects.
function signalR_OnDisconnect(e, data) {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: red">SignalR Connection Disconnected</span>signalR_OnDisconnect() <b>Raised</b>');
    }
	jQuery( document.body ).trigger( 'pro-pay-disconnected' );

}

// This function is called when the server signalR issues a 'FormSubmitWasInvalid' message (indicating there was a problem validating the form data).
function signalR_OnFormWasInvalid() {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: red">Form Submitted failed Validation - </span>signalR_OnFormWasInvalid() <b>Raised</b>');
    }
	jQuery( document.body ).trigger( 'pro-pay-submission-error' );
}

// This function is called when the server signalR issues a 'FormSubmitErrored' message (indicating an error occurred when the user submitted the form).
function signalR_OnFormSubmitErrored() {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: red">Form Submission Error - </span>signalR_OnFormSubmitErrored() <b>Raised</b>');
    }
	jQuery( document.body ).trigger( 'pro-pay-submission-error' );

}

// This function is called when the server signalR issues a 'FormSubmitSucceeded' message (indicating the form was submitted successfully).
function signalR_OnFormSubmitSucceeded() {
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: Green">Form Submission Succeeded - </span>signalR_OnFormSubmitSucceeded() <b>Raised</b>');
    }
   jQuery( document.body ).trigger( 'pro-pay-submission-success' );
}

//This function is called when the timeout period elapses for communication from the Hosted Payment Page to your Checkout Page
function signalR_OnFormCommunicationTimeout(){
    //Custom Handler
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: Green">Form Submission Succeeded - </span>signalR_OnFormCommunicationTimeout() <b>Raised</b>');
    }

   jQuery( document.body ).trigger( 'pro-pay-connection-timeout' );
 }

//==============================================================================================================================================
// The following functions and variables must NOT be modified except by experienced developers. ProPay will not troubleshoot changes made below
//==============================================================================================================================================

var hppURI = baseURI + '/hpp/home/';
var signalrURI = baseURI + '/hpp/signalr';
var debug = false;

//Object Reference Variables
var Connection;
var signalR;
var TimeoutTimerId;

// This function is called to set up and initiate and validate the SignalR Connection, Load the Hosted Payment Page and verify communication into the iFrame
function hpp_Load(hostedTransactionIdentifier, debugMode) {
    if(debugMode){
        debug = true;
    }

    // Add missing javascript functionality based on browser
    fixMissingBrowserFunctionality();

    // Wire up the scripting error handler
    window.onerror = window_OnError;

    // Wire up the window unload event
    window.onbeforeunload = signalR_OnUnload;

    // Set local variables
    hppURI = hppURI + hostedTransactionIdentifier;

    // Setup the connection to the server
    signalR_SetupConnection(hostedTransactionIdentifier);

    // Connect to the server
    signalR_Connect();
}

// This sets up the connection with the signalR on the server.
function signalR_SetupConnection(HID) {

    // Create a reference to the signalR
    Connection = jQuery.hubConnection();
    Connection.url = signalrURI;
    Connection.qs = { 'hid': HID, 'c': '0' };

    if(debug){
        jQuery.connection.fn.log = function (message) {
            echoSignalRConsoleMessage(message);
        }

    }

    // Get a reference to the signalR Proxy Connection
    signalR = Connection.createHubProxy('hostedTransaction');

    // Wire up all the SignalR events to local callback methods.
    Connection.starting(signalR_OnStarting);
    Connection.received(signalR_OnReceived);
    Connection.connectionSlow(signalR_OnConnectionSlow);
    Connection.reconnecting(signalR_OnReconnecting);
    Connection.reconnected(signalR_OnReconnect);
    Connection.stateChanged(signalR_OnStateChanged);
    Connection.disconnected(signalR_OnDisconnect);

    //Wire up all the SignalR Error Handler
    Connection.error(signalR_OnError);

    // Wire up the client-side function to receive calls from the server
    signalR.on('ping', signalR_OnPing);
    signalR.on('formSubmitSucceeded', signalR_OnFormSubmitSucceeded);
    signalR.on('formSubmitWasInvalid', signalR_OnFormWasInvalid);
    signalR.on('formSubmitErrored', signalR_OnFormSubmitErrored);
}

// This function starts the signalR and initialize the connection to the server signalR.
function signalR_Connect() {
    if (debug) {
        echoBrowserMessage('<b style="color: Green">Start: </b><span style="color: blue">Create SignalR Connection</span>');
    }
    Connection.start({}, signalR_OnEstablished)
        .done(signalR_OnConnected)
        .fail(signalR_OnConnectionFailed);
}

// Invoked after an error occurs with the connection.
function signalR_OnError(e, data) {
    if (e !== null) {
        var msg = e.message;
        if (valueIsValid(e) === true) {
            if (String(e).indexOf('Access is denied') !== -1 && navigator.userAgent.indexOf('MSIE 10.0') !== -1) {
                msg = e + ' (If you are using IE 10, try pressing F12 and switch the "Browser Mode" or "Document Mode" to "IE9")';
            } else {
                msg = e;
            }
        }
        echoBrowserMessage('<b style="color: red">Error Thrown: </b>SignalR Connection Error Message: ' + msg);
        echoBrowserMessage('<b style="color: red">Error Thrown: </b>Stack: ' + e.stack);
    }
}

// Invoked when any data is received on the connection from the signalR server.
function signalR_OnReceived(e, data) {
    if (debug) {
        echoSignalRMessage('<span style="color: blue"><b>SignalR Data Received</b></span><br> <b>&nbsp; &#149; Hub:</b> ' + e.H + '<br> <b>&nbsp; &#149; Method:</b> ' + e.M + '<br> <b>&nbsp; &#149; Callback Id:</b> ' + e.I);
    }
    //Unload the iFrame on successful submission
    if(e.M === 'formSubmitSucceeded'){
        document.getElementById(iFrameId).src = '';
        signalR_Disconnect();
    }
}

// Callback funrtion that is invoked on establishing a SignalR server connection this Loads the Hosted Payment Page into the specified iFrame
function signalR_OnEstablished() {
    if (debug) {
        echoBrowserMessage('<b>Loading Hosted Payment Page</b></span><br> <b>&nbsp; &#149; Hosted Payment Page URL:</b> <u>' + hppURI + '</u><br> <b>&nbsp; &#149; iFrame ID:</b> ' + iFrameId);
    }
    var iFrame = document.getElementById(iFrameId);
    if (iFrame) {
        iFrame.onload = signalR_OnIFrameLoaded;
        iFrame.src = hppURI;
    }
    else {
        if (debug) {
            echoBrowserMessage('<b style="color: red">Error Thrown: </b>Unable to locate an iFrame with element ID: ' + iFrameId);
        }
    }
}

// Invoked when the Hosted Payment Page in the specified iFrame has loaded.
// The Hosted Payment Page onLoad() Event sends a Ping Request through the SignalR Connection to this Page
// A Timeout is set for connection errors from the Hosted Payment Page to the SignalR Server
function signalR_OnIFrameLoaded() {
    if (debug) {
        echoBrowserMessage('<span style="color: blue"><b>Hosted Payment Page Form Loaded</b> - </span>Waiting for Ping Request From Hosted Payment Page Through SignalR Server<br> <b>&nbsp; &#149</b>Setting Ping Request Timout Timer to:<b> ' + TimeoutInterval + "ms</b>");
    }
    TimeoutTimerId = window.setTimeout(signalR_OnTimeout, TimeoutInterval);
}

// This function is invoked when the timer times out This indicates the Hosted Payment Page failed to send a Ping Request through the SignalR Server Connection.
// This indicates a problem with the connection of the Hosted Payment Page to the SignalR Server
function signalR_OnTimeout() {
    if (debug) {
        echoBrowserMessage('<b style="color: red">Error Thrown: </b>Failed to Receive Ping Request after: <b>' + TimeoutInterval + 'ms</b> Raising <b>formCommunicationTimeout</b>');
    }
    signalR_OnFormCommunicationTimeout();
    signalR_Disconnect();
}

// This function is called when the SignalR server sends the 'Ping' message indicated the Hosted Payment Page is Connected to it.
function signalR_OnPing(e, data) {
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: Blue">SignalR Server Ping Recieved - </span>Hosted Payment Page Connected to SignalR Server<br> <b>&nbsp; &#149</b>Sending Pong Response <br> <b>&nbsp; &#149</b>Stopping Ping Request Timout Timer');

    }

    if (TimeoutTimerId && valueIsNumeric(TimeoutTimerId) === true) {
        window.clearTimeout(TimeoutTimerId);
        TimeoutTimerId = undefined;
    }

    signalR.invoke('pong');

    if(debug){
         echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: blue">Form Ready For Submission</span> Raising <b>formIsReadyToSubmit()</b>');
    }

    formIsReadyToSubmit();
}

// This function sends a 'SubmitForm' message from the parent page to the server signalR... which relays a 'SubmitForm' message to the child page.
function signalR_SubmitForm() {
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: blue">Submit Form Method Invoked - </span>Sending Message to SignalRServer');
    }

    signalR.invoke('submitForm');
}

// Invoked when the page is unloaded to disconnect from the server.
function signalR_OnUnload() {
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: blue">Page Unloading');
    }

    signalR_Disconnect();
}

// This function stops the communication with the server signalR.
function signalR_Disconnect() {
    if (debug) {
        echoBrowserMessage('<b style="color: gold">Event: </b><span style="color: red">SignalR Connection Disconnecting</span>');
    }

    var iFrame = document.getElementById(iFrameId);
    if (iFrame) {
        iFrame.onload = null;
    }

    // If the Hosted Payment Page Communication Timer is active disable it.
    if (TimeoutTimerId && valueIsNumeric(TimeoutTimerId) === true) {
        window.clearTimeout(TimeoutTimerId);
        TimeoutTimerId = undefined;
    }

    // The 1st parameter of the 'stop' method is whether or not to asynchronously abort the connection.
    // The 2nd parameter of the 'stop' method is whether we want to notify the server that we are aborting the connection.
    Connection.stop(false, true);
}

// Gets a human readable string for the specified connection state ID
function getStateName(state) {
    switch (state) {
        case 0 : return 'Connecting';
        case 1 : return 'Connected';
        case 2 : return 'Reconnecting';
        case 4 : return 'Disconnected';
        default: return 'Unkown(' + state + ')';
    }
}

//This function checks if the value is Numberic
function valueIsNumeric(value) {
    return !isNaN(parseFloat(value)) && isFinite(value);
}

//The function checks if the value passed is not empty
function valueIsValid(value) {
    return (value && value !== '' && value.trim !== '');
}
