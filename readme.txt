=== Ruby Markup Converter ===
Contributors: Foriba
Tags: ruby, japanese, typography, shortcode, converter
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically convert plain-text ruby notations into display-ready HTML.

== Description ==

Ruby Markup Converter is designed for authors, editors, and archivists who already have content written in common ruby and bouten notations.

If you have a collection of text files or manuscripts written in popular markup styles used by online novel submission sites, this plugin lets you import them into WordPress without any manual rewriting. It automatically detects and converts those notations into valid HTML <ruby> tags on the fly.

While modern WordPress (6.3+) offers a built-in UI for adding ruby annotations, it is designed for word-by-word manual input. Ruby Markup Converter solves a different problem: it bridges the gap between your existing plain-text workflow and the web, ensuring that your established writing habits and archived assets are perfectly preserved in a browser-ready format.

Although primarily designed for Japanese typography, the plugin can convert any text that follows the supported notation formats.

You can use shortcode-only conversion for safer operation, or enable conversion for the entire post content. The plugin also includes an admin settings screen where you can choose which markup styles to enable and how bouten should be rendered.

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

Whether you are migrating existing text files or looking for a way to write ruby annotations without breaking your typing flow, Ruby Markup Converter provides a lightweight and robust solution.

== Installation ==

1. Upload the `ruby-markup-converter` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the `Plugins` menu in WordPress.
3. Open `Settings > Ruby Markup Converter`.
4. Choose the conversion scope and markup rules you want to use.
5. Save your settings.

== Frequently Asked Questions ==

= How do I use the plugin safely at first? =

Start with the shortcode-only mode. In this mode, markup is converted only inside `[rubymaco]...[/rubymaco]` blocks.

= Can I apply conversions to the entire post content? =

Yes. You can switch the conversion scope in the settings screen to apply markup conversion to the entire post content.

= Does the plugin support bouten? =

Yes. The plugin supports bouten markup using `《《Emphasis》》`, and you can choose both the bouten style and the rendering method.

= Can I enable only specific markup styles? =

Yes. Each supported markup rule can be enabled or disabled individually from the settings screen.

= What happens when I uninstall the plugin? =

When the plugin is uninstalled, its saved settings are removed.

== Screenshots ==

1. Ruby Markup Converter settings screen
2. Markup rule selection and preview
3. Bouten style and rendering method settings

== Changelog ==

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

= 1.0.0 =
* Initial public release.

= 0.9.1 =

* Addressed WordPress.org review feedback.

= 0.9.0 =

Initial review submission.
