# Configuration

All settings live under **Stream Overlay** in the admin sidebar and are stored
in the `stream_overlay_settings` option.

## Settings reference

| Setting | Key | Applies to | Notes |
| --- | --- | --- | --- |
| Display Mode | `display_mode` | Both | `floating` or `shortcode`. |
| Stream Source Type | `source_type` | Both | `youtube` or `zoom`. |
| YouTube Channel ID or Video URL | `youtube_input` | YouTube | Channel ID or a URL. |
| Zoom SDK Key | `zoom_sdk_key` | Zoom | Public key, safe to expose. |
| Zoom SDK Secret | `zoom_sdk_secret` | Zoom | Masked, server-side only. |
| Meeting Number | `zoom_meeting_number` | Zoom | Digits only. |
| Meeting Password | `zoom_meeting_password` | Zoom | Optional depending on meeting. |
| Display Name | `zoom_user_name` | Zoom | Name shown in the meeting. |
| Join Role | `zoom_role` | Zoom | `0` participant, `1` host. |

Fields for the inactive source are hidden by JavaScript but are still submitted;
their values are retained.

## Display Mode

### Floating overlay

Renders a fixed panel (380×240) in the bottom-right of every public page with a
`✕` close button. Nothing needs to be added to your content.

### Shortcode only

Nothing is injected automatically. Place `[stream_overlay]` where you want the
video. See [Usage](usage.md).

## YouTube

The **Channel ID or Video URL** field accepts any of the following:

| Input | Example | Result |
| --- | --- | --- |
| Channel ID | `UC_x5XG1OV2P6uZZ5FSM9Ttw` | Channel live-stream embed |
| Watch URL | `https://www.youtube.com/watch?v=VIDEOID` | Video embed |
| Short URL | `https://youtu.be/VIDEOID` | Video embed |
| Live URL | `https://www.youtube.com/live/VIDEOID` | Video embed |
| Embed URL | `https://www.youtube.com/embed/VIDEOID` | Used as-is |
| Channel URL | `https://www.youtube.com/channel/CHANNELID` | Channel live-stream embed |

Every embed is built with `autoplay=1&mute=1&playsinline=1&rel=0`. Autoplay
requires the muted attribute, which the plugin always sets.

## Zoom

### Getting credentials

1. Sign in to the [Zoom App Marketplace](https://marketplace.zoom.us/).
2. Create a **General App** (or a Meeting SDK app).
3. Enable the **Meeting SDK** feature.
4. Copy the **SDK Key** and **SDK Secret**.
5. Add your site domain under the app's allowed domains.

### Role

| Role | Value | Use |
| --- | --- | --- |
| Participant | `0` | View the meeting. |
| Host | `1` | Start/host the meeting (requires a licensed account). |

> **Webinar note:** Webinar meetings require additional join parameters. This
> plugin targets standard meetings.

### How joining works

On the front end the plugin:

1. Enqueues React, ReactDOM, Redux, Redux-Thunk, Lodash, and the Zoom Meeting
   Embedded SDK from `source.zoom.us`.
2. Generates an HS256 JWT signature server-side (see
   [Developer reference](developer-reference.md#signature-generation)).
3. Passes the signature, SDK Key, meeting number, password, and display name to
   the browser through `wp_localize_script`.
4. Initialises `ZoomMtgEmbedded.createClient()` and joins the meeting inside the
   overlay or shortcode container.

## Validation

- Source and display mode are constrained to their allowed values.
- The Zoom meeting number is reduced to digits.
- An empty **SDK Secret** submission preserves the stored secret (so re-saving
  the form does not wipe it).
- An empty **Display Name** falls back to `Guest`.
