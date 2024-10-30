=== JKL Reviews ===
Contributors:           jekkilekki
Plugin Name:            JKL Reviews
Plugin URI:             http://asnowberger.github.io/plugin-jkl-reviews/
Author:                 Aaron Snowberger
Author URI:             http://www.aaronsnowberger.com/
Donate link:            https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=567MWDR76KHLU
Tags:                   content, custom, custom meta box, review, reviews, books, music, movies, products, courses, services, FontAwesome
Requires at least:      3.5
Tested up to:           4.0
Stable tag:             1.2
Version:                1.2
License:                GPLv2 or later
License URI:            http://www.gnu.org/licenses/gpl-2.0.html

A simple Reviews plugin to review books, music, movies, products, or online courses 
with Star Ratings and links out to related sites.

== Description ==

As an avid reader myself, I wanted a better way to save the best of the best of 
what I read on my blog. I wanted a way to quickly and easily save book information, 
my 5-star rating, and external links out to the book and its resources to save for 
my own future and personal reference.

The primary functionality of this plugin is to store book (or product) information 
in a Custom Meta Box beneath your Post editor (in the back-end) that will then be 
displayed in its own "ratings ID card" at the top of your Post (in the front-end). 

Best of all: This plugin allows you to quickly input all the relevant information 
and links related to your review and have those *consistently* display in the same 
(responsive) format in your Posts without you ever needing to worry about styling 
or formatting the review boxes yourself. This allows you to spend more time writing 
the review itself and less time worrying about the display of your review.

In short: *This plugin makes writing reviews fun and simple as the styling is all 
taken care of for you.*

Requires WordPress 3.5 and PHP 5.

