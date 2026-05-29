function VcselCameraAnalyzer(options) {
    options = options || {};

    this.preferredCameraName = options.preferredCameraName || 'USB 2.0 PC Cam';
    this.brightnessMin = options.brightnessMin || 159;
    this.brightnessMax = options.brightnessMax || 220;
    this.areaMin = options.areaMin || 599;
    this.areaMax = options.areaMax || 4500;
    this.analysisWidth = 320;
    this.analysisHeight = 240;
    this.videoElement = null;
    this.overlayCanvas = null;
    this.overlayContext = null;
    this.analysisCanvas = document.createElement('canvas');
    this.analysisCanvas.width = this.analysisWidth;
    this.analysisCanvas.height = this.analysisHeight;
    this.analysisContext = this.analysisCanvas.getContext('2d');
    this.stream = null;
    this.running = false;
    this.armed = false;
    this.locked = false;
    this.lastSavedSerial = '';
    this.latest = {
        brightness: 0,
        area: 0,
        ready: false
    };
    this.statusCallback = options.onStatus || function() {};
    this.measurementCallback = options.onMeasurement || function() {};
    this.savedCallback = options.onSaved || function() {};
    this.saveMeasurementCallback = options.onSaveMeasurement || function() {};
    this.lastAnalysisTime = 0;
    this.logFilePath = this.getLogFilePath();
}

VcselCameraAnalyzer.prototype.getLogFilePath = function() {
    try {
        var path = require('path');
        var os = require('os');
        return path.join(os.homedir(), 'vcsel_log.csv');
    } catch (err) {
        return 'vcsel_log.csv';
    }
};

VcselCameraAnalyzer.prototype.setElements = function(videoElement, overlayCanvas) {
    this.videoElement = videoElement;
    this.overlayCanvas = overlayCanvas;
    this.overlayContext = overlayCanvas ? overlayCanvas.getContext('2d') : null;
};

