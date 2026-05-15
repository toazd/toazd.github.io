---
layout: default
---
<center>
    <p style="color: red !important;">
    Verily, verily, I say unto thee, Except a man be born again, he cannot see the kingdom of God.
    </p>
</center>

#### Selah is a touch friendly, feature rich, Bible-focused, KJV Bible study app.

#### <a href="https://toazd.github.io/bible">Try the web version by clicking/tapping here</a>

<center><img src="https://github.com/toazd/toazd.github.io/blob/main/Screenshot_20260515_103629.png?raw=true" width="300"></center>

<ul>
    <li>Open source: 100% transparency and anyone can contribute.</li>
    <li>No distractions, no ads, no analytics, no gated features, no copyrights.</li>
    <li>Customizable colors, fonts, and font size.</li>
    <li>Word-level highlighting (customizeable highlight colors).</li>
    <li>Verse-level notes (simple and advanced formatting).</li>
    <li>Built-in support for TSK references (Treasury of Scripture Knowledge).</li>
    <li>A simple search that also includes advanced and unique features.</li>
    <li>Optional, seemless, transparent, account-based online sync (no email required; uses <a href="https://supabase.com/">Supabase</a>).</li>
    <li>Local user-data exporting and importing to a portable format (JSON).</li>
</ul>
---
<center><h3>Download Selah</h3></center>

#### Virus total reports:
- <a href="https://www.virustotal.com/gui/file/71f888c183f9bd67a5ec4c0d11e141cac48ebc6ef5d394b4a686313329219482" target="_blank">Windows portable</a>
- <a href="https://www.virustotal.com/gui/file/ef2ef330d3dec842ee6216dd02a638aa6407be03c22a0d5f788ba73cde813918" target="_blank">Linux portable</a>
- <a href="https://www.virustotal.com/gui/file/a3d72189bc8b5cfd1448230abf6f53fdbde1816874223bc40ab6bbace352f97f?nocache=1">Android APK</a>

##### If you need assistance with downloading, installing, or running Selah please <a href="https://github.com/toazd/selah/issues" target="_blank">open an issue for help</a> (click the green "New Issue" button after opening the link).
##### Some packages will require manual installation. See below for basic installation instructions.

##### Selah is not currently available on any app store.

| Platform | Format | Link |
| :--- | :--- | :--- |
| Windows | EXE / Installer | <a id="link-exe" href="#">Fetching...</a> |
| Windows | ZIP / Portable | <a id="link-zip" href="#">Fetching...</a> |
| MacOS / OS X | DMG | <a id="link-dmg" href="#">Fetching...</a> |
| iOS / iPadOS | IPA | <a id="link-ipa" href="#">Fetching...</a> |
| Android / ChromeOS | APK | <a id="link-apk" href="#">Fetching...</a> |
| Arch / Manjaro / CachyOS | ZST | <a id="link-zst" href="#">Fetching...</a> |
| Debian / Ubuntu | DEB | <a id="link-deb" href="#">Fetching...</a> |
| Fedora / OpenSUSE | RPM | <a id="link-rpm" href="#">Fetching...</a> |
| Linux | AppImage | <a id="link-appimage" href="#">Fetching...</a> |
| Linux | Flatpak | <a id="link-flatpak" href="#">Fetching...</a> |
| Linux | Tarball / Portable | <a id="link-tar" href="#">Fetching...</a> |

Basic installation help:
- Windows portable
  - Unzip anywhere and run selah.exe
- Linux Portable
  - Unzip anywhere and run selah
- MacOS
  - Unknown. Testers needed.
- iOS
  - Sideloadly
- Linux (AppImage)
  - Make the downloaded file executible with a file manager or `chmod +x ./Selah-X.X.X.AppImage` and then run it
- Linux (Flatpak)
  - `flatpak install ./selah-X.X.X.flatpak`
  - Press `y` to install any needed dependencies
  - Press `y` to give necessary application permissions (access to XDG configured config path, typically `~/.config`, to store user data and access to dbus and network for connectivity check and sync features if enabled)
- Arch / Manjaro / CachyOS
  - `sudo pacman -U /.selah-X.X.X.zst`
- Debian / Ubuntu / MX Linux
  - `sudo dpkg -i ./selah_X.X.X.deb`
- Fedora / OpenSUSE / RPM
  - `sudo rpm -ivh ./selah-X.X.X.rpm`

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
}

window.addEventListener('load', updateLinks);
</script>
