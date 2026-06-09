const { app, BrowserWindow, ipcMain, Menu, Notification, dialog } = require('electron');
const path = require('path');
const { spawn, exec } = require('child_process');
const net = require('net');
const fs = require('fs');
const https = require('https');
const os = require('os');
const http = require('http');

let mainWindow = null;
let phpProcess = null;
let phpPort = 8085; // Default fallback
let tunnelProcess = null;
let activeTunnelToken = '';

// Helper function to force kill whatever is listening on a port
function killPort(port, cb) {
    console.log(`Ensuring port ${port} is free...`);
    if (process.platform === 'win32') {
        const cmd = `FOR /F "tokens=5" %P IN ('netstat -aon ^| findstr :${port} ^| findstr LISTENING') DO taskkill /F /PID %P`;
        exec(cmd, { windowsHide: true }, () => {
            if (cb) cb();
        });
    } else {
        const cmd = `fuser -k ${port}/tcp || kill -9 $(lsof -t -i:${port})`;
        exec(cmd, () => {
            if (cb) cb();
        });
    }
}

// Start PHP Server in background
function startPhpServer(port, phpBinary) {
    const rootPath = path.join(__dirname, '..');
    const docRoot = path.join(rootPath, 'public');
    const routerScript = path.join(rootPath, 'public', 'index.php');

    const logDir = path.join(rootPath, 'storage', 'logs');
    if (!fs.existsSync(logDir)) {
        fs.mkdirSync(logDir, { recursive: true });
    }
    const logFile = path.join(logDir, 'desktop_php.log');
    const logFd = fs.openSync(logFile, 'a');

    console.log(`Starting PHP server on port ${port} using ${phpBinary}...`);
    fs.writeSync(logFd, `\n=== Desktop app started at ${new Date().toLocaleString()} ===\n`);

    phpProcess = spawn(phpBinary, [
        '-S', `127.0.0.1:${port}`,
        '-t', docRoot,
        routerScript
    ], {
        cwd: rootPath,
        env: process.env, 
        stdio: ['ignore', logFd, logFd],
        windowsHide: true
    });

    phpProcess.on('error', (err) => {
        console.error('Failed to start PHP process:', err);
        fs.writeSync(logFd, `[ERROR] Failed to start PHP process: ${err.message}\n`);
        dialog.showErrorBox(
            'فشل تشغيل خادم PHP / PHP Server Error',
            'لم نتمكن من العثور على خادم PHP المحلي أو تشغيله.\n' +
            'يرجى التأكد من تثبيت XAMPP أو إضافة مسار PHP إلى متغيرات البيئة (PATH) في ويندوز، ثم أعد تشغيل التطبيق.\n\n' +
            `تفاصيل الخطأ: ${err.message}`
        );
    });

    phpProcess.on('close', (code) => {
        console.log(`PHP process exited with code ${code}`);
        fs.writeSync(logFd, `[INFO] PHP process exited with code ${code}\n`);
    });
}

// Get the local cloudflared binary path
function getCloudflaredBinaryPath() {
    const binPath = path.join(__dirname, 'bin');
    if (!fs.existsSync(binPath)) {
        fs.mkdirSync(binPath, { recursive: true });
    }
    const ext = process.platform === 'win32' ? '.exe' : '';
    return path.join(binPath, `cloudflared${ext}`);
}

// Add directory to permanent path
function addBinToSystemPath(binPath) {
    if (process.platform === 'linux') {
        const homeDir = os.homedir();
        const bashrcPath = path.join(homeDir, '.bashrc');
        if (fs.existsSync(bashrcPath)) {
            try {
                const bashrcContent = fs.readFileSync(bashrcPath, 'utf8');
                const lineToAdd = `export PATH="$PATH:${binPath}"`;
                if (!bashrcContent.includes(binPath)) {
                    fs.appendFileSync(bashrcPath, `\n# Gharib POS Cloudflare path\n${lineToAdd}\n`);
                    console.log('Successfully added bin path to .bashrc');
                }
            } catch (err) {
                console.error('Error writing to .bashrc:', err);
            }
        }
    } else if (process.platform === 'win32') {
        exec(`setx PATH "%PATH%;${binPath}"`, { windowsHide: true }, (err) => {
            if (err) console.error('Error setting permanent PATH on Windows:', err);
            else console.log('Successfully set Windows PATH variable.');
        });
    }
}

