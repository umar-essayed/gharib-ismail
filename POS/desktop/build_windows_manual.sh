#!/bin/bash
set -e

echo "=== GharibPOS Windows Manual Packager ==="

# Define paths
DESKTOP_DIR="$(cd "$(dirname "$0")" && pwd)"
DIST_DIR="$DESKTOP_DIR/dist"
APP_OUT_DIR="$DIST_DIR/GharibPOS-win32-x64"
CACHE_ZIP="/home/omar/.cache/electron/electron-v31.7.7-win32-x64.zip"

if [ ! -f "$CACHE_ZIP" ]; then
    # Fallback to look inside the subdirectories of cache
    CACHE_ZIP=$(find /home/omar/.cache/electron/ -name "electron-v31.7.7-win32-x64.zip" | head -n 1)
fi

if [ -z "$CACHE_ZIP" ] || [ ! -f "$CACHE_ZIP" ]; then
    echo "Error: Cached Electron win32-x64 zip file not found in ~/.cache/electron/"
    exit 1
fi

echo "Using cached Electron zip: $CACHE_ZIP"

# Clean previous dist
echo "Cleaning output directory..."
rm -rf "$APP_OUT_DIR"
rm -f "$DIST_DIR/GharibPOS-win32-x64.zip"
mkdir -p "$APP_OUT_DIR"

# Extract Electron
echo "Extracting Electron pre-built binary..."
unzip -q "$CACHE_ZIP" -d "$APP_OUT_DIR"

# Rename executable
echo "Renaming executable to GharibPOS.exe..."
mv "$APP_OUT_DIR/electron.exe" "$APP_OUT_DIR/GharibPOS.exe"

# Generate logo icons and apply to executable
echo "Generating and applying custom icon..."
node "$DESKTOP_DIR/apply_icon.js"

# Copy App files (including generated icon.png)
echo "Packaging application files..."
APP_DIR="$APP_OUT_DIR/resources/app"
mkdir -p "$APP_DIR"
cp "$DESKTOP_DIR/main.js" "$APP_DIR/"
cp "$DESKTOP_DIR/preload.js" "$APP_DIR/"
cp "$DESKTOP_DIR/package.json" "$APP_DIR/"
cp "$DESKTOP_DIR/loading_php.html" "$APP_DIR/"
if [ -f "$DESKTOP_DIR/icon.png" ]; then
    cp "$DESKTOP_DIR/icon.png" "$APP_DIR/"
fi

# Copy PHP Backend Application files
echo "Packaging PHP backend application..."
RESOURCES_DIR="$APP_OUT_DIR/resources"
mkdir -p "$RESOURCES_DIR"
cp -r "$DESKTOP_DIR/../app" "$RESOURCES_DIR/"
cp -r "$DESKTOP_DIR/../config" "$RESOURCES_DIR/"
cp -r "$DESKTOP_DIR/../database" "$RESOURCES_DIR/"
cp -r "$DESKTOP_DIR/../db" "$RESOURCES_DIR/"
cp -r "$DESKTOP_DIR/../public" "$RESOURCES_DIR/"
cp -r "$DESKTOP_DIR/../routes" "$RESOURCES_DIR/"
cp -r "$DESKTOP_DIR/../storage" "$RESOURCES_DIR/"
cp "$DESKTOP_DIR/../bootstrap.php" "$RESOURCES_DIR/"

# Clean up large backup zip files from build to save space
rm -f "$RESOURCES_DIR/storage/backups"/*.zip


# Zip the package
echo "Creating zip archive..."
cd "$DIST_DIR"
zip -r -q "GharibPOS-win32-x64.zip" "GharibPOS-win32-x64"

echo "=== Build Completed Successfully! ==="
echo "Output Archive: $DIST_DIR/GharibPOS-win32-x64.zip"
