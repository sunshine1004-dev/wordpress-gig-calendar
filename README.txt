=== Plugin Name ===
Contributors: sergey pestin @herostat88, optune, schmidsi
Tags: gigs, events, calendar, optune, dj, upcoming gigs
Donate link: https://profiles.wordpress.org/herostat88
Requires at least: 4.6
Tested up to: 4.7
Stable tag: trunk,
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Automatically import your optune.me gigs to your Wordpress

== Description ==

This plugin automatically creates a post for every gig on your optune.me account. You can choose if the post is published 
imediately or if you want to publish it manually. Furthermore, you can display a list of all your upcoming gigs on a page
or post with the shortcode `[optune-gig-calendar]`.

If you don't want to have a new post for every gig you can change the post type to gig.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. There is now a new item in your admin menu: "Optune Gigs".  Go there to configure the plugin.
1. Enter your artist handle in the field "Your Optune Username"
1. Choose the status that the auto-generated post will have. If you choose "publish", new gigs/posts are published automatically.
1. Choose the type of post. If you choose "gig", new gigs will not show up as posts, but are in the database to be displayd with the `[optune-gig-calendar]` shortcode.


== Frequently Asked Questions ==

= What the heck is optune.me? =

Optune.me is the online collaboration platform for events & gigs where DJs, promoters and agents work together. DJs can 
manage their upcoming gigs easily. Checkout www.optune.me

== Changelog ==

= 1.1 =
- Sort Gigs by Date
- Set default post type to "post"(no more select box for post type)
- Auto update Gigs if settings in admin panel is changed

= 1.0 =
First release
