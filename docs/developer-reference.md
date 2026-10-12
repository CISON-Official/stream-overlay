# Developer reference

## File structure

```
stream/
├── stream-overlay.php                              # Bootstrap: header, constants, activation, init
├── README.md
├── docs/
│   ├── installation.md
│   ├── configuration.md
│   ├── usage.md
│   └── developer-reference.md
├── includes/
│   ├── class-stream-overlay-plugin.php             # Composition root, settings accessor
│   ├── class-stream-overlay-settings.php           # Admin menu + Settings API
│   ├── class-stream-overlay-frontend.php           # Overlay markup + shortcode
│   ├── class-stream-overlay-youtube.php            # YouTube URL normalisation
│   └── class-stream-overlay-zoom.php               # Zoom SDK enqueue + JWT signing
└── assets/
    ├── css/stream-overlay.css
    └── js/
        ├── admin.js                              # Conditional field toggle
        └── zoom.js                               # ZoomMtgEmbedded bootstrap
```

## Constants

Defined in `stream-overlay.php`:

| Constant | Value | Purpose |
| --- | --- | --- |
| `STREAM_OVERLAY_FILE` | `__FILE__` | Absolute path to the main file. |
| `STREAM_OVERLAY_VERSION` | `1.0.0` | Asset cache-busting version. |

Defined in `Stream_Overlay_Plugin`:

| Constant | Value | Purpose |
| --- | --- | --- |
| `OPTION_KEY` | `stream_overlay_settings` | Option row name. |
| `OPTION_GROUP` | `stream_overlay_group` | Settings API group. |
| `MENU_SLUG` | `stream-overlay` | Admin page slug. |
| `CAPABILITY` | `manage_options` | Required capability. |
| `ZOOM_SDK_VERSION` | `1.9.9` | Zoom Web SDK build. |
| `SHORTCODE` | `stream_overlay` | Shortcode tag. |

## Class map

| Class | Responsibility |
| --- | --- |
| `Stream_Overlay_Plugin` | Singleton. Loads dependencies, holds `get_settings()`, `defaults()`, `should_render()`. |
| `Stream_Overlay_Settings` | Registers the admin menu, fields, sanitization, and admin script. |
| `Stream_Overlay_Frontend` | Enqueues assets, renders the floating overlay and the `[stream_overlay]` shortcode. |
| `Stream_Overlay_YouTube` | Static `build_embed_url()` normalising channel IDs and URLs. |
| `Stream_Overlay_Zoom` | Enqueues the SDK stack, signs the JWT, localizes config. |

Access the composition root:

```php
$plugin   = stream_overlay();
$settings = $plugin->get_settings();
```

## Hooks

### Actions (registered internally)

| Hook | Callback |
| --- | --- |
| `admin_menu` | `Stream_Overlay_Settings::register_admin_menu` |
| `admin_init` | `Stream_Overlay_Settings::register_settings` |
| `admin_enqueue_scripts` | `Stream_Overlay_Settings::enqueue_assets` |
| `wp_enqueue_scripts` | `Stream_Overlay_Frontend::enqueue_assets` |
| `wp_footer` | `Stream_Overlay_Frontend::render_overlay` |

### Shortcode

| Tag | Callback |
| --- | --- |
| `[stream_overlay]` | `Stream_Overlay_Frontend::render_shortcode` |

### Script handles

| Handle | Loaded when |
| --- | --- |
| `stream-overlay` (style) | Overlay or shortcode is rendered. |
| `stream-overlay-react`, `-react-dom`, `-redux`, `-redux-thunk`, `-lodash`, `-zoom-sdk` | Zoom source is configured and active. |
| `stream-overlay-zoom-init` | Always with the Zoom SDK (footer). |
| `stream-overlay-admin` | On the settings screen only. |

## Signature generation

`Stream_Overlay_Zoom::generate_signature()` builds a JWT signed with
`hash_hmac( 'sha256', ..., $secret, true )` and base64url-encodes each segment.

Header:

```json
{ "alg": "HS256", "typ": "JWT" }
```

Payload:

```json
{
  "sdkKey": "<SDK Key>",
  "appKey": "<SDK Key>",
  "mn": "<meeting number>",
  "role": 0,
  "iat": 1700000000,
  "exp": 1700007200,
  "tokenExp": 1700007200
}
```

The token lives for `TOKEN_LIFETIME` seconds (`7200`). The SDK Secret is read
from settings and used only within this method; it is never localized to the
browser.

## Localized object

`assets/js/zoom.js` reads the `StreamOverlayZoom` object:

| Property | Description |
| --- | --- |
| `sdkKey` | Public SDK Key. |
| `signature` | Server-generated JWT. |
| `meetingNumber` | Meeting number. |
| `password` | Meeting password. |
| `userName` | Display name. |
| `role` | Join role (`0` / `1`). |
| `rootId` | Zoom App Root element id (`stream-overlay-zoom-root`). |
| `assetPath` | Zoom asset path for `client.init`. |
| `loadingId` | Loading element id (`stream-overlay-zoom-loading`). |

## Extending

### Change the display markup

Filter the rendered inner markup is not currently exposed; override the CSS
instead, or add a small companion plugin that hooks `wp_footer` after the
overlay. To add a filter point, wrap `build_inner()` output:

```php
$inner = apply_filters( 'stream_overlay_inner_html', $inner, $settings );
```

### Add a new source

1. Add the option value to `sanitize_settings()`.
2. Add an option to `field_source_type()`.
3. Add a `build_<source>_inner()` branch in `Stream_Overlay_Frontend::build_inner()`.

### Changing the Zoom SDK version

Update `ZOOM_SDK_VERSION` in `class-stream-overlay-plugin.php`. The asset URLs
are derived from it:

```
https://source.zoom.us/<version>/lib/vendor/react.min.js
https://source.zoom.us/<version>/lib/vendor/react-dom.min.js
https://source.zoom.us/<version>/lib/vendor/redux.min.js
https://source.zoom.us/<version>/lib/vendor/redux-thunk.min.js
https://source.zoom.us/<version>/lib/vendor/lodash.min.js
https://source.zoom.us/<version>/zoom-meeting-embedded-<version>.min.js
```

> **2.x+ note:** Zoom Web SDK 2.x changed the vendor bundle filenames and the
> `client.init()` options (it expects a `webEndpoint`). Adapt `Stream_Overlay_Zoom::enqueue()`
> and `assets/js/zoom.js` accordingly.

## Coding standards

The code follows WordPress PHP coding standards: Yoda conditions, tab
indentation, prefixed globals, `ABSPATH` guards on every file, and escaped
output. Run your linter of choice against the `includes/` and root files.
