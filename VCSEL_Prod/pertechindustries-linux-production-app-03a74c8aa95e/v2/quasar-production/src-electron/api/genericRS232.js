import { SerialPort } from "serialport";

let genericRS232 = {
  connected: false,
  serialPort: null,
  receivedData: [],

  getDeviceList: () => {
    const errPrepend = "Get Device List: ";
    return new Promise(async (resolve, reject) => {
      try {
        let ports = await SerialPort.list();
        resolve(ports);
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  connect: (
    comPort,
    baudRate,
    dataBits,
    stopBits,
    parity,
    rtscts,
    xon,
    xoff
  ) => {
    const errPrepend = "Connect: ";
    return new Promise(async (resolve, reject) => {
      try {
        // Disconnect if already connected
        if (genericRS232.connected) {
          await genericRS232.disconnect();
        }
        // Open device
        genericRS232.serialPort = new SerialPort({
          path: comPort.path,
          baudRate: baudRate,
          dataBits: dataBits,
          lock: true,
          stopBits: stopBits,
          parity: parity,
          rtscts: rtscts,
          xon: xon,
          xoff: xoff,
        });
        genericRS232.serialPort.on("error", (error) => {
          console.log(errPrepend + error);
          reject(errPrepend + error);
        });
        genericRS232.serialPort.on("data", (data) => {
          if (Buffer.byteLength(data) > 0) {
            for (let i = 0; i < Buffer.byteLength(data); i++) {
              genericRS232.receivedData.push(data[i]);
            }
          }
        });
        genericRS232.serialPort.on("open", () => {
          genericRS232.connected = true;
          resolve();
        });
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  disconnect: () => {
    const errPrepend = "Disconnect: ";
    return new Promise(async (resolve, reject) => {
      try {
        if (genericRS232.connected) {
          genericRS232.serialPort.close();
          genericRS232.connected = false;

          genericRS232.serialPort = null;
          genericRS232.receivedData = [];

          resolve();
        } else {
          reject(errPrepend + "Not connected");
        }
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  sendBytes: (bytes) => {
    const errPrepend = "Send Bytes: ";
    return new Promise(async (resolve, reject) => {
      try {
        if (genericRS232.connected) {
          genericRS232.receivedData = [];
          await genericRS232.serialPort.write(bytes);
          resolve();
        } else {
          reject(errPrepend + "Not connected");
        }
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  getBytes: (byteCountExpected) => {
    const errPrepend = "Get Bytes: ";
    return new Promise(async (resolve, reject) => {
      try {
        if (genericRS232.connected) {
          // Wait for data
          let waitCount = 0;
          let waitInterval = setInterval(() => {
            waitCount++;
            if (genericRS232.receivedData.length > byteCountExpected) {
              clearInterval(waitInterval);
              reject(errPrepend + "Data length mismatch");
            } else if (genericRS232.receivedData.length === byteCountExpected) {
              clearInterval(waitInterval);

              // Return data
              resolve(genericRS232.receivedData);
            } else if (waitCount > 300) {
              clearInterval(waitInterval);
              reject(errPrepend + "Timeout");
            }
          }, 10);
        } else {
          reject(errPrepend + "Not connected");
        }
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  sendAndReceiveBytes: (bytes, byteCountExpected) => {
    const errPrepend = "Send And Receive Bytes: ";
    return new Promise(async (resolve, reject) => {
      try {
        if (genericRS232.connected) {
          // Send bytes
          await genericRS232.sendBytes(bytes);

          // Get bytes
          const receivedBytes = await genericRS232.getBytes(byteCountExpected);

          // Return bytes
          resolve(receivedBytes);
        } else {
          reject(errPrepend + "Not connected");
        }
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
};

export default genericRS232;
