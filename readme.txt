=== Ruby Markup Converter ===
Contributors: Foriba
Tags: ruby, japanese, typography, block, converter
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Converts ruby and emphasis mark notation from Japanese novel-sharing sites for WordPress, without modifying the original text.

== Description ==

= Want to Publish Your Manuscripts Written for Japanese Novel-Sharing Sites on WordPress, Too? =

Ruby Markup Converter is a plugin that automatically converts ruby (furigana) and emphasis mark (bouten) notation used on Japanese novel-sharing sites and elsewhere into a format that displays correctly in WordPress, without modifying the original text.

Simply copy and paste your manuscript from a text file. There's no need to add ruby annotations or emphasis marks one by one. Of course, you can also type the same notation directly into the WordPress editor.

---

= Key Features =
- **Use your original manuscripts as they are:** Supported ruby and bouten notation is converted automatically, so there's no need to rewrite your text.
- **Easy to use:** Simply paste your manuscript into the Ruby Markup Converter block.
- **Choose the conversion scope:** Convert notation within a dedicated block or throughout the entire post content.
- **Flexible settings:** Enable or disable individual markup rules and choose how bouten marks are displayed.

---

= Support for Languages Other Than Japanese =

Although Ruby Markup Converter is designed with Japanese typography and novel-writing conventions in mind, it is not limited to Japanese. Text in other languages can also be converted as long as it follows the supported markup rules.

---

= Supported Markup Styles =

* Ruby:

  * `｜BaseText《RubyAnnotation》`
  * `BaseText《RubyAnnotation》`
  * `BaseText(RubyAnnotation)`
  * `[[rb:BaseText > RubyAnnotation]]`
  * `#BaseText__RubyAnnotation__#`
  * `{{ruby|BaseText|RubyAnnotation}}`

* Bouten (Emphasis):

  * `《《Emphasis》》`

== Installation ==

1. Install the plugin using the built-in WordPress plugin installer, or manually upload the extracted files to the `wp-content/plugins/` directory.
2. Activate the plugin through the `Plugins` menu in WordPress.
3. Go to `Settings > Ruby Markup Converter`.
4. Select the conversion scope and markup rules you want to use.
5. Save your settings.

== Usage ==

Ruby Markup Converter supports three ways to convert ruby and bouten notation:

= Ruby Markup Converter Block =

Add the Ruby Markup Converter block to a post or page, then paste or type your text inside it. Supported notation is automatically converted when the content is displayed.

= Shortcode =

Wrap your text in `[rubymaco]...[/rubymaco]` to convert supported notation within that section. This method is also available in the Classic Editor.

= Entire Post Content =

Enable Apply to Entire Post Content in the plugin settings to automatically convert supported notation throughout the post content.

== Documentation ==

For detailed instructions, examples, configuration options, and troubleshooting, see the [Ruby Markup Converter User Guide](https://github.com/foriba/ruby-markup-converter/blob/main/docs/en-US/user-guide.md).

== Frequently Asked Questions ==

= Can I choose which markup styles to convert? =

Yes. You can enable or disable each supported ruby and bouten markup rule individually in the plugin settings. You can also choose the bouten style and rendering method.

= What happens when I uninstall the plugin? =

Uninstalling the plugin removes its saved settings but does not delete or modify your posts or pages. Ruby and bouten notation remains in the original text and is no longer converted. Any manually written `[rubymaco]...[/rubymaco]` shortcode tags also remain visible.

== Screenshots ==

1. Plugin settings screen.
2. Adding the Ruby Markup Converter block.
3. Example of entering or pasting text into the block. Sample text: Wagahai wa Neko de Aru (I Am a Cat) by Natsume Soseki (public domain). Source: Aozora Bunko.
4. Preview showing the converted ruby and bouten notation.

== Changelog ==

= 1.3.0 =
- Added an option to convert the full text of posts in selected categories.
- Added per-post and per-page conversion settings in the block editor.
- Fixed some kanji, such as 𠮟 and 﨑, not being recognized as base text in ruby notation without an explicit base text.
- Fixed shortcodes nested inside [rubymaco]...[/rubymaco] not being processed.
- Fixed settings failing to save after the settings screen had been left open for a long time.
- Improved conversion performance, especially for long posts.
- Fixed typos and improved overall stability.

= 1.2.0 =
- Redesigned the settings screen for easier navigation and configuration.
- Improved feedback when saving settings or making changes.
- Moved the custom bouten rendering option to Compatibility Settings.
- Improved internal reliability while preserving existing settings and conversion behavior.
- Corrected the documentation to remove an inaccurate statement about built-in ruby support in WordPress.

= 1.1.0 =

- Added the Ruby Markup Converter block for easier use in the block editor.
- Changed the default bouten rendering method to CSS text-emphasis.
- Prevented markup conversion inside HTML tags and attributes to avoid potential display issues.
- Fixed bouten markup immediately following kanji being incorrectly interpreted as ruby markup.
- Fixed incorrect display of ampersands when using the custom bouten renderer.
- Fixed an issue where disabling all markup rules caused the settings to revert to their defaults.
- Updated the minimum required WordPress version to 6.8.

= 1.0.1 =

- Improved internal settings management.

= 1.0.0 =

- Initial public release.

== Upgrade Notice ==

= 1.3.0 =

Adds category and per-post conversion settings. Ruby notation without an explicit base text now recognizes more kanji, and shortcodes nested inside [rubymaco] are now processed, so some existing content may display differently.

= 1.2.0 =

The settings screen has been redesigned. Existing settings are preserved, and no action is required after updating. Custom bouten rendering is now under Compatibility Settings.

= 1.1.0 =

Requires WordPress 6.8 or later. Adds a dedicated conversion block and fixes several markup conversion issues.
