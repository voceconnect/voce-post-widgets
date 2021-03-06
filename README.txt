=== Voce Post Widgets ===
Contributors: johnciacia, markparolisi, banderon, voceplatforms
Tags: widgets, sidebar
Requires at least: 3.3
Tested up to: 3.4
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A better interface for managing your widgets.

== Description ==

Add widgets on a post by post basis by using a new interface directly on the post edit screen.

Filter to set the post types the Post Widgets plugin should load on
post_widget_post_types

== Installation ==

Minimum requirements:

* WordPress Version 3.3
* PHP Version 5.3

Instructions:

1. Upload the `voce-post-widgets` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

Usage: 
By default, the Pages post type has the widget UI enabled. You can add custom post types by using the filter `voce_post_widgets_post_types`.

Filters to set the default arguments for the sidebar:

* `post_widgets_default_sidebar_args`
* `post_widgets_default_sidebar_args-$sidebar`
* `post_widgets_default_sidebar_args-$post_name`
* `post_widgets_default_sidebar_args-$sidebar-$post_name`

Admin UI:

The first column shows all available widgets.
The second column shows all of the registered sidebars as a dropdown then that sidebar's active widgets.
To add/edit/delete widgets in a sidebar, select a sidebar name in the dropdown. All active widgets appear in below
and you can add new ones via drag and drop from the first (left) column and edit them as you normally would in the
widgets screen by opening their options with a click on the widget name. 

== Frequently asked questions ==

== Screenshots ==

1. Widget Interface

== Changelog ==
= 1.2 =
* Adding Capistrano deploy support

= 1.1 =
* Fixed bugs for post names containing numbers
* Added check for empty sidebar before attempting assignment

= 1.0 =
* Updated UI for metabox

= 0.5 =
* Updated documentation
* Fixing JS error for undefined vars

= 0.2 =
* Updated documentation.
* JS fixes to meet standards.

= 0.1 =
* Initial release