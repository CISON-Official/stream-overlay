# Stream Overlay

A WordPress plugin that shows a floating video overlay in the bottom-right corner of every public page. Paste **one URL** — the plugin detects whether it is a YouTube or Zoom link and renders the correct embed.

---

## Requirements

- WordPress 5.6+
- PHP 7.4+
- HTTPS (required for camera/microphone and secure embeds)

---

## Installation

1. Copy the `stream` folder into `wp-content/plugins/` (or upload a ZIP in **Plugins → Add New → Upload Plugin**).
2. Activate **Stream Overlay**.
3. Open **Stream Overlay** in the admin sidebar and paste your URL.
4. Save.

---

## Configuration

A single field, **Stream or Meeting URL**, accepts YouTube or Zoom links.

Detection is automatic; there is nothing else to configure.

---

## How the embeds work

### YouTube
The video ID is extracted and embedded as:
```
https://www.youtube.com/embed/VIDEOID?autoplay=1&mute=1&playsinline=1&rel=0
```
Channel links use `live_stream` endpoint when a channel ID is available; otherwise the link is embedded as-is.

### Zoom
The meeting URL redirects to the canonical Zoom Web Client:
```
https://app.zoom.us/wc/{meetingId}/join?pwd={passcode}
```
The plugin renders a lightweight "Join meeting" poster and only creates the iframe after the visitor clicks. An "Open in Zoom ↗" link is always shown as fallback.

---

## Developer filters

| Filter | Purpose |
| --- | --- |
| `stream_overlay_zoom_web_client_url` | Override the Zoom Web Client iframe URL. |
| `stream_overlay_zoom_sdk_key` | Supply a Meeting SDK Key to switch to the SDK path. |
| `stream_overlay_zoom_signature` | Supply a server-generated JWT signature. |

When both an SDK Key and a signature are supplied, the plugin mounts the Zoom Meeting SDK instead of the Web Client.

---

## Uninstall

Deleting the plugin removes the stored option via `register_uninstall_hook()`.

---

## License

GPL-2.0-or-later.