// Ensure cloudflared is downloaded and ready to run
function ensureCloudflared(cb) {
    const binaryPath = getCloudflaredBinaryPath();
    if (fs.existsSync(binaryPath)) {
        if (process.platform !== 'win32') {
            try {
                fs.chmodSync(binaryPath, 0755);
            } catch (e) {
                console.error('Failed to set executable permissions:', e);
            }
        }
        return cb(null, binaryPath);
    }

    let downloadUrl = '';
    const platform = process.platform;

    if (platform === 'win32') {
        downloadUrl = 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe';
    } else if (platform === 'linux') {
        downloadUrl = 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64';
    } else if (platform === 'darwin') {
        downloadUrl = 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-darwin-amd64';
    } else {
        return cb(new Error(`Unsupported platform: ${platform}`));
    }

    console.log(`Downloading cloudflared binary from: ${downloadUrl}`);
    const tempDest = binaryPath + '.tmp';
    const file = fs.createWriteStream(tempDest);

    const request = (targetUrl) => {
        https.get(targetUrl, (response) => {
            if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
                request(response.headers.location);
                return;
            }
            if (response.statusCode !== 200) {
                fs.unlink(tempDest, () => {});
                cb(new Error(`Failed to download binary. Status code: ${response.statusCode}`));
                return;
            }

            response.pipe(file);
            file.on('finish', () => {
                file.close(() => {
                    try {
                        fs.renameSync(tempDest, binaryPath);
                        if (platform !== 'win32') {
                            fs.chmodSync(binaryPath, 0755);
                        }
                        const binDir = path.dirname(binaryPath);
                        addBinToSystemPath(binDir);
                        cb(null, binaryPath);
                    } catch (e) {
                        cb(e);
                    }
                });
            });
        }).on('error', (err) => {
            fs.unlink(tempDest, () => {});
            cb(err);
        });
    };

    request(downloadUrl);
}

// Start Cloudflare Tunnel
function startTunnel(token, cb) {
    stopTunnel();

    if (!token || token.trim() === '') {
        if (cb) cb(new Error('No token provided.'));
        return;
    }

    activeTunnelToken = token.trim();
    // Kill any orphaned metrics listener on port 2026 to prevent bind failures
    killPort(2026, () => {
        ensureCloudflared((err, binaryPath) => {
            if (err) {
                console.error('Failed to ensure cloudflared binary:', err);
                if (cb) cb(err);
                return;
            }

            const logDir = path.join(__dirname, '..', 'storage', 'logs');
            if (!fs.existsSync(logDir)) {
                fs.mkdirSync(logDir, { recursive: true });
            }
            const logFile = path.join(logDir, 'desktop_tunnel.log');
            const logFd = fs.openSync(logFile, 'a');

            console.log(`Starting Cloudflare Tunnel with token prefix: ${token.substring(0, 10)}...`);
            fs.writeSync(logFd, `\n=== Cloudflare Tunnel started at ${new Date().toLocaleString()} ===\n`);

            tunnelProcess = spawn(binaryPath, [
                'tunnel',
                '--metrics', '127.0.0.1:2026',
                'run',
                '--token', token
            ], {
                cwd: path.dirname(binaryPath),
                env: process.env,
                stdio: ['ignore', logFd, logFd],
                windowsHide: true
            });

        tunnelProcess.on('error', (err) => {
            console.error('Cloudflare Tunnel failed to start:', err);
            fs.writeSync(logFd, `[ERROR] Failed to start tunnel process: ${err.message}\n`);
        });

        tunnelProcess.on('close', (code) => {
            console.log(`Cloudflare Tunnel process exited with code ${code}`);
            fs.writeSync(logFd, `[INFO] Tunnel process exited with code ${code}\n`);
            tunnelProcess = null;
        });

        if (cb) cb(null);
        });
    });
}

// Stop Cloudflare Tunnel
function stopTunnel() {
    if (tunnelProcess) {
        console.log('Stopping Cloudflare Tunnel...');
        tunnelProcess.kill();
        tunnelProcess = null;
    }
}

// Helper to query PHP settings endpoint
function fetchSavedToken(port, cb) {
    http.get(`http://127.0.0.1:${port}/api/settings`, (res) => {
        let data = '';
        res.on('data', (chunk) => { data += chunk; });
        res.on('end', () => {
            try {
                const settings = JSON.parse(data);
                cb(null, settings.cloudflare_tunnel_token || '');
            } catch (e) {
                cb(e);
            }
        });
    }).on('error', (err) => {
        cb(err);
    });
}

