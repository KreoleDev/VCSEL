<?php
//Developer:    Charles Palmer
//Created:      2019.06.17
//Revision:     2019.06.17

/*
* 
*/
?>

//Helper functions for the vcsel tests
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
isVcselOnPortIndex = function(validComPorts, checkIndex) {
    return new Promise(function(resolve, reject) {
        if(checkIndex < validComPorts.length) {
            //Attempt connection to each found com port to see if vcsel exists there (stop on first valid find)
            window.app.rs232Device.connect(validComPorts[checkIndex].comName,'9600', '8', '1', 'none', false, false, false).then(() => {

                window.app.rs232Device.sendBytesIgnoreDSR([0x5A, 0x01], 300).then(() => { //Get Version info
                    window.app.rs232Device.getBytes(3).then((response) => {
                        if(response[0]==0x07) {
                            //Is the correct device
                            window.app.rs232Device.sendBytesIgnoreDSR([0x5A, 0x03], 300).then(() => { //Get Serial number
                                window.app.rs232Device.getBytes(8).then((response) => {
                                    programmerSerialNumber = stringFromArray(response);

                                    //Configure device
                                    window.app.rs232Device.sendBytesIgnoreDSR([0x5A, 0x02, 0x70, 0x0F], 300).then(() => { //I2C_H_400KHZ, 2 analog inputs
                                        window.app.rs232Device.getBytes(2).then((response) => {
                                            if(response[0]==0xFF) {
                                                //Programming successful

                                                //Verify a response happens when requesting digital pot value
                                                getCollectorPotNV().then((response2) => {
                                                    if(response2==1023) {
                                                        reject("Vcsel Board NOT Loaded.");
                                                    } else {
                                                        resolve(validComPorts[checkIndex].comName);
                                                    }
                                                }, (err) => {
                                                    reject("Vcsel Board NOT Loaded.");
                                                });
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
                            isVcselOnPortIndex(validComPorts, checkIndex + 1).then((validComName) => {
                                resolve(validComName);
                            }, (err) => {
                                reject(err);
                            });
                        }
                    }, () => {
                        isVcselOnPortIndex(validComPorts, checkIndex + 1).then((validComName) => {
                            resolve(validComName);
                        }, (err) => {
                            reject(err);
                        });
                    });

                }, (err) => {
                    console.log('error ' + err);
                    isVcselOnPortIndex(validComPorts, checkIndex + 1).then((validComName) => {
                        resolve(validComName);
                    }, (err) => {
                        reject(err);
                    });
                });
            }, (err) => {
                console.log('Connection attempt failed');
                isVcselOnPortIndex(validComPorts, checkIndex + 1).then((validComName) => {
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
findVcselOnSerial = function() {
    return new Promise(function(resolve, reject) {
        validComPorts = [];
        window.app.rs232Device.findDevices().then((ports) => {
            for(var i=0; i < ports.length; i++){
                //console.log('Ven: ' + ports[i].vendorId + ' PID: ' + ports[i].productId);
                if(ports[i].vendorId == "04d8" && ports[i].productId == "ffee") {
                    validComPorts[0] = {
                        "comName": ports[i].comName,
                        "manufacturer": ports[i].manufacturer
                    };
                }
            }

            if(validComPorts.length) {
                //Attempt connection
                isVcselOnPortIndex(validComPorts,0).then((validComName) => {
                    resolve(validComName);
                }, (err) => {
                    reject(err);
                });
            } else {
                reject('Vcsel programmer NOT found.');
            }
        }, (err) => {
            reject(err);
        });
    });
};
//---------------------------------------------------------------------------------------------------------------
getCollectorVoltage = function() {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x65, 0x02, 0x0A], 300).then(() => {
                window.app.rs232Device.getBytes(2).then((response) => {
                    let reading = (response[0] << 8) + response[1];
                    resolve((reading * 3.3) / 1023);
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
getCollectorVoltageFast = function() {
    return new Promise(function(resolve, reject) {
      setTimeout(()=> {
        window.app.rs232Device.sendBytesIgnoreDSR([0x65, 0x02, 0x0A], 300).then(() => {
            window.app.rs232Device.getBytes(2).then((response) => {
                let reading = (response[0] << 8) + response[1];
                resolve((reading * 3.3) / 1023);
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
getCollectorPotNV = function() {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5F, 0x2C, 0x02], 300).then(() => { //0x5F = read at collector address, 0x2C = read NV Wiper 0, 0x02 = Bytes to read
                window.app.rs232Device.getBytes(2).then((response) => {
                    let calcVal = ((response[0] & 0x03) << 8) + response[1];
                    resolve(calcVal);
                }, (err) => {
                    console.log('failed getting bytes');
                    reject(err);
                });
            }, (err) => {
                reject(err);
            });
        },100);  
    });   
};
//---------------------------------------------------------------------------------------------------------------
setCollectorPotNV = function(valueToSet) {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5E, 0x20, 0x01, valueToSet], 300).then(() => { //0x5E = write at collector address, 0x20 = write Wiper 0, 0x01 = Bytes to write
                resolve();
            }, (err) => {
                reject(err);
            });
        },100);  
    });   
};
//---------------------------------------------------------------------------------------------------------------
getCollectorPot = function() {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5F, 0x0C, 0x02], 300).then(() => { //0x5F = read at collector address, 0x0C = read Wiper 0, 0x02 = Bytes to read
                window.app.rs232Device.getBytes(2).then((response) => {
                    let calcVal = ((response[0] & 0x03) << 8) + response[1];
                    resolve(calcVal);
                }, (err) => {
                    console.log('failed getting bytes');
                    reject(err);
                });
            }, (err) => {
                reject(err);
            });
        },100);  
    });   
};
//---------------------------------------------------------------------------------------------------------------
setCollectorPot = function(valueToSet) {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5E, 0x00, 0x01, valueToSet], 300).then(() => { //0x5E = write at collector address, 0x00 = write Wiper 0, 0x01 = Bytes to write
                resolve();
            }, (err) => {
                reject(err);
            });
        },100);  
    });   
};
//---------------------------------------------------------------------------------------------------------------
setCollectorPotFast = function(valueToSet) {
    return new Promise(function(resolve, reject) {
        window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5E, 0x00, 0x01, valueToSet], 300).then(() => { //0x5E = write at collector address, 0x00 = write Wiper 0, 0x01 = Bytes to write
          setTimeout(()=> {
            resolve();
          },100);
        }, (err) => {
            reject(err);
        });
    });   
};
//---------------------------------------------------------------------------------------------------------------
setCollectorPotInit = function(valueToSet) {
    return new Promise(function(resolve, reject) {
        window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5E, 0x00, 0x01, valueToSet], 300).then(() => { //0x5E = write at collector address, 0x00 = write Wiper 0, 0x01 = Bytes to write
          setTimeout(()=> {
            resolve();
          },100);  
        }, (err) => {
            reject(err);
        });
    });   
};
//---------------------------------------------------------------------------------------------------------------
getTransmitterPotNV = function() {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5D, 0x2C, 0x02], 300).then(() => { //0x5D = read at transmitter address, 0x2C = read NV Wiper 0, 0x02 = Bytes to read
                window.app.rs232Device.getBytes(2).then((response) => {
                    let calcVal = ((response[0] & 0x03) << 8) + response[1];
                    resolve(calcVal);
                }, (err) => {
                    console.log('failed getting bytes');
                    reject(err);
                });
            }, (err) => {
                reject(err);
            });
        },100);  
    });   
};
//---------------------------------------------------------------------------------------------------------------
setTransmitterPotNV = function(valueToSet) {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5C, 0x20, 0x01, valueToSet], 300).then(() => { //0x5C = write at transmitter address, 0x20 = write Wiper 0, 0x01 = Bytes to write
                resolve();
            }, (err) => {
                reject(err);
            });
        },100);  
    });   
};
//---------------------------------------------------------------------------------------------------------------
getTransmitterPot = function() {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5D, 0x0C, 0x02], 300).then(() => { //0x5D = read at transmitter address, 0x0C = read Wiper 0, 0x02 = Bytes to read
                window.app.rs232Device.getBytes(2).then((response) => {
                    let calcVal = ((response[0] & 0x03) << 8) + response[1];
                    resolve(calcVal);
                }, (err) => {
                    console.log('failed getting bytes');
                    reject(err);
                });
            }, (err) => {
                reject(err);
            });
        },100);  
    });   
};
//---------------------------------------------------------------------------------------------------------------
setTransmitterPot = function(valueToSet) {
    return new Promise(function(resolve, reject) {
        setTimeout(()=> {
            window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5C, 0x00, 0x01, valueToSet], 300).then(() => { //0x5C = write at transmitter address, 0x00 = write Wiper 0, 0x01 = Bytes to write
                resolve();
            }, (err) => {
                reject(err);
            });
        },100);  
    });   
};
//---------------------------------------------------------------------------------------------------------------
setTransmitterPotInit = function(valueToSet) {
    return new Promise(function(resolve, reject) {
        window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5C, 0x00, 0x01, valueToSet], 300).then(() => { //0x5C = write at transmitter address, 0x00 = write Wiper 0, 0x01 = Bytes to write
          setTimeout(()=> {
            resolve();
          },100); 
        }, (err) => {
            reject(err);
        });
    });   
};
//---------------------------------------------------------------------------------------------------------------
setTransmitterPotFast = function(valueToSet) {
    return new Promise(function(resolve, reject) {
        window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5C, 0x00, 0x01, valueToSet], 300).then(() => { //0x5C = write at transmitter address, 0x00 = write Wiper 0, 0x01 = Bytes to write
          setTimeout(()=> {
            resolve();
          },100);
        }, (err) => {
            reject(err);
        });
    });   
};
//---------------------------------------------------------------------------------------------------------------
setSerialNumber = function(valueToSet) {
  return new Promise(function(resolve, reject) {
      let highByte = (valueToSet >> 16) & 0xFF;
      let middleByte = (valueToSet >> 8) & 0xFF;
      let lowByte = valueToSet & 0xFF;
      setTimeout(()=> {
          window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5E, 0x60, 0x01, highByte], 300).then(() => { //0x5E = write at collector address, 0x06 = write Data Eprom 0, 0x01 = Bytes to write
            window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5E, 0x70, 0x01, middleByte], 300).then(() => { //0x5E = write at collector address, 0x06 = write Data Eprom 0, 0x01 = Bytes to write
              window.app.rs232Device.sendBytesIgnoreDSR([0x55, 0x5E, 0x80, 0x01, lowByte], 300).then(() => { //0x5E = write at collector address, 0x06 = write Data Eprom 0, 0x01 = Bytes to write
                  resolve();
              }, (err) => {
                  reject(err);
              });
            }, (err) => {
                reject(err);
            });
          }, (err) => {
              reject(err);
          });
      },100);  
  });
};
//---------------------------------------------------------------------------------------------------------------
getSerialNumber = async function() {
  let highByte = await getRegisterValue(6)
  let middleByte = await getRegisterValue(7)
  let lowByte = await getRegisterValue(8)
  console.log(highByte, middleByte, lowByte)
  return (((highByte & 0xFF) << 16) + ((middleByte & 0xFF) << 8) + (lowByte & 0xFF))
};
//---------------------------------------------------------------------------------------------------------------
getRegisterValue = function(registerToRead) {
  return new Promise(function(resolve, reject) {
      setTimeout(()=> {
          window.app.rs232Device.sendBytesIgnoreDSR([0x57, 0x01, 0x31, 0x5E, 0x0C | (registerToRead << 4), 0x02, 0x30, 0x5F, 0x20, 0x04, 0x20, 0x03], 300).then(() => {
              window.app.rs232Device.getBytes(4).then((response) => {
                let calcVal = ((response[2] & 0x01) << 8) + response[3];
                  resolve(calcVal);
              }, (err) => {
                  console.log('failed getting bytes');
                  reject(err);
              });
          }, (err) => {
              reject(err);
          });
      },100);  
  });
};
//---------------------------------------------------------------------------------------------------------------
getRegisterValueTransmitter = function(registerToRead) {
  return new Promise(function(resolve, reject) {
      setTimeout(()=> {
          window.app.rs232Device.sendBytesIgnoreDSR([0x57, 0x01, 0x31, 0x5C, 0x0C | (registerToRead << 4), 0x02, 0x30, 0x5D, 0x20, 0x04, 0x20, 0x03], 300).then(() => {
              window.app.rs232Device.getBytes(4).then((response) => {
                let calcVal = ((response[2] & 0x01) << 8) + response[3];
                  resolve(calcVal);
              }, (err) => {
                  console.log('failed getting bytes');
                  reject(err);
              });
          }, (err) => {
              reject(err);
          });
      },100);  
  });
};
//---------------------------------------------------------------------------------------------------------------