VcselCameraAnalyzer.prototype.start = function() {
    var self = this;

    if (this.running) {
        return Promise.resolve();
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        this.statusCallback('Camera API unavailable');
        return Promise.reject(new Error('Camera API unavailable'));
    }

    return navigator.mediaDevices.getUserMedia({ video: true, audio: false }).then(function(initialStream) {
        self.stream = initialStream;
        return navigator.mediaDevices.enumerateDevices().then(function(devices) {
            var preferredDevice = null;

            for (var i = 0; i < devices.length; i++) {
                if (devices[i].kind === 'videoinput' && devices[i].label.indexOf(self.preferredCameraName) >= 0) {
                    preferredDevice = devices[i];
                    break;
                }
            }

            if (!preferredDevice) {
                return initialStream;
            }

            self.stopStream(initialStream);
            return navigator.mediaDevices.getUserMedia({
                video: {
                    deviceId: { exact: preferredDevice.deviceId },
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            });
        });
    }).then(function(stream) {
        self.stream = stream;
        self.running = true;
        self.locked = false;

        if (self.videoElement) {
            self.videoElement.srcObject = stream;
            self.videoElement.play();
        }

        self.statusCallback('Camera live');
        self.tick();
    }).catch(function(err) {
        self.statusCallback('Camera unavailable: ' + err.message);
        throw err;
    });
};

VcselCameraAnalyzer.prototype.stopStream = function(stream) {
    if (!stream) {
        return;
    }

    var tracks = stream.getTracks();
    for (var i = 0; i < tracks.length; i++) {
        tracks[i].stop();
    }
};

VcselCameraAnalyzer.prototype.stop = function() {
    this.running = false;
    this.stopStream(this.stream);
    this.stream = null;
};

VcselCameraAnalyzer.prototype.arm = function() {
    var shouldStartReading = !this.running;
    this.armed = true;
    this.locked = false;
    this.lastSavedSerial = '';
    this.running = true;
    if (this.videoElement && this.videoElement.paused) {
        this.videoElement.play();
    }
    this.statusCallback('Waiting for VCSEL serial');
    if (shouldStartReading) {
        this.tick();
    }
};

VcselCameraAnalyzer.prototype.disarm = function() {
    this.armed = false;
};

VcselCameraAnalyzer.prototype.resetSavedSerial = function() {
    this.lastSavedSerial = '';
};

VcselCameraAnalyzer.prototype.tick = function(timestamp) {
    var self = this;

    if (!this.running || this.locked) {
        return;
    }

    if (!this.lastAnalysisTime || timestamp - this.lastAnalysisTime > 120) {
        this.lastAnalysisTime = timestamp || 0;
        this.analyzeFrame();
    }

    window.requestAnimationFrame(function(nextTimestamp) {
        self.tick(nextTimestamp);
    });
};

VcselCameraAnalyzer.prototype.analyzeFrame = function() {
    if (!this.videoElement || this.videoElement.readyState < 2) {
        return;
    }

    this.analysisContext.drawImage(this.videoElement, 0, 0, this.analysisWidth, this.analysisHeight);
    var imageData = this.analysisContext.getImageData(0, 0, this.analysisWidth, this.analysisHeight);
    var data = imageData.data;
    var threshold = 150;
    var count = 0;
    var brightnessTotal = 0;
    var minX = this.analysisWidth;
    var minY = this.analysisHeight;
    var maxX = 0;
    var maxY = 0;

    for (var y = 0; y < this.analysisHeight; y++) {
        for (var x = 0; x < this.analysisWidth; x++) {
            var index = (y * this.analysisWidth + x) * 4;
            var gray = (data[index] * 0.299) + (data[index + 1] * 0.587) + (data[index + 2] * 0.114);

            if (gray > threshold) {
                count++;
                brightnessTotal += gray;
                minX = Math.min(minX, x);
                minY = Math.min(minY, y);
                maxX = Math.max(maxX, x);
                maxY = Math.max(maxY, y);
            }
        }
    }

    var brightness = count > 0 ? brightnessTotal / count : 0;
    if (brightness <= 20) {
        count = 0;
        brightness = 0;
    }

    var scaledArea = count * 4;
    this.latest = {
        brightness: brightness,
        area: scaledArea,
        ready: this.isReady(brightness, scaledArea)
    };
    this.measurementCallback(this.latest);
    this.drawOverlay(minX, minY, maxX, maxY, count > 0);
};

VcselCameraAnalyzer.prototype.drawOverlay = function(minX, minY, maxX, maxY, hasSpot) {
    if (!this.overlayCanvas || !this.overlayContext || !this.videoElement) {
        return;
    }

    var width = this.overlayCanvas.width;
    var height = this.overlayCanvas.height;
    var ctx = this.overlayContext;
    ctx.clearRect(0, 0, width, height);
    ctx.strokeStyle = 'rgba(255,255,255,0.75)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(0, 0);
    ctx.lineTo(width, height);
    ctx.moveTo(width, 0);
    ctx.lineTo(0, height);
    ctx.stroke();

    if (hasSpot) {
        var scaleX = width / this.analysisWidth;
        var scaleY = height / this.analysisHeight;
        ctx.strokeStyle = '#00ff00';
        ctx.lineWidth = 2;
        ctx.strokeRect(minX * scaleX, minY * scaleY, (maxX - minX) * scaleX, (maxY - minY) * scaleY);
    }
};

VcselCameraAnalyzer.prototype.isReady = function(brightness, area) {
    return brightness >= this.brightnessMin &&
        brightness <= this.brightnessMax &&
        area >= this.areaMin &&
        area <= this.areaMax;
};

VcselCameraAnalyzer.prototype.extractSerial = function(text) {
    var match = /Serial\s*#?\s*(\d{5,})/i.exec(text || '');
    return match ? match[1] : null;
};

VcselCameraAnalyzer.prototype.captureSerialResult = function(resultText) {
    var serial = this.extractSerial(resultText);

    if (!serial || !this.armed) {
        return false;
    }

    if (serial === this.lastSavedSerial) {
        this.statusCallback('Duplicate serial ' + serial);
        this.armed = false;
        return false;
    }

    if (!this.latest.ready) {
        this.statusCallback('Serial ' + serial + ' found; camera values out of range');
        return false;
    }

    this.saveMeasurement(serial, 'Auto');
    this.armed = false;
    return true;
};

VcselCameraAnalyzer.prototype.saveMeasurement = function(serial, acquired) {
    var fs = require('fs');
    var exists = fs.existsSync(this.logFilePath);
    var timestamp = this.formatTimestamp(new Date());
    var lotNumber = 'LOT0000';
    var acquiredValue = acquired || 'Auto';
    var row = [
        timestamp,
        this.latest.brightness.toFixed(2),
        this.latest.area.toFixed(2),
        lotNumber,
        serial,
        acquiredValue
    ].join(',') + '\n';

    if (!exists) {
        fs.writeFileSync(this.logFilePath, 'Timestamp,Brightness,Area,Lot Number,Serial Number,Acquired\n');
    }

    fs.appendFileSync(this.logFilePath, row);
    this.lastSavedSerial = serial;
    this.armed = false;
    this.locked = true;
    this.running = false;
    if (this.videoElement) {
        this.videoElement.pause();
    }
    this.statusCallback('Saved serial ' + serial);
    this.savedCallback(serial, this.logFilePath);
    this.saveMeasurementCallback({
        timestamp: timestamp,
        brightness: Number(this.latest.brightness.toFixed(2)),
        area: Number(this.latest.area.toFixed(2)),
        lot_number: lotNumber,
        serial_number: serial,
        acquired: acquiredValue
    });
};

VcselCameraAnalyzer.prototype.formatTimestamp = function(date) {
    function pad(value) {
        return value < 10 ? '0' + value : '' + value;
    }

    return date.getFullYear() + '-' +
        pad(date.getMonth() + 1) + '-' +
        pad(date.getDate()) + ' ' +
        pad(date.getHours()) + ':' +
        pad(date.getMinutes()) + ':' +
        pad(date.getSeconds());
};

window.VcselCameraAnalyzer = VcselCameraAnalyzer;
