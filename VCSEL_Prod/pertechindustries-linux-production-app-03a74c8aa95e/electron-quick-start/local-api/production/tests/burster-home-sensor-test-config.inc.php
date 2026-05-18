<?php
//Developer:    Charles Palmer
//Created:      2019.07.16
//Revision:     2019.07.16

/*
* 
*/
?>

//Helper functions for the burster home tests
var programmerSerialNumber = '';
//---------------------------------------------------------------------------------------------------------------
stringFromArray = function(data) {
    var count = data.length;
    var str = "";
    
    for(var index = 0; index < count; index += 1) {
      str += String.fromCharCode(data[index]);
    }
    
    return str;
};
//---------------------------------------------------------------------------------------------------------------
isBursterOnPortIndex = function(validComPorts, checkIndex) {
    return new Promise(function(resolve, reject) {
        if(checkIndex < validComPorts.length) {
            //Attempt connection to each found com port to see if burster exists there (stop on first valid find)
            window.app.rs232Device.connect(validComPorts[checkIndex].comName,'9600', '8', '1', 'none', false, false, false).then(() => {

                window.app.rs232Device.sendBytesIgnoreDSR([0x5A, 0x01], 300).then(() => { //Get Version info
                    window.app.rs232Device.getBytes(3).then((response) => {
                        if(response[0]==0x07) {
                            //Is the correct device
                            window.app.rs232Device.sendBytesIgnoreDSR([0x5A, 0x03], 300).then(() => { //Get Serial number
                                window.app.rs232Device.getBytes(8).then((response) => {
                                    programmerSerialNumber = stringFromArray(response);

                                    //Configure device
                                    window.app.rs232Device.sendBytesIgnoreDSR([0x5A, 0x02, 0x00, 0xFE], 300).then(() => { // I/O Mode, 3 analog inputs (IO2, IO3, IO4), 1 digital input (IO1)
                                        window.app.rs232Device.getBytes(2).then((response) => {
                                            if(response[0]==0xFF) {
                                                //Programming successful
                                                resolve(validComPorts[checkIndex].comName);
                                                
                                            } else {
                                                reject("Failed configuring programmer.");
                                            }
                                        }, () => {
                                            reject("Failed configuring programmer.");
                                        });
                                    }, () => {
                                        reject("Failed configuring programmer.");
                                    });
                                }, () => {
                                    reject("Failed getting programmer Serial.");
                                });
                            }, () => {
                                reject("Failed getting programmer Serial.");
                            });
                        } else {
                            //Wrong device
                            isBursterOnPortIndex(validComPorts, checkIndex + 1).then((validComName) => {
                                resolve(validComName);
                            }, (err) => {
                                reject(err);
                            });
                        }
                    }, () => {
                        isBursterOnPortIndex(validComPorts, checkIndex + 1).then((validComName) => {
                            resolve(validComName);
                        }, (err) => {
                            reject(err);
                        });
                    });

                }, (err) => {
                    console.log('error ' + err);
                    isBursterOnPortIndex(validComPorts, checkIndex + 1).then((validComName) => {
                        resolve(validComName);
                    }, (err) => {
                        reject(err);
                    });
                });
            }, (err) => {
                console.log('Connection attempt failed');
                isBursterOnPortIndex(validComPorts, checkIndex + 1).then((validComName) => {
                    resolve(validComName);
                }, (err) => {
                    reject(err);
                });
            });
        } else {
            reject('No Vcsel found.');
        }
    });
};
//---------------------------------------------------------------------------------------------------------------
findBursterOnSerial = function() {
    return new Promise(function(resolve, reject) {
        validComPorts = [];
        window.app.rs232Device.findDevices().then((ports) => {
            for(var i=0; i < ports.length; i++){
                if(ports[i].vendorId == "04d8" && ports[i].productId == "ffee") {
                    validComPorts[i] = {
                        "comName": ports[i].comName,
                        "manufacturer": ports[i].manufacturer
                    };
                }
            }

            if(validComPorts.length) {
                //Attempt connection
                isBursterOnPortIndex(validComPorts,0).then((validComName) => {
                    resolve(validComName);
                }, (err) => {
                    reject(err);
                });
            } else {
                reject('Burster programmer NOT found.');
            }
        }, (err) => {
            reject(err);
        });
    });
};
//---------------------------------------------------------------------------------------------------------------
getIsDeviceConnected = function() {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x65, 0x04, 0x0A], 300).then(() => {
                window.app.rs232Device.getBytes(2).then((response) => {
                    let reading = (response[0] << 8) + response[1];
                    let calcVal = (reading * 5) / 1023;
                    if(calcVal!=5) {
                        reject('Voltage is ' + calcVal);
                    } else {
                        resolve(calcVal);
                    }
                }, (err) => {
                    reject(err);
                });
            }, (err) => {
                reject(err);
            });
        },300);
    });   
};
//---------------------------------------------------------------------------------------------------------------
getLEDValue = function() {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x65, 0x03, 0x0A], 300).then(() => {
                window.app.rs232Device.getBytes(2).then((response) => {
                    let reading = (response[0] << 8) + response[1];
                    let calcVal = (reading * 5) / 1023;
                    resolve(calcVal);
                }, (err) => {
                    reject(err);
                });
            }, (err) => {
                reject(err);
            });
        },300);
    });   
};
//---------------------------------------------------------------------------------------------------------------
getCollectorValue = function() {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x65, 0x02, 0x0A], 300).then(() => {
                window.app.rs232Device.getBytes(2).then((response) => {
                    let reading = (response[0] << 8) + response[1];
                    let calcVal = (reading * 5) / 1023;
                    resolve(calcVal);
                }, (err) => {
                    reject(err);
                });
            }, (err) => {
                reject(err);
            });
        },300);
    });   
};
//---------------------------------------------------------------------------------------------------------------