<?php
//Developer:    Charles Palmer
//Created:      2019.09.16
//Revision:     2019.11.18

/*
*   2019.11.18  CP  Added in getting of TLA info, reading firmware version from device
*/

//Get TLA Info
$tlaInfo = $common['db']->pec('
    SELECT 
        tla_number, 
        version, 
        filename, 
        md5,
        wide_vault
    FROM 
        2019_prod_tlas, 
        2019_prod_tla_info_product_4, 
        2019_prod_firmwares
    WHERE 
        ext_tla_id=tla_id AND 
        tla_id=? AND 
        ext_firmware_id=firmware_id 
    ORDER BY rev_timestamp DESC LIMIT 1',
    array($_REQUEST['tla_id']),'i',array(
    'tla_number', 
    'version', 
    'filename', 
    'md5', 
    'wide_vault'
));

if(isset($tlaInfo[0])) {
    ?>
    var TLA_ID                      = "<?=$_REQUEST['tla_id']; ?>";
    var TLA_NUMBER                  = "<?=$tlaInfo[0]['tla_number']; ?>";
    var TLA_FIRMWARE_VERSION        = "<?=$tlaInfo[0]['version']; ?>";
    var TLA_FIRMWARE_MD5            = "<?=$tlaInfo[0]['md5']; ?>";
    var TLA_FIRMWARE_URL            = "<?=BASE_URL . 'uploads/firmwares/7680/' . $tlaInfo[0]['filename']; ?>";
    var TLA_WIDE_VAULT              = "<?=$tlaInfo[0]['wide_vault']; ?>";
    <?php
}
?>