// Auto start Cloudflare Tunnel if a token is configured, with retries to wait for PHP server
function autoStartTunnel(port, retries = 5, delay = 1000) {
    fetchSavedToken(port, (err, token) => {
        if (err) {
            if (retries > 0) {
                console.log(`PHP server not ready yet. Retrying to fetch tunnel settings in ${delay}ms... (${retries} retries left)`);
                setTimeout(() => {
                    autoStartTunnel(port, retries - 1, delay);
                }, delay);
            } else {
                console.error('Failed to fetch saved tunnel token after multiple retries:', err.message);
            }
            return;
        }
        if (token) {
            console.log('Auto-starting Cloudflare Tunnel...');
            startTunnel(token);
        } else {
            console.log('No Cloudflare Tunnel Token configured. Skipped auto-start.');
        }
    });
}

// Show OS Native Desktop Notification for new orders
function showNativeNotification() {
    if (Notification.isSupported()) {
        const notification = new Notification({
            title: 'طلب جديد - أسواق الناصرية',
            body: 'لقد وصل طلب جديد من المتجر الإلكتروني!',
            icon: path.join(__dirname, 'icon.png'),
            silent: true // We already play our own pleasant beep inside the app
        });

        notification.show();

        notification.on('click', () => {
            if (mainWindow) {
                if (mainWindow.isMinimized()) mainWindow.restore();
                mainWindow.show();
                mainWindow.focus();
            }
        });
    }
}

// Watch the new_order.flag file for changes and notify the main window
function watchNewOrderFlag() {
    const rootPath = path.join(__dirname, '..');
    const logDir = path.join(rootPath, 'storage', 'logs');
    if (!fs.existsSync(logDir)) {
        fs.mkdirSync(logDir, { recursive: true });
    }
    const flagFile = path.join(logDir, 'new_order.flag');
    
    if (!fs.existsSync(flagFile)) {
        fs.writeFileSync(flagFile, '');
    }

    console.log(`Watching flag file for new orders: ${flagFile}`);
    
    let debounceTimer = null;
    fs.watch(flagFile, (eventType) => {
        if (eventType === 'change') {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (mainWindow) {
                    console.log('Detected new order flag change! Notifying main window...');
                    mainWindow.webContents.send('new-order-received');
                    showNativeNotification();
                }
            }, 150);
        }
    });
}

// PHP Environment Check and Downloader Implementation
function checkPhpEnvironment(cb) {
    if (process.platform !== 'win32') {
        // For non-Windows platforms, check if php is available globally
        exec('php -v', (err) => {
            if (err) {
                cb(false, 'php');
            } else {
                cb(true, 'php');
            }
        });
        return;
    }

    const localPhp = path.join(__dirname, 'bin', 'php', 'php.exe');
    const xamppC = 'C:\\xampp\\php\\php.exe';
    const xamppD = 'D:\\xampp\\php\\php.exe';
    const rawPhpC = 'C:\\php\\php.exe';

    if (fs.existsSync(localPhp)) {
        return cb(true, localPhp);
    }
    if (fs.existsSync(xamppC)) {
        return cb(true, xamppC);
    }
    if (fs.existsSync(xamppD)) {
        return cb(true, xamppD);
    }
    if (fs.existsSync(rawPhpC)) {
        return cb(true, rawPhpC);
    }

    // Try checking if it's in system PATH
    exec('php -v', { windowsHide: true }, (err) => {
        if (!err) {
            return cb(true, 'php');
        }
        cb(false, null);
    });
}

