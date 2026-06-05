//------------------------------------------------------------------------------------------------------
function aniShowLoading(){
    return new Promise(function(resolve) {
        Velocity(loadingMessage, { opacity:1 }, { duration: 500 });
        Velocity(loadingSlate, { opacity:1 }, { duration: 300, complete: function() {
            resolve();
        }});
    }, function(reject) {
        
    });
}
//------------------------------------------------------------------------------------------------------
function aniHideLoading(){
    return new Promise(function(resolve) {
        Velocity(loadingMessage, { opacity:0 }, { duration: 500 });
        Velocity(mainContent, { opacity: 1 }, { delay: 200, duration: 400, complete: function() {
            resolve();
        }});
        Velocity(loadingSlate, { opacity:0 }, { duration: 500 });
    }, function(reject) {
        
    });
}
//------------------------------------------------------------------------------------------------------
function aniShowError(){
    return new Promise(function(resolve) {
        var loadingMessage = document.getElementById("loadingMessage");
        var loadingSlate = document.getElementById("loadingSlate");
        var errorSlate = document.getElementById("errorSlate");

        if(loadingMessage != null){
            Velocity(loadingMessage, { opacity:0 }, { duration: 500 });
            Velocity(loadingSlate, { opacity:0 }, { duration: 300 });
        }

        Velocity(errorSlate, { opacity:1 }, { delay: 500, duration: 300, complete: function() {
            resolve();
        }});
    }, function(reject) {
        
    });
}
//------------------------------------------------------------------------------------------------------
function aniHideError(){
    return new Promise(function(resolve) {
        Velocity(mainContent, { opacity: 1 }, { delay: 200, duration: 400, complete: function() {
            resolve();
        }});
        Velocity(errorSlate, { opacity:0 }, { duration: 500 });
    }, function(reject) {
        
    });
}
//------------------------------------------------------------------------------------------------------
function aniShowErrorPrompt(){
    return new Promise(function(resolve) {
        Velocity(errorPrompt, { opacity:1 }, { duration: 400, complete: function() {
            resolve();
        }});
    }, function(reject) {
        
    });
}
//------------------------------------------------------------------------------------------------------
function aniHideErrorPrompt(){
    return new Promise(function(resolve) {
        Velocity(errorPrompt, { opacity: 0 }, { duration: 400, complete: function() {
            resolve();
        }});
    }, function(reject) {
        
    });
}
//------------------------------------------------------------------------------------------------------
function aniUnloadPage() {
    return new Promise(function(resolve) {
        var loadingMessage = document.getElementById("loadingMessage");
        var mainContent = document.getElementById("mainContent");
        var loadingSlate = document.getElementById("loadingSlate");

        if(loadingMessage != null){
            Velocity(loadingMessage, { opacity:0 }, { duration: 300 });
        }
        Velocity(mainContent, { opacity: 0 }, { duration: 300, complete: function() {
            resolve();
        }});
        if(loadingSlate != null) {
            Velocity(loadingSlate, { opacity:0 }, { duration: 300 });
        }
    }, function(reject) {
        
    });
}
//------------------------------------------------------------------------------------------------------