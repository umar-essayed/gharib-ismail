const fs = require('fs');
const path = require('path');
const { Jimp } = require('jimp');
const pngToIco = require('png-to-ico').default;
const ResEdit = require('resedit');

const logoJpegPath = path.join(__dirname, '..', '..', 'logo.jpeg');
const targetPngPath = path.join(__dirname, 'icon.png');
const tempIcoPath = path.join(__dirname, 'icon.ico');
const targetExePath = path.join(__dirname, 'dist', 'GharibPOS-win32-x64', 'GharibPOS.exe');

async function main() {
    try {
        console.log("Reading logo.jpeg...");
        const image = await Jimp.read(logoJpegPath);
        
        console.log("Resizing and converting logo to PNG...");
        // Resize to 256x256 for high quality icon
        image.resize({ w: 256, h: 256 });
        await image.write(targetPngPath);
        console.log("Created icon.png successfully.");
        
        console.log("Converting PNG to ICO...");
        const icoBuffer = await pngToIco(targetPngPath);
        fs.writeFileSync(tempIcoPath, icoBuffer);
        console.log("Created icon.ico successfully.");

        if (fs.existsSync(targetExePath)) {
            console.log("Modifying GharibPOS.exe PE resources with new icon...");
            const data = fs.readFileSync(targetExePath);
            const exe = ResEdit.NtExecutable.from(data);
            const res = ResEdit.NtExecutableResource.from(exe);

            const iconFile = ResEdit.Data.IconFile.from(fs.readFileSync(tempIcoPath));

            ResEdit.Resource.IconGroupEntry.replaceIconsForResource(
                res.entries, 
                1, 
                1033, // US English
                iconFile.icons.map(item => item.data)
            );

            res.outputResource(exe);
            fs.writeFileSync(targetExePath, Buffer.from(exe.generate()));
            console.log("GharibPOS.exe icon replaced successfully!");
        } else {
            console.log("Warning: GharibPOS.exe not found at " + targetExePath);
        }

        // Clean up temp files
        if (fs.existsSync(tempIcoPath)) fs.unlinkSync(tempIcoPath);
        
        console.log("Icon setup completed successfully!");
    } catch (err) {
        console.error("Error modifying executable icon:", err);
    }
}

main();