= Special Features = 
* Multiple Review Types supported
* [FontAwesome](http://fortawesome.github.io/Font-Awesome/) icons
* 8 color choices for the box header 
* Light and Dark display box styles
* Range slider to select your rating
* Uses WordPress's native Image Chooser to add a Cover Image
* Responsive design (front-end and back-end)
* Affiliate link support
* WordPress Options page
* Ability to enable or disable Affiliate link disclaimer on WP Options page
* Ability to enable or disable Affiliate link disclaimer on individual Posts after enabling that functionality in WP Options

= Information Stored by the Plugin = 
* The Title
* Author name
* Cover image
* Category
* Book Series
* Star-rating
* Summary
* External links
 * Affiliate/Product purchase link
 * The Book's homepage
 * The author's homepage
 * The book resources page 

= Additional Reviews Types supported = 
* Book
* Audio
* Video
* Course
* Product
* Service
* Other

= Planned Upcoming Features = 
* Custom Post Type
* Custom Taxonomy
* Shortcode
* Ability to link between Categories (like WP Tags)
* Ability to display all of a certain Review Type (like WP Categories)
* Parameters `big` and `small` to change the display box features
* Override (optional) the Post Featured Image with the Cover Image you choose
* Overlay Star-ratings over the Featured Image on index pages
* CSS animations
* Dropdown menu to select different icons for external links or Review Type
* Translations
* (More ideas? Let me know!)

= Translations = 
* English (EN) - default
* Korean (KO) - upcoming

If you want to help translate the plugin into your language, please have a look 
at the `.pot` file which contains all definitions and may be used with a [gettext] 
editor.

If you have created your own language pack, or have an update of an existing one, 
you can send your [gettext .po or .mo file] to me so that I can bundle it in the
plugin.

= Contact Me = 
If you have questions about, problems with, or suggestions for improving this 
plugin, please let me know at the [WordPress.org support forums](http://wordpress.org/support/plugin/jkl-reviews)

Want updates about my other WordPress plugins, themes, or tutorials? Follow me [@jekkilekki](http://twitter.com/jekkilekki)


== Installation ==

= Automatic installation =
1. Log into your WordPress admin
1. Go to `Plugins -> Add New`
1. Search for `JKL Reviews`
1. Click `Install Now`
1. Activate the Plugin

= Manual installation =
1. Download the Plugin
1. Extract the contents of the .ZIP file
1. Upload the contents of the `jkl-reviews` directory to your `/wp-content/plugins` 
folder of your WordPress installation
1. Activate the Plugin from the `Plugins` page

= Documentation = 
Full documentation of the Plugin and its uses can (currently) be found at its [GitHub page](http://asnowberger.github.io/plugin-jkl-reviews/) 

== Frequently Asked Questions ==

= Tips =
As a general rule, it is always best to keep your WordPress installation and all 
Themes and Plugins fully updated in order to avoid problems with this or any other 
Themees or Plugins. I regularly update my site and test my Plugins and Themes with
the latest version of WordPress.

= How can I change the style of the plugin to match my website? =
While there is currently no built-in Color Chooser to allow for full customization 
of the plugin styles (though that is being considered for future releases), each 
element within the `jkl_review_box` contains its own unique CSS identifier, allowing 
you to hook into those in your own Custom CSS stylesheet.

Additionally, there are now (since version 1.2) 8 different header color choices 
and 2 box style choices (Light and Dark) that you can choose from in the WP Options
Page. These are not currently available to mix and match on individual Post pages. 
Whichever style you choose in WP Options will be the style for the entire site.

= Why doesn't the Cover Image show up when I select it? =
In order to *immediately* display the Cover Image after you select it, this plugin 
would need to run AJAX (something it currently does not do). So, in order to see 
the preview of your Cover Image in the Custom Meta Box, you need to first 
either `Save Draft` or `Publish` or `Update` your Post so that it **saves** the 
meta data first.

= What if I don't know the some of the links for the product? =
Simple: just leave those spaces blank. The plugin tests for the presence of data in
each input location and ONLY displays the data which it has saved. If you don't 
give it data in a particular field, it won't display that field at all on the Post page.

= Why doesn't the Affiliate Link disclaimer show up? =
You need to enable the Material Disclosures option in the WordPress Options page 
for this plugin. After doing so, you'll be shown a preview of the Disclosures in 
your preferred box style (Light or Dark). You will then be able to choose whether 
or not to include the Disclosures on a Post-by-Post basis in the last of the 
Custom meta boxes.

= Why can't I specify the `Other` Review Type? =
There is currently no functionality to input or modify the `Other` Review Type 
although this may be a feature to be considered for future releases.

= Why doesn't the Reviews plugin show up on my WordPress Pages? =
The plugin's current functionality only places it on WordPress Posts. Later, there 
will be a Custom Post Type released for this plugin as well as additional display
options, but for now, the plugin will only work on WordPress Posts.

= How can I add multiple {authors, series, categories, images, links, etc}? =
Currently there is no way to add (linking) multiples of anything. If you want to 
add multiple authors, series, or categories, just type in the two (or more) names 
in the appropriate fields. If you want to add multiple images, use the Post Editor 
to do so. If you want to add additional links, specifiy those in the Post Editor.

= What if I want to add Tags to the Review for easier searching? =
If you want to add Tags, please use the Post Tags in the default WordPress Post
editor. Tags specifically for Reviews will not be implemented until the Custom
Post Type for Reviews is released.

= FontAwesome is not displaying properly on my site or taking too long to load. =
Please send me a detailed description of your error and a link to the site with the 
issue so that I can have a look.

= The display box style is broken/messed up on my site. =
Please send me a detailed description of your error as well as a screenshot so that
I can take a look at it. Things to include in your message include: (1) Your WordPress 
installation version number, and (2) The Theme you are using (and its version 
number - if relevant).

== Screenshots ==

1. Custom Meta Box on the Post Editor page
2. Review box display on the Post itself (defaults)
3. Dark review box with different header color
4. Responsive view of the Review box (Light and Dark)
5. Possible color combinations for the Review box
6. WP Options page

== Other Notes ==

= Support = 
[Click here to visit the forum for this plugin](https://wordpress.org/support/plugin/jkl-reviews)

= Acknowledgements = 
This plugin uses:

* [jQuery](http://jquery.com/) licensed under MIT License or GNU General Public License (GPL) Version 2
* [FontAwesome](http://fortawesome.github.io/Font-Awesome/) licensed under SIL OFL 1.1;
Code licensed under MIT License
* [Material Connection Disclosures](http://michaelhyatt.com/five-ways-to-comply-with-the-new-ftc-guidelines-for-bloggers.html) 
as found on Michael Hyatt's website and in compliance with FTC Guidelines

= License = 
This program is free software; you can redistribute it and/or modify it under the terms 
of the GNU General Public License as published by the Free Software Foundation; either 
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY 
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this 
program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth 
Floor, Boston, MA 02110-1301 USA

== Changelog ==

= 1.2 =
* Added WP Options page
* Made Material Connection Disclosures box optional with a checkbox
* Added a Dark box style
* Added 8 different header color choices based on colors in the JKL Reviews Icon
* Added an attribution link at the bottom-right of the plugin

= 1.1 =
* Added Material Connection Disclosures to comply with FTC regulations for affiliate links and the like

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.2 =
* Added WP Options page with different color choices, box styles, and checkboxes for optional components

= 1.1 =
* Added Material Connection Disclosures to comply with FTC regulations for affiliate links and the like

= 1.0 =
* Initial release
