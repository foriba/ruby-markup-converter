=== Ruby Markup Converter ===
Contributors: Foriba
Tags: ruby, japanese, typography, block, converter
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Paste manuscripts written for novel submission sites into WordPress and display supported markup as ruby annotations and bouten.

== Description ==

Ruby Markup Converter lets you publish manuscripts written for online novel submission sites on WordPress using the ruby and bouten (emphasis dots) notation already in your text.

Copy and paste your manuscript from a text file into a Ruby Markup Converter block, or write directly in the block using the same notation. Supported notations are displayed as ruby annotations and bouten when readers view the post, without having to add each annotation again in the editor.

Place a Ruby Markup Converter block wherever markup conversion should apply. You can also enable conversion for the entire post content.

The plugin includes an admin settings screen where you can choose the conversion scope, enable or disable individual markup styles, and choose how bouten should be rendered.

Supported Markup Styles:

* Ruby:
    * ｜BaseText《RubyAnnotation》
    * BaseText《RubyAnnotation》
    * BaseText(RubyAnnotation)
    * [[rb:BaseText > RubyAnnotation]]
    * #BaseText__RubyAnnotation__#
    * {{ruby|BaseText|RubyAnnotation}}

* Bouten (Emphasis):
    * 《《Emphasis》》

== Installation ==

1. Upload the `ruby-markup-converter` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the `Plugins` menu in WordPress.
3. Open `Settings > Ruby Markup Converter`.
4. Choose the conversion scope and markup rules you want to use.
5. Save your settings.

== Usage ==

= Using the Ruby Markup Converter block =

1. Open a post or page in the block editor.
2. Add the `Ruby Markup Converter` block.
3. Paste your manuscript into a Paragraph block inside it, keeping the supported ruby and bouten notation as written. You can also type directly using the same notation.
4. Preview or publish the post to see the notation displayed as ruby annotations and bouten. In the editor, the notation remains as written.

= Converting the entire post content =

If you prefer to write markup directly in the post content without placing it inside Ruby Markup Converter blocks, open `Settings > Ruby Markup Converter` and set the conversion scope to `Apply to Entire Post Content`.

This also includes text inside Ruby Markup Converter blocks and existing `[rubymaco]...[/rubymaco]` shortcodes.

This mode is convenient for posts that are mostly written with ruby or bouten markup, but unintended text may also be converted. Long posts may also take more processing.

== Frequently Asked Questions ==

= How do I use the plugin safely at first? =

Start with the selected-area mode and place a Ruby Markup Converter block around the text you want to convert. This keeps conversion limited to the content you choose.

= Can I apply conversions to the entire post content? =

Yes. You can switch the conversion scope in the settings screen to apply markup conversion to the entire post content. This is convenient, but selected-area conversion is safer when only part of a post uses ruby or bouten markup.

= Can I still use existing shortcodes? =

Yes. Existing `[rubymaco]...[/rubymaco]` shortcodes remain supported. For new content in the block editor, use the Ruby Markup Converter block.

= Does the plugin support bouten? =

Yes. The plugin supports bouten markup using `《《Emphasis》》`, and you can choose both the bouten style and the rendering method.

= Can I enable only specific markup styles? =

Yes. Each supported markup rule can be enabled or disabled individually from the settings screen.

= What happens when I uninstall the plugin? =

Uninstalling the plugin removes its saved settings but does not delete or rewrite your posts or pages. Ruby and bouten notation remains in your text and is displayed as written instead of being converted into annotations or emphasis dots. Any manually written `[rubymaco]...[/rubymaco]` shortcode tags also remain visible.

== Screenshots ==

1. Ruby Markup Converter settings screen
2. Markup rule selection and preview
3. Bouten style and rendering method settings

== Changelog ==

= 1.1.0 =
* Added the Ruby Markup Converter block for converting markup within a specific block area.
* Changed the default bouten rendering method to CSS text-emphasis.
* Updated the minimum required WordPress version to 6.8.

= 1.0.1 =
* Improved internal option management.

= 1.0.0 =
* Initial public release.

= 0.9.1 =

* Addressed WordPress.org review feedback.

= 0.9.0 =

* Initial review submission
* Added support for multiple ruby markup styles
* Added bouten markup support
* Added shortcode-only and full-content conversion modes
* Added admin settings screen with previews
* Added selectable bouten style and rendering method

== Upgrade Notice ==

= 1.1.0 =
* Adds a dedicated block for limiting ruby and bouten markup conversion to a selected block area. Requires WordPress 6.8 or later.

= 1.0.0 =
* Initial public release.

= 0.9.1 =

* Addressed WordPress.org review feedback.

= 0.9.0 =

Initial review submission.