function downloadPhpZip(urls, index, destPath, onProgress, cb) {
    if (index >= urls.length) {
        return cb(new Error('All download URLs failed.'));
    }
    const url = urls[index];
    console.log(`Trying to download PHP from: ${url}`);
    
    const file = fs.createWriteStream(destPath);
    let request = null;
    
    const handleResponse = (response) => {
        if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
            file.close(() => {
                fs.unlink(destPath, () => {
                    // Follow redirect
                    downloadPhpZip([response.headers.location], 0, destPath, onProgress, cb);
                });
            });
            return;
        }
        
        if (response.statusCode !== 200) {
            file.close(() => {
                fs.unlink(destPath, () => {
                    console.log(`Failed to download from ${url}, status: ${response.statusCode}. Trying next...`);
                    downloadPhpZip(urls, index + 1, destPath, onProgress, cb);
                });
            });
            return;
        }
        
        const totalBytes = parseInt(response.headers['content-length'], 10);
        let downloadedBytes = 0;
        let startTime = Date.now();
        
        response.pipe(file);
        
        response.on('data', (chunk) => {
            downloadedBytes += chunk.length;
            const percent = totalBytes ? (downloadedBytes / totalBytes) * 100 : 0;
            const downloadedMb = (downloadedBytes / (1024 * 1024)).toFixed(2);
            const totalMb = totalBytes ? (totalBytes / (1024 * 1024)).toFixed(2) : '0';
            const elapsedSeconds = (Date.now() - startTime) / 1000;
            const speedKbPs = elapsedSeconds > 0 ? ((downloadedBytes / 1024) / elapsedSeconds).toFixed(1) : '0';
            
            onProgress({ percent, downloadedMb, totalMb, speedKbPs });
        });
        
        file.on('finish', () => {
            file.close(() => {
                cb(null);
            });
        });
    };
    
    request = https.get(url, handleResponse);
    
    request.on('error', (err) => {
        file.close(() => {
            fs.unlink(destPath, () => {
                console.log(`Error downloading from ${url}: ${err.message}. Trying next...`);
                downloadPhpZip(urls, index + 1, destPath, onProgress, cb);
            });
        });
    });
}

function extractZip(zipPath, destDir, cb) {
    if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true });
    }
    
    console.log(`Extracting ${zipPath} to ${destDir}...`);
    // Try native tar first (available in Windows 10/11)
    const tarCmd = `tar -xf "${zipPath}" -C "${destDir}"`;
    exec(tarCmd, { windowsHide: true }, (err, stdout, stderr) => {
        if (!err) {
            console.log('Extraction completed via native tar.');
            return cb(null);
        }
        console.warn(`Native tar extraction failed: ${stderr || err.message}. Trying PowerShell Expand-Archive...`);
        
        // Try PowerShell Expand-Archive as fallback
        const psCmd = `powershell -Command "Expand-Archive -Path '${zipPath}' -DestinationPath '${destDir}' -Force"`;
        exec(psCmd, { windowsHide: true }, (err2, stdout2, stderr2) => {
            if (!err2) {
                console.log('Extraction completed via PowerShell.');
                return cb(null);
            }
            cb(new Error(`Failed to extract PHP zip. Tar error: ${err.message}. PowerShell error: ${err2.message}`));
        });
    });
}

function startPhpSetupFlow() {
    const localPhpDir = path.join(__dirname, 'bin', 'php');
    if (!fs.existsSync(localPhpDir)) {
        fs.mkdirSync(localPhpDir, { recursive: true });
    }
    const zipPath = path.join(localPhpDir, 'php.zip');

    const urls = [
        'https://windows.php.net/downloads/releases/archives/php-8.2.12-nts-Win32-vs16-x64.zip',
        'https://windows.php.net/downloads/releases/php-8.2.12-nts-Win32-vs16-x64.zip',
        'https://windows.php.net/downloads/archives/php-8.2.12-nts-Win32-vs16-x64.zip'
    ];

    console.log('Starting PHP setup flow...');

    downloadPhpZip(urls, 0, zipPath, (progress) => {
        if (mainWindow && !mainWindow.isDestroyed()) {
            mainWindow.webContents.send('download-progress', progress);
        }
    }, (err) => {
        if (err) {
            console.error('PHP Download failed:', err);
            if (mainWindow && !mainWindow.isDestroyed()) {
                mainWindow.webContents.send('status-update', `فشل تحميل خادم PHP: ${err.message}`);
            }
            dialog.showErrorBox(
                'فشل تحميل PHP / PHP Download Error',
                `لم نتمكن من تنزيل PHP تلقائياً لتشغيل البرنامج.\nتفاصيل الخطأ: ${err.message}`
            );
            return;
        }

        if (mainWindow && !mainWindow.isDestroyed()) {
            mainWindow.webContents.send('status-update', 'جاري فك ضغط خادم PHP وتثبيته...');
        }

        extractZip(zipPath, localPhpDir, (err2) => {
            // Delete ZIP file
            if (fs.existsSync(zipPath)) {
                try {
                    fs.unlinkSync(zipPath);
                } catch (e) {
                    console.error('Failed to delete zip file:', e);
                }
            }

            if (err2) {
                console.error('PHP Extraction failed:', err2);
                if (mainWindow && !mainWindow.isDestroyed()) {
                    mainWindow.webContents.send('status-update', `فشل فك ضغط خادم PHP: ${err2.message}`);
                }
                dialog.showErrorBox(
                    'فشل تثبيت PHP / PHP Extraction Error',
                    `لم نتمكن من فك ضغط خادم PHP وتثبيته.\nتفاصيل الخطأ: ${err2.message}`
                );
                return;
            }

            if (mainWindow && !mainWindow.isDestroyed()) {
                mainWindow.webContents.send('status-update', 'جاري إنشاء ملف إعدادات PHP...');
            }

            // Create php.ini configuration file
            const iniPath = path.join(localPhpDir, 'php.ini');
            const iniContent = `[PHP]
extension_dir = "ext"
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=sqlite3

max_execution_time = 300
max_input_time = 300
memory_limit = 512M
post_max_size = 100M
upload_max_filesize = 100M
default_charset = "UTF-8"
`;
            try {
                fs.writeFileSync(iniPath, iniContent, 'utf8');
                console.log('Successfully created php.ini');
            } catch (e) {
                console.error('Failed to create php.ini:', e);
            }

            if (mainWindow && !mainWindow.isDestroyed()) {
                mainWindow.webContents.send('status-update', 'اكتمل التثبيت بنجاح! جاري تشغيل النظام...');
            }

            // Small delay to let user see success status before app loads
            setTimeout(() => {
                const phpBinaryPath = path.join(localPhpDir, 'php.exe');
                launchApp(phpBinaryPath);
            }, 1000);
        });
    });
}