//Helper functions for the printer tests
//---------------------------------------------------------------------------------------------------------------
connectViaUSB = async (usbPID) => {
    try {
        await window.app.genericUSBDevice.connect(0x1431, usbPID, 0, 2, 130);
        return {
            passed: true
        };
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not connect to 7680. Error: ' + err
        };
    }
}
//---------------------------------------------------------------------------------------------------------------
getFirmwareVersionViaUSB = async () => {
    try {
        await window.app.genericUSBDevice.sendBytes([0x1B,0x47]);
        let bytesBack = await window.app.genericUSBDevice.getBytes(11);
        return {
            passed: true,
            firmwareVersion: bytesBack
        };
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not read firmware version. Error: ' + err
        };
    }
}
//---------------------------------------------------------------------------------------------------------------
getFirmwareVersionViaSerial = async () => {
    try {
        await window.app.rs232Device.sendBytes([0x1B,0x47]);
        let bytesBack = await window.app.rs232Device.getBytes(11);
        return {
            passed: true,
            firmwareVersion: bytesBack
        };
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not read firmware version. Error: ' + err
        };
    }
}
//---------------------------------------------------------------------------------------------------------------
readFileAsync = (fwBytes) => {
    return new Promise((resolve, reject) => {
        let arrayBuffer;
        let reader = new FileReader();

        reader.onload = (event) => {
            arrayBuffer = event.target.result;
        };

        reader.readAsArrayBuffer(fwBytes);
        reader.onloadend = (event) => {
            resolve(reader.result);
        };
    });
}
//---------------------------------------------------------------------------------------------------------------
updateFWViaUSB = async (fwBytes) => {
    try {
        console.log('pre enter boot');
        await window.app.genericUSBDevice.sendBytes([0x1B, 0x7E, 0x7D]); //Enter boot mode
        await window.app.genericUSBDevice.disconnect();
        console.log('post disconnect');
        
        //~~~~~~~~~~~~~~~~~~~~~~~~~~ Time Delay Start ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        window.app.allowBack = false;
        let timeWait = new Promise((resolve, reject) => {
            setTimeout(() => resolve(), 4000);
        });
        await timeWait;
        window.app.allowBack = true; 
        //~~~~~~~~~~~~~~~~~~~~~~~~~~~ Time Delay End ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        console.log('pre connect');
        await window.app.genericUSBDevice.connect(0x1431, 0x7680, 0, 2, 130); //Reconnect to device

        //~~~~~~~~~~~~~~~~~~~~~~~~~~ FW Upload Start ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        let contentBuffer = await readFileAsync(fwBytes);
        let curBuf = Buffer.from(contentBuffer);
        console.log('pre send fw dl cmd');
        await window.app.genericUSBDevice.sendBytes([0x1D, 0x0E]); //Boot Erase
        await window.app.genericUSBDevice.sendBytes([0x1D, 0x11, 0x00, 0x00, 0x00, 0x00]); //Enter Firmware download
        console.log('pre send fw bytes');
        await window.app.genericUSBDevice.sendBytes(curBuf); //Send firmware

        console.log('pre get resp');

        let response = await window.app.genericUSBDevice.getBytes(1);

        if(response[0] === 0x06 || response[0] === 0x50) {
            //Firmware successful, get version from device
            await window.app.genericUSBDevice.disconnect();
            //~~~~~~~~~~~~~~~~~~~~~~~~~~ Time Delay Start ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            window.app.allowBack = false;
            let timeWaitB = new Promise((resolve, reject) => {
                setTimeout(() => resolve(), 4000);
            });
            await timeWaitB;
            window.app.allowBack = true;
            //~~~~~~~~~~~~~~~~~~~~~~~~~~~ Time Delay End ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            await window.app.genericUSBDevice.connect(0x1431, 0x7680, 0, 2, 130); //Reconnect to device

            return getFirmwareVersionViaUSB();
        } else {
            //Firmware Failed
            await window.app.genericUSBDevice.disconnect();
            //~~~~~~~~~~~~~~~~~~~~~~~~~~ Time Delay Start ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            window.app.allowBack = false;
            let timeWaitB = new Promise((resolve, reject) => {
                setTimeout(() => resolve(), 4000);
            });
            await timeWaitB;
            window.app.allowBack = true;
            //~~~~~~~~~~~~~~~~~~~~~~~~~~~ Time Delay End ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            await window.app.genericUSBDevice.connect(0x1431, 0x7680, 0, 2, 130); //Reconnect to device
            return {
                passed: false,
                errorMsg: 'Failed to write firmware to device.'
            };
        }
        //~~~~~~~~~~~~~~~~~~~~~~~~~~~ FW Upload End ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not update firmware. Error: ' + err
        };
    }
};
//---------------------------------------------------------------------------------------------------------------
configureTLA = async () => {
    try {
        //Narrow or Wide vault
        if(TLA_WIDE_VAULT == 0) {
            console.log('Normal Vault');
            await window.app.genericUSBDevice.sendBytes([0x1B, 0x2D, 0x01, 0x68, 0x00, 0x44, 0x1B, 0x2C, 0x00]); //Normal vault
        } else {
            console.log('Wide Vault');
            await window.app.genericUSBDevice.sendBytes([0x1B, 0x2D, 0x00, 0xA6, 0x00, 0x44, 0x1B, 0x2C, 0x01]); //Wide vault
        }

        //Get config
        await window.app.genericUSBDevice.sendBytes([0x1B, 0x59]);
        let response = await window.app.genericUSBDevice.getBytes(128);

        //Check config
        if((response[12] && TLA_WIDE_VAULT == 0) || (!response[12] && TLA_WIDE_VAULT == 1)) {
            return {
                passed: false,
                errorMsg: 'Wide vault setting is incorrect on device'
            };
        }

        return {
            passed: true
        };
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not configure TLA. Error: ' + err
        };
    }
};
//---------------------------------------------------------------------------------------------------------------
checkFCT = async () => {
    try {
        //Get config
        await window.app.genericUSBDevice.sendBytes([0x1B, 0x59]);
        let response = await window.app.genericUSBDevice.getBytes(128);

        //Check config
        switch(response[0]) {
            case 0x03:
                //Clear FCT
                let securityResponse = await getSecurityBytes('fctKey');
                if(securityResponse.passed) {
                    await window.app.genericUSBDevice.sendBytes([0x1B, 0x79, 0x00, securityResponse.securityBytes[0], securityResponse.securityBytes[1]]);
                    return {
                        passed: true
                    };
                } else {
                    return securityResponse;
                }
            break;
            case 0x02:
                return {
                    passed: false,
                    errorMsg: 'Self test was attempted but failed'
                };
            break;
            default:
                return {
                    passed: false,
                    errorMsg: 'Self test was NOT attempted'
                };
            break;
        }
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not check FCT. Error: ' + err
        };
    }
};
//---------------------------------------------------------------------------------------------------------------
getSecurityBytes = async (mode) => {
    try {
        await window.app.genericUSBDevice.sendBytes([0x1B, 0x58]);
        let response = await window.app.genericUSBDevice.getBytes(2);

        let connectionInst = new serverRequest(CFG_DATA_SERVICE_URL);
        let servResponse = await connectionInst.postToServer({
            'mode': mode,
            'key0': response[0],
            'key1': response[1]
        }, '7680-printer-tests/');

        return {
            passed: true,
            securityBytes: servResponse.key
        };
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not get Security Seeds. Error: ' + err
        };
    }
};
//---------------------------------------------------------------------------------------------------------------
getSecurityBytesSerial = async (mode) => {
    try {
        await window.app.rs232Device.sendBytes([0x1B, 0x58]);
        let response = await window.app.rs232Device.getBytes(2);

        let connectionInst = new serverRequest(CFG_DATA_SERVICE_URL);
        let servResponse = await connectionInst.postToServer({
            'mode': mode,
            'key0': response[0],
            'key1': response[1]
        }, '7680-printer-tests/');

        return {
            passed: true,
            securityBytes: servResponse.key
        };
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not get Security Seeds. Error: ' + err
        };
    }
};
//---------------------------------------------------------------------------------------------------------------
getExtendedStatus = async () => {
    try {
        await window.app.genericUSBDevice.sendBytes([0x1B, 0x2B]);
        let response = await window.app.genericUSBDevice.getBytes(6);
        let status = {
            carriageHomeSensorInError: false,
            bursterHomeSensorInError: false,
            paperOut: false,
            paperLoadingError: false,
            failedTopOfFormMark: false,
            burstError: false,
            checksumError: false,
            feederNotPresent: false,
            burstSensorDefective: false,
            printerWasReset: false,
            printerWasResetWithFormLoaded: false,
            ejectFailed: false
        }

        if(response[0] & 0b1) {
            status.carriageHomeSensorInError = true;
        }
        if(response[0] & 0b10) {
            status.bursterHomeSensorInError = true;
        }
        if(response[0] & 0b100) {
            status.paperOut = true;
        }
        if(response[0] & 0b1000) {
            status.paperLoadingError = true;
        }
        if(response[0] & 0b10000) {
            status.failedTopOfFormMark = true;
        }
        if(response[0] & 0b100000) {
            status.burstError = true;
        }
        if(response[0] & 0b1000000) {
            status.checksumError = true;
        }
        if(response[0] & 0b10000000) {
            status.feederNotPresent = true;
        }

        if(response[1] & 0b1) {
            status.burstSensorDefective = true;
        }
        if(response[1] & 0b10) {
            status.printerWasReset = true;
        }
        if(response[1] & 0b100) {
            status.printerWasResetWithFormLoaded = true;
        }
        if(response[1] & 0b1000) {
            status.ejectFailed = true;
        }

        return {
            passed: true,
            status: status
        };
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not get status. Error: ' + err
        };
    }
};
//---------------------------------------------------------------------------------------------------------------
getExtendedStatusSerial = async () => {
    try {
        await window.app.rs232Device.sendBytes([0x1B, 0x2B], 300);
        let response = await window.app.rs232Device.getBytes(6);
        let status = {
            carriageHomeSensorInError: false,
            bursterHomeSensorInError: false,
            paperOut: false,
            paperLoadingError: false,
            failedTopOfFormMark: false,
            burstError: false,
            checksumError: false,
            feederNotPresent: false,
            burstSensorDefective: false,
            printerWasReset: false,
            printerWasResetWithFormLoaded: false,
            ejectFailed: false
        }

        if(response[0] & 0b1) {
            status.carriageHomeSensorInError = true;
        }
        if(response[0] & 0b10) {
            status.bursterHomeSensorInError = true;
        }
        if(response[0] & 0b100) {
            status.paperOut = true;
        }
        if(response[0] & 0b1000) {
            status.paperLoadingError = true;
        }
        if(response[0] & 0b10000) {
            status.failedTopOfFormMark = true;
        }
        if(response[0] & 0b100000) {
            status.burstError = true;
        }
        if(response[0] & 0b1000000) {
            status.checksumError = true;
        }
        if(response[0] & 0b10000000) {
            status.feederNotPresent = true;
        }

        if(response[1] & 0b1) {
            status.burstSensorDefective = true;
        }
        if(response[1] & 0b10) {
            status.printerWasReset = true;
        }
        if(response[1] & 0b100) {
            status.printerWasResetWithFormLoaded = true;
        }
        if(response[1] & 0b1000) {
            status.ejectFailed = true;
        }

        return {
            passed: true,
            status: status
        };
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not get status. Error: ' + err
        };
    }
};
//---------------------------------------------------------------------------------------------------------------
isPrinterOnPortIndex = async (validComPorts, checkIndex, baudRate, dataBits, stopBits, parity, rtscts, xon, xoff) => {
    try {
        if(checkIndex < validComPorts.length) {
            //Attempt connection to each found com port to see if printer exists there (stop on first valid find)
            await window.app.rs232Device.connect(validComPorts[checkIndex].comName,baudRate,dataBits,stopBits,parity,rtscts,xon,xoff);
            await window.app.rs232Device.sendBytes([0x1B, 0x41], 300); // Printer Status
                    
            let response = await window.app.rs232Device.getBytes(2);

            if(response[0] == 0x00 && (response[1] == 0x00 || response[1] == 0b10)) {
                //Our expected response
                return {
                    passed: true,
                    validComName: validComPorts[checkIndex].comName
                };
            } else {
                //Didn't match our expected response
                console.log('Wrong response: ', response);
                let response = await isPrinterOnPortIndex(validComPorts, checkIndex + 1, baudRate, dataBits, stopBits, parity, rtscts, xon, xoff);
                return response;
            }
        } else {
            return {
                passed: false,
                errorMsg: 'No printer found'
            };
        }
    } catch(err) {
        console.log(err);
        if(checkIndex < validComPorts.length) {
            let response = await isPrinterOnPortIndex(validComPorts, checkIndex + 1, baudRate, dataBits, stopBits, parity, rtscts, xon, xoff);
            return response;
        } else {
            return {
                passed: false,
                errorMsg: 'Could not find on serial. Error: ' + err
            };
        }
    }
};
//---------------------------------------------------------------------------------------------------------------
findPrinterOnSerial = async (baudRate, dataBits, stopBits, parity, rtscts, xon, xoff) => {
    try {
        let validComPorts = [];
        let foundComDevice = false;
        let ports = await window.app.rs232Device.findDevices();
        for(let i=0; i<ports.length; i++) {
            if(ports[i].manufacturer != null) {
              console.log('VID: ', ports[i].vendorId)
              console.log('PID: ', ports[i].productId)
              if(!(ports[i].vendorId == "04d8" && ports[i].productId == "ffee")) {
                validComPorts[0] = {
                    comName: ports[i].comName,
                    manufacturer: ports[i].manufacturer
                };
              }
            }
        }

        if(validComPorts.length) {
            let response = await isPrinterOnPortIndex(validComPorts, 0, baudRate, dataBits, stopBits, parity, rtscts, xon, xoff);
            if(response.passed) {
                return {
                    passed: true,
                    validComName: response.validComName
                };
            } else {
                return {
                    passed: false,
                    errorMsg: response.errorMsg
                };
            }
        } else {
            return {
                passed: false,
                errorMsg: 'No COM ports found'
            };
        }

    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not find on serial. Error: ' + err
        };
    }
};
//---------------------------------------------------------------------------------------------------------------
convertHexToPrintableHex = (hexByte) => {
    let response = [0x00, 0x00, 0x20];

    response[0] = (hexByte >> 4);
    if(response[0] <= 9) {
        response[0] += 48;
    } else {
        response[0] += (65 - 9);
    }

    response[1] = (hexByte & 0xF);
    if(response[1] <= 9) {
        response[1] += 48;
    } else {
        response[1] += (65 - 9);
    }

    return response;
};
//---------------------------------------------------------------------------------------------------------------
printHexConfigViaSerial = async () => {
    try {
        //Get config
        await window.app.rs232Device.sendBytes([0x1B, 0x59], 300);
        let configArray = await window.app.rs232Device.getBytes(128);

        let margin = new Array(30).fill(0x20);
        let doubleMargin = new Array(15).fill(0x20);

        let configArray2 = [];
        configArray2 = configArray2.concat(configArray);

        //Load form
        await window.app.rs232Device.sendBytes([0x1B, 0x46], 300);

        //~~~~~~~~~~~~~~~~~~~~~~~~~~ Time Delay Start ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        window.app.allowBack = false;
        let timeWait = new Promise((resolve, reject) => {
            setTimeout(() => resolve(), 4000);
        });
        await timeWait;
        window.app.allowBack = true;
        //~~~~~~~~~~~~~~~~~~~~~~~~~~~ Time Delay End ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        //Check for load
        await window.app.rs232Device.sendBytes([0x1B, 0x50], 300);
        let loadResponse = await window.app.rs232Device.getBytes(1);
        if(loadResponse[0] === 0x01) {
            //Get status
            let response = await getExtendedStatusSerial();
            if(response.passed) {
                console.log(JSON.stringify(response.status));
                if(response.status.paperOut || response.status.paperLoadingError) {
                    return {
                        passed: false,
                        errorMsg: 'Load sheet and retry.'
                    };
                }


                let printJob = [];

                //Advance form some
                printJob = printJob.concat([0x0A, 0x0A, 0x0A, 0x0A, 0x0A, 0x0A, 0x0A, 0x0A]);

                for(let i=128; i>=8; i-=8) {
                    printJob = printJob.concat(margin);
                    printJob = printJob.concat(convertHexToPrintableHex(configArray2[(i - 8)]));
                    printJob = printJob.concat(convertHexToPrintableHex(configArray2[(i - 7)]));
                    printJob = printJob.concat(convertHexToPrintableHex(configArray2[(i - 6)]));
                    printJob = printJob.concat(convertHexToPrintableHex(configArray2[(i - 5)]));
                    printJob = printJob.concat(convertHexToPrintableHex(configArray2[(i - 4)]));
                    printJob = printJob.concat(convertHexToPrintableHex(configArray2[(i - 3)]));
                    printJob = printJob.concat(convertHexToPrintableHex(configArray2[(i - 2)]));
                    printJob = printJob.concat(convertHexToPrintableHex(configArray2[(i - 1)]));
                    printJob = printJob.concat([0x0A]);
                }

                //Advance form some
                printJob = printJob.concat([0x0A]);
                await window.app.rs232Device.sendBytes(printJob, 300);

                //Font expansion to double wide, double high
                await window.app.rs232Device.sendBytes([0x1B, 0x4B, 0x32], 300);

                await window.app.rs232Device.sendBytes(doubleMargin, 300);
                await window.app.rs232Device.sendBytes([0x48, 0x45, 0x58, 0x20, 0x44, 0x55, 0x4D, 0x50, 0x0A], 300); //HEX DUMP

                //Font expansion to normal
                await window.app.rs232Device.sendBytes([0x1B, 0x4B, 0x30], 300);
                
                //Eject
                await window.app.rs232Device.sendBytes([0x1B, 0x45], 300);

                return {
                    passed: true
                };
            } else {
                return response;
            }
        } else {
            return {
                passed: false,
                errorMsg: 'Did NOT see form loaded.'
            };
        }
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not print config. Error: ' + err
        };
    }  
};
//---------------------------------------------------------------------------------------------------------------
printStringViaSerial = function(textLine) {
    return new Promise(function(resolve, reject) {
        let byteArray = [];

        for(var i=0; i < textLine.length; i++) {
            byteArray.push(textLine.charCodeAt(i));
        }
        
        window.app.rs232Device.sendBytes(byteArray).then(() => {
            resolve();
        }, (err) => {
            reject('Could not print line. Error: ' + err);
        });  
    });
};
//---------------------------------------------------------------------------------------------------------------
alignHead = async (alignmentVal) => {
    try {
        if(TLA_WIDE_VAULT == 0) {
            console.log('Normal Vault');
            await window.app.rs232Device.sendBytes([0x1B, 0x2D, 0x01, 0x68, 0x00, alignmentVal, 0x1B, 0x2C, 0x00]); //Normal vault
        } else {
            console.log('Wide Vault');
            await window.app.rs232Device.sendBytes([0x1B, 0x2D, 0x00, 0xA6, 0x00, alignmentVal, 0x1B, 0x2C, 0x01]); //Wide vault
        }
        document.getElementById("altInstructions").innerHTML = '';
        retryTest();
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not set alignment. Error: ' + err
        };
    }  
};
//---------------------------------------------------------------------------------------------------------------
alignHeadAndAdvance = async (alignmentVal) => {
    try {
        if(TLA_WIDE_VAULT == 0) {
            console.log('Normal Vault');
            await window.app.rs232Device.sendBytes([0x1B, 0x2D, 0x01, 0x68, 0x00, alignmentVal, 0x1B, 0x2C, 0x00]); //Normal vault
        } else {
            console.log('Wide Vault');
            await window.app.rs232Device.sendBytes([0x1B, 0x2D, 0x00, 0xA6, 0x00, alignmentVal, 0x1B, 0x2C, 0x01]); //Wide vault
        }
        document.getElementById("altInstructions").innerHTML = '';
        window.app.allowBack = true;
        window.app.showStateIndicator = false;
        window.app.resultState = 'pass';
        window.app.resultText = 'Alignment Test Passed.';
        window.app.showResultText = false;
        window.app.nextTestVisible = true;
        window.app.passTest();
    } catch(err) {
        return {
            passed: false,
            errorMsg: 'Could not set alignment. Error: ' + err
        };
    }  
};
//---------------------------------------------------------------------------------------------------------------
