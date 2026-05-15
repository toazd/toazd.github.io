---

layout: default

---

#### Selah is a cross platform, feature rich, Bible-focused, KJV Bible study app for all platforms.

<center><img src="https://github.com/toazd/toazd.github.io/blob/main/Screenshot_20260515_103629.png?raw=true" width="300"></center>

<ul>
    <li>Open source: 100% transparency and anyone can contribute.</li>
    <li>No distractions, no ads, no analytics, no gated features, no copyrights.</li>
    <li>Customizable colors, fonts, and font size.</li>
    <li>Support for word-level highlighting (with customizeable highlight colors).</li>
    <li>Support for verse-level notes (with both simple and advanced formatting).</li>
    <li>Support for click-able reference links in notes.</li>
    <li>Built-in support for displaying TSK references (Treasury of Scripture Knowledge).</li>
    <li>A simple search that includes advanced and unique features.</li>
    <li>(Optional) Seemless, transparent, account-based online sync (no email required; uses <a href="https://supabase.com/">Supabase</a>).</li>
    <li>Data exporting and importing to a portable JSON format (highlights, notes, verse history, and search history).</li>
</ul>

---

##### If platform auto-detection fails, download links are below

<div id="detection-container" style="text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 16px; margin-bottom: 30px;">
  <h2 id="os-heading">Detecting your platform...</h2>
  <div id="primary-action">
    <!-- This button will be populated by JS -->
    <a id="main-download-btn" href="#" class="button" style="font-size: 1.2em; padding: 10px 25px;">Download</a>
  </div>
  <p id="os-subtext" style="font-size: 0.9em; color: #666;"></p>
</div>

##### If you need assistance with downloading, installing, or running Selah <a href="https://github.com/toazd/selah/issues" target="_blank">open an issue for help</a> (click the "New Issue" button after opening the link).

##### If you don't have or don't want a Github account to open an issue then simply send your concerns to wmcdannell@gmail.com

| Platform | Format | Link |
| :--- | :--- | :--- |
| Windows | EXE / Installer | <a id="link-exe" href="#">Fetching...</a> |
| Windows | ZIP / Portable | <a id="link-zip" href="#">Fetching...</a> |
| MacOS / OS X | DMG | <a id="link-dmg" href="#">Fetching...</a> |
| iOS / iPadOS | IPA | <a id="link-ipa" href="#">Fetching...</a> |
| Android / ChromeOS | APK | <a id="link-apk" href="#">Fetching...</a> |
| Linux (AppImage) | AppImage | <a id="link-appimage" href="#">Fetching...</a> |
| Linux (Flatpak) | Flatpak | <a id="link-flatpak" href="#">Fetching...</a> |
| Arch / Manjaro / CachyOS | ZST | <a id="link-zst" href="#">Fetching...</a> |
| Debian / Ubuntu | DEB | <a id="link-deb" href="#">Fetching...</a> |
| Fedora / OpenSUSE | RPM | <a id="link-rpm" href="#">Fetching...</a> |
| Linux (Generic/Portable) | Tarball | <a id="link-tar" href="#">Fetching...</a> |

---

<script>
async function updateLinks() {
    const repo = "toazd/selah";
    const response = await fetch(`https://api.github.com/repos/${repo}/releases/latest`);
    const data = await response.json();
    const assets = data.assets;

    // Mapping extensions to their HTML IDs
    const fileMap = {
        ".exe": "link-exe",
        ".zip": "link-zip",
        ".dmg": "link-dmg",
        ".ipa": "link-ipa",
        ".apk": "link-apk",
        ".appimage": "link-appimage",
        ".flatpak": "link-flatpak",
        ".zst": "link-zst",
        ".deb": "link-deb",
        ".rpm": "link-rpm",
        ".tar.gz": "link-tar"
    };

    const downloadUrls = {};

    // Match assets to the map
    assets.forEach(asset => {
        const name = asset.name.toLowerCase();
        for (const [ext, id] of Object.entries(fileMap)) {
            if (name.endsWith(ext)) {
                downloadUrls[ext] = asset.browser_download_url;
                const el = document.getElementById(id);
                if (el) {
                    el.href = asset.browser_download_url;
                    //el.innerText = "Download " + ext;
                    el.innerText = "Download ";
                }
            }
        }
    });

    // Handle OS Detection for the Big Button
    const ua = navigator.userAgent;
    const platform = navigator.platform;
    const btn = document.getElementById('main-download-btn');
    const heading = document.getElementById('os-heading');

    let detectedUrl = "#all-platforms";
    let osName = "";

    if (/Android/i.test(ua)) {
        osName = "Android";
        detectedUrl = downloadUrls[".apk"];
    } else if (/iPhone|iPad|iPod/i.test(ua)) {
        osName = "iOS";
        detectedUrl = "YOUR_APP_STORE_LINK"; 
    } else if (/Win/i.test(platform)) {
        osName = "Windows";
        detectedUrl = downloadUrls[".exe"];
    } else if (/Mac/i.test(platform)) {
        osName = "macOS";
        detectedUrl = downloadUrls[".dmg"];
    } else if (/Linux/i.test(platform)) {
        osName = "Linux";
        detectedUrl = downloadUrls[".appimage"]; // Default Linux recommendation
        
        if (/Ubuntu|Debian/i.test(ua)) {
            detectedUrl = downloadUrls[".deb"] || detectedUrl;
        } else if (/Arch/i.test(ua)) {
            detectedUrl = downloadUrls[".zst"] || detectedUrl;
        }
    }

    if (osName && detectedUrl) {
        heading.innerText = "Download for " + osName;
        btn.href = detectedUrl;
    }
}

window.addEventListener('load', updateLinks);
</script>