function launchApp(phpBinaryPath) {
    killPort(8085, () => {
        phpPort = 8085;
        startPhpServer(phpPort, phpBinaryPath);
        
        setTimeout(() => {
            if (!mainWindow) {
                createWindow(false);
            } else {
                mainWindow.loadURL(`http://127.0.0.1:${phpPort}`);
            }
            
            // Auto-start tunnel 1.5 seconds after launch to ensure PHP API is ready
            setTimeout(() => {
                autoStartTunnel(phpPort);
            }, 1500);
        }, 500);
    });
}

function createWindow(showLoader = false) {
    watchNewOrderFlag();
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 850,
        title: 'نظام نقاط البيع - أسواق الناصرية',
        icon: path.join(__dirname, 'icon.png'),
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            contextIsolation: true,
            nodeIntegration: false
        }
    });

    // Intercept print windows, inject preload, and make them invisible for silent printing
    mainWindow.webContents.setWindowOpenHandler((details) => {
        const isPrintWindow = details.url.includes('/print') || (details.frameName && details.frameName.startsWith('pos-print'));
        if (isPrintWindow) {
            console.log(`Intercepting print window (URL: ${details.url}, Name: ${details.frameName})`);
            return {
                action: 'allow',
                overrideBrowserWindowOptions: {
                    show: false, // Make window completely invisible
                    webPreferences: {
                        preload: path.join(__dirname, 'preload.js'),
                        contextIsolation: true,
                        nodeIntegration: false
                    }
                }
            };
        }
        return { action: 'allow' };
    });

    if (showLoader) {
        mainWindow.loadFile(path.join(__dirname, 'loading_php.html'));
    } else {
        mainWindow.loadURL(`http://127.0.0.1:${phpPort}`);
    }

    mainWindow.webContents.on('did-fail-load', (event, errorCode, errorDescription, validatedURL) => {
        console.error(`Page failed to load: ${errorDescription} (${errorCode})`);
        if (validatedURL.startsWith('http://127.0.0.1')) {
            if (errorCode === -102 || errorCode === -105 || errorCode === -100 || errorCode === -21) {
                setTimeout(() => {
                    if (mainWindow) {
                        console.log('Retrying connection to local PHP server...');
                        mainWindow.loadURL(validatedURL);
                    }
                }, 500);
            }
        }
    });

    mainWindow.on('closed', () => {
        mainWindow = null;
    });

    const template = [
        {
            label: 'خيارات النظام',
            submenu: [
                { label: 'إعادة تحميل الصفحة', role: 'reload' },
                { label: 'ملء الشاشة', role: 'togglefullscreen' },
                { type: 'separator' },
                { label: 'أدوات المطور (DevTools)', role: 'toggleDevTools' },
                { type: 'separator' },
                { label: 'إغلاق البرنامج', role: 'quit' }
            ]
        }
    ];
    const menu = Menu.buildFromTemplate(template);
    Menu.setApplicationMenu(menu);
}

