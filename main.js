// Modules to control application life and create native browser window
const {app, BrowserWindow, Menu, ipcRenderer, session} = require('electron');

function navigate (routePath) {
  if (mainWindow.webContents) {
    mainWindow.webContents.send('navigate', routePath)
  }
}

// Keep a global reference of the window object, if you don't, the window will
// be closed automatically when the JavaScript object is garbage collected.
let mainWindow;
const mainMenuTemplate = [{
  label: 'File',
  submenu: [{
      label:'Config',
      click() {
        navigate('/config');
      }
    },{
    label:'Quit',
    accelerator: 'Ctrl+Q',
    click() {
      app.quit();
    }
  }]
}, {
  label: 'Edit',
  submenu: [
    { role: 'undo' },
    { role: 'redo' },
    { type: 'separator' },
    { role: 'cut' },
    { role: 'copy' },
    { role: 'paste' },
    { role: 'selectAll' }
  ]
}, {
  label: 'DevTools',
  submenu: [{
    label: 'Toggle Tools',
    click(item, focusedWindow) {
      focusedWindow.toggleDevTools();
    }
  }, {
    role: 'reload'
  }]
}];

process.on('uncaughtException', function(err) {
  console.log('Caught Exception: ', err);
});

function createWindow () {
  session.defaultSession.setPermissionRequestHandler(function(webContents, permission, callback) {
    callback(permission === 'media');
  });

  // Create the browser window.
  mainWindow = new BrowserWindow({
    width: 1024,
    height: 768,
    'minWidth': 1024,
    'minHeight': 768,
    icon: __dirname + '/lib/images/pertech.png',
    webPreferences: {
      nodeIntegration: true,
      contextIsolation: false,
      enableRemoteModule: true
    }
  })

  // and load the index.html of the app.
  mainWindow.loadFile('index.html')

  // Open the DevTools.
  //mainWindow.webContents.openDevTools();

  //Build menu from template
  const mainMenu = Menu.buildFromTemplate(mainMenuTemplate);
  Menu.setApplicationMenu(mainMenu);

  // Emitted when the window is closed.
  mainWindow.on('closed', function () {
    // Dereference the window object, usually you would store windows
    // in an array if your app supports multi windows, this is the time
    // when you should delete the corresponding element.
    mainWindow = null
  })
}

// This method will be called when Electron has finished
// initialization and is ready to create browser windows.
// Some APIs can only be used after this event occurs.
app.on('ready', createWindow)

app.on('window-all-closed', function () {
  app.quit()
})

app.on('activate', function () {
  if (mainWindow === null) {
    createWindow()
  }
})

// In this file you can include the rest of your app's specific main process
// code. You can also put them in separate files and require them here.
