# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry describes changes verified by comparing its tag with the immediately preceding published release. Later changes on `master` are excluded.

## 1.0.6 - 2026-10-23

### Changed

- Documented installation requirements in the README: the activity requires `local_video_bridge` version `2026100614` or later, which must be installed separately in `local/video_bridge` before installing or upgrading Video Tracker Max. The README also describes Video Bridge's Moodle 4.5-or-later requirement and its current dependency on `local_ai_bridge`.
- Rebuilt and minified the distributed AMD player module `amd/build/player.min.js`. The tag comparison does not include modifications to the player source code or verify any new player behavior.
- Added Git-reference-based concurrency settings to the existing GitHub release workflow, with `cancel-in-progress: false` and `queue: max`.
- Updated the plugin release identifier from `1.0.5` to `1.0.6` and its build number from `2026101501` to `2026102300`.

No new user-facing Video Tracker Max functionality was identified in this tag comparison.

[Compare 1.0.5...1.0.6](https://github.com/EduardoKrausME/moodle-mod_videotrackermax/compare/1.0.5...1.0.6)

## 1.0.5 - 2026-10-15

### Changed

- Added the plugin icon at `pix/icon.png` and reorganized the SVG logo asset as `pix/monologo.svg`.
- Updated the plugin release identifier from `1.0.4` to `1.0.5` and its build number from `2026101500` to `2026101501`.

No functional changes to the Video Tracker Max activity were identified in this tag comparison.

[Compare 1.0.4...1.0.5](https://github.com/EduardoKrausME/moodle-mod_videotrackermax/compare/1.0.4...1.0.5)