// Initialize Electron App
app.whenReady().then(() => {
    // Add local bin to PATH for the current process
    const localBin = path.join(__dirname, 'bin');
    if (!fs.existsSync(localBin)) {
        fs.mkdirSync(localBin, { recursive: true });
    }
    process.env.PATH = localBin + path.delimiter + process.env.PATH;

    checkPhpEnvironment((hasPhp, phpBinaryPath) => {
        if (hasPhp) {
            console.log(`PHP found at ${phpBinaryPath}. Starting application...`);
            launchApp(phpBinaryPath);
        } else {
            console.log('PHP not found in environment. Showing downloader...');
            createWindow(true);
            startPhpSetupFlow();
        }
    });

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            checkPhpEnvironment((hasPhp, phpBinaryPath) => {
                createWindow(!hasPhp);
                if (!hasPhp) {
                    startPhpSetupFlow();
                } else {
                    setTimeout(() => {
                        autoStartTunnel(phpPort);
                    }, 1500);
                }
            });
        }
    });
});

// Clean up processes on exit
app.on('window-all-closed', () => {
    stopTunnel();
    if (phpProcess) {
        phpProcess.kill();
    }
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('quit', () => {
    stopTunnel();
    if (phpProcess) {
        phpProcess.kill();
    }
});

// IPC Handler to trigger native silent printing
ipcMain.on('print-silent', (event, printerName, isLabel = false) => {
    const senderContents = event.sender;

    const doPrint = () => {
        if (senderContents.isDestroyed()) return;

        // Label: 50x30mm in microns (exact match to @page { size: 50mm 30mm })
        // Receipt: 80mm wide × 297mm tall (longest common thermal roll, CUPS-accepted)
        //          Using 'Custom' with explicit dimensions avoids 'A4' mismatch on thermal printers
        const printOptions = {
            silent: true,
            printBackground: true,
            margins: { marginType: 'none' },
            pageSize: isLabel
                ? { width: 50000, height: 30000 }          // 50×30mm label
                : { width: 80000, height: 297000 }          // 80mm thermal roll
        };

        if (printerName) {
            printOptions.deviceName = printerName;
        }

        senderContents.print(printOptions, (success, errorType) => {
            if (!senderContents.isDestroyed()) {
                senderContents.send('print-finished', { success, error: errorType || null });
            }
            if (!success) {
                console.error('Silent print failed:', errorType);
                if (mainWindow && !mainWindow.isDestroyed()) {
                    mainWindow.webContents.send('print-status', {
                        success: false,
                        error: errorType,
                        message: 'فشلت عملية الطباعة: ' + errorType
                    });
                }
            } else {
                console.log('Silent print succeeded!');
                if (mainWindow && !mainWindow.isDestroyed()) {
                    mainWindow.webContents.send('print-status', {
                        success: true,
                        message: 'تمت عملية الطباعة صامتاً بنجاح ✓'
                    });
                }
            }
        });
    };

    // If page is still loading, wait for it — otherwise print immediately
    if (senderContents.isLoading()) {
        senderContents.once('did-finish-load', () => {
            setTimeout(doPrint, 300);
        });
    } else {
        doPrint();
    }
});

// IPC Handler to quit app
ipcMain.on('quit-app', () => {
    app.quit();
});

// IPC Handle to get all available system printers
ipcMain.handle('get-printers', async () => {
    if (mainWindow) {
        return await mainWindow.webContents.getPrintersAsync();
    }
    return [];
});

// IPC Handle to check status of Cloudflare Tunnel via metrics endpoint
ipcMain.handle('check-tunnel-status', async () => {
    return new Promise((resolve) => {
        const req = http.get('http://127.0.0.1:2026/ready', (res) => {
            if (res.statusCode === 200) {
                resolve({ online: true, metricsPort: 2026 });
            } else {
                resolve({ online: false });
            }
        });
        req.on('error', () => {
            resolve({ online: false });
        });
        req.setTimeout(800, () => {
            req.destroy();
            resolve({ online: false });
        });
    });
});

// IPC Handle to start/restart or stop Cloudflare Tunnel
ipcMain.handle('control-tunnel', async (event, action, token) => {
    if (action === 'restart' || action === 'start') {
        return new Promise((resolve, reject) => {
            startTunnel(token, (err) => {
                if (err) reject(err);
                else resolve({ success: true });
            });
        });
    } else if (action === 'stop') {
        stopTunnel();
        return { success: true };
    }
    return { error: 'Unknown action' };
});
