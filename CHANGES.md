# Release notes

## 2.0.0 (Build - 2026070900)

- Removed the ATbar dependency and replaced read-aloud with the browser
  Web Speech API.
- Removed the YUI JavaScript, legacy PHP endpoints, Internet Explorer
  workarounds and per-instance colour configuration.
- Added read-aloud controls for play, pause and stop.
- Added voice selection, limited to voices matching the page language.
- Added fallback handling when a selected voice is unavailable or fails
  to start.
- Added link to instructions when no matching voice is available. 
  Instructions are provided for Windows, macOS, iOS, Android, Linux 
  and ChromeOS.
- Added a standalone voice and language diagnostic page.
- Added text-size controls.
- Added Default, Cream, Blue, High contrast and Dark colour themes.
- Applied colour themes to page navigation, drawers, menus, cards, tables,
  blocks, books and quizzes.
- Added Windows forced-colours support.
- Stored text-size, colour and voice preferences in a browser cookie.
- Added the Secure flag to the preferences cookie when HTTPS is used.
- Added translatable read-aloud status messages.
- Added an accessible information panel.
- Self-hosted the Brickfield logo.
- Added a privacy provider and updated the privacy documentation.
- Removed the block_accessibility database table during upgrade.
- Added automated tests and updated continuous integration.
- Removed the Travis CI configuration.
- Fixed PHP 8.3 compatibility issues.
- Raised the minimum requirements to Moodle 4.5 and PHP 8.1.
- Reset the included translations to English.


## 1.39.03 (Build - 2021092201)

- Fixed a potential cross-site scripting issue where a redirect could send the
  user to another site.

## 1.39.02 (Build - 2021080501)

- The page will no longer redirect to the CSS page on login.

## 1.39.01 (Build - 2021071301)

- Introduced a release-code system indicating the lowest supported Moodle
  version.
- Adjusted the ATbar pop-up so it no longer appears over selected text, fixed
  its position to the bottom and made it responsive.
