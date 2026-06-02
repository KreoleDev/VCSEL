'use strict';

function printHeading(title) {
    console.log('');
    console.log('=== ' + title + ' ===');
}

function printPorts(ports) {
    if (!ports || ports.length === 0) {
        console.log('No serial ports found.');
        return;
    }

    ports.forEach(function(port, index) {
        console.log(
            String(index + 1) + '. ' +
            (port.path || port.comName || '(unknown port)') +
            (port.manufacturer ? ' | ' + port.manufacturer : '') +
            (port.serialNumber ? ' | serial ' + port.serialNumber : '')
        );
    });
}

printHeading('Electron');
console.log('Electron version: ' + process.versions.electron);
console.log('Node version: ' + process.versions.node);
console.log('Platform: ' + process.platform + ' ' + process.arch);

printHeading('SerialPort');

try {
    var serialport = require('serialport');
    var SerialPortClass = serialport.SerialPort || serialport;
    var listPorts = SerialPortClass.list ? SerialPortClass.list.bind(SerialPortClass) : serialport.list.bind(serialport);

    console.log('serialport module loaded.');

    Promise.resolve(listPorts()).then(function(ports) {
        printPorts(ports);
        console.log('');
        console.log('If the VCSEL controller is not listed, check USB/serial cable, driver, and Device Manager.');
        console.log('If this command failed before listing ports, rebuild/install the native serialport module on this PC.');
        process.exit(0);
    }).catch(function(err) {
        console.error('Could not list serial ports:');
        console.error(err && err.stack ? err.stack : err);
        process.exit(1);
    });
} catch (err) {
    console.error('Could not load serialport inside Electron:');
    console.error(err && err.stack ? err.stack : err);
    console.log('');
    console.log('Try reinstalling dependencies on this Windows PC, then rerun this diagnostic:');
    console.log('  rmdir /s /q node_modules');
    console.log('  del package-lock.json');
    console.log('  npm install');
    console.log('  npm run diagnose:hardware');
    process.exit(1);
}
