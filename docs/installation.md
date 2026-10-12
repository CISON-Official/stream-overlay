# Installation

## Requirements

- WordPress 5.6 or newer
- PHP 7.4 or newer
- HTTPS (required by the Zoom Meeting Web SDK; YouTube also prefers it)

## Steps

### 1. Upload the plugin

**Option A — manual copy**

Copy the plugin folder into your WordPress plugins directory:

```
wp-content/plugins/stream/
```

The main file must be present at:

```
wp-content/plugins/stream/stream-overlay.php
```

**Option B — ZIP upload**

1. Compress the `stream` folder into `stream.zip`.
2. In WordPress go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP, click **Install Now**.

### 2. Activate

Go to **Plugins**, find **Stream Overlay**, and click **Activate**.

On activation the plugin seeds default settings without overwriting any
existing configuration.

### 3. Configure

A new **Stream Overlay** item appears in the admin sidebar. Open it and follow
[Configuration](configuration.md).

## Upgrading

Replace the plugin files with the new version. Settings are stored in the
`stream_overlay_settings` option and are preserved across upgrades.

To change the bundled Zoom Web SDK build, edit the `ZOOM_SDK_VERSION` constant
in `includes/class-stream-overlay-plugin.php` (see
[Developer reference](developer-reference.md#changing-the-zoom-sdk-version)).

## Uninstalling

Deactivate and delete the plugin from the **Plugins** screen. Note that the
plugin does not remove its option row on delete; you can remove the
`stream_overlay_settings` row from `wp_options` manually if desired.
