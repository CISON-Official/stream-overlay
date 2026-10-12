# Usage

## Floating overlay

With **Display Mode → Floating overlay**, the video appears automatically in the
bottom-right corner of every public page. Visitors close it with the `✕`
button, which hides the container with vanilla JavaScript.

When the overlay is closed it stays closed for that page load only; a refresh
(or navigation) shows it again. To keep it closed site-wide you would need to
add session/local storage logic in `assets/js/zoom.js` or a theme script.

## Shortcode

With **Display Mode → Shortcode only**, place the shortcode where you want the
video:

```
[stream_overlay]
```

### Attributes

| Attribute | Values | Default | Description |
| --- | --- | --- | --- |
| `source` | `youtube`, `zoom` | configured source | Override the source for this instance. |
| `title` | any text | `Live stream` | Accessible label for the wrapper. |

### Examples

Force a YouTube stream on a specific page:

```
[stream_overlay source="youtube"]
```

Force a Zoom meeting with a custom label:

```
[stream_overlay source="zoom" title="Town Hall"]
```

### Where it can be used

- Post and page content
- Classic text widgets (those that run shortcodes)
- Page builders that parse shortcodes
- Theme templates: `echo do_shortcode( '[stream_overlay]' );`

The container is responsive: it is 16:9 and fills the width of its parent. Wrap
it in a narrow column to control its size.

## Styling

Two root classes are used:

| Class | View |
| --- | --- |
| `#stream-overlay` | Floating overlay |
| `.stream-overlay-embed` | Shortcode/inline container |

Override the bundled stylesheet from your theme:

```css
#stream-overlay {
	width: 480px;
	height: 300px;
}

.stream-overlay-embed {
	max-width: 960px;
	margin: 0 auto;
}
```

## Caching and page builders

The plugin enqueues the stylesheet and (for Zoom) the SDK when it detects the
shortcode in the main post content during `wp_enqueue_scripts`. When the
shortcode is injected late — inside a widget, page builder, or template — the
shortcode callback enqueues its own assets as a fallback, so the stream still
works.

If your site caches full pages, clear the cache after changing the display mode
or source.
