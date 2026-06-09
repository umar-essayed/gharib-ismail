const { contextBridge, ipcRenderer, webFrame } = require('electron');

contextBridge.exposeInMainWorld('electronAPI', {
    printSilent: (printerName, isLabel = false) => ipcRenderer.send('print-silent', printerName, isLabel),
    printUrl: (url) => ipcRenderer.send('print-url', url), // NEW: Direct URL print trigger
    quitApp: () => ipcRenderer.send('quit-app'),
    getPrinters: () => ipcRenderer.invoke('get-printers'),
    checkTunnelStatus: () => ipcRenderer.invoke('check-tunnel-status'),
    controlTunnel: (action, token) => ipcRenderer.invoke('control-tunnel', action, token),
    onPrintStatus: (callback) => ipcRenderer.on('print-status', (event, data) => callback(data)),
    onPrintFinished: (callback) => ipcRenderer.once('print-finished', (event, data) => callback(data)),
    onNewOrderReceived: (callback) => ipcRenderer.on('new-order-received', () => callback()),
    onDownloadProgress: (callback) => ipcRenderer.on('download-progress', (event, data) => callback(data)),
    onStatusUpdate: (callback) => ipcRenderer.on('status-update', (event, text) => callback(text))
});

// Override the default window.print in the main world context to bypass print dialogs
webFrame.executeJavaScript(`
    window.print = () => {
        if (window.electronAPI && typeof window.electronAPI.printSilent === 'function') {
            window.electronAPI.printSilent();
        }
    };
`);
