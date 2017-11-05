=== Jetpack Easy Playlists ===
Contributors: two7sclash
Donate link: http://www.jamesfishwick.com
Tags: playlists, jetpack, audio, mp3, player
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 2.4

Generate playlists automatically from mp3s attached to your post/page. Requires Jetpack.

== Description ==

Audio support in Wordpress makes me cry. No native player, the fields in the Media library seem wrong for audio, and let's not get into ID3 tag reading. The audio shortcode and player provided by Jetpack is a step in the right direction. However, the ability to directly create a player or playlist on a post/page from attached mp3s makes me sad again. Writing that shortcode is nasty business for anything beyond a file or two. 

And what about the [gallery] shortcode for images? So easy to round up all your attached pictures and display them all automagically. Why no love for [audio]?

This plugin acts as a wrapper for Jetpack's [audio] shortcode. It rounds up all the mp3s attached to your post/page and adds them as a playlist in the Jetpack player. Simply attach your mp3s to your post/page and use the shortcode "[jplaylist]" where you want your playlist

JEP supports all the options that [audio] does, and now allows you to call up attachments from other pages/posts. You can also turn on "list mode,"  "random mode," add download links, and even a pop-up button.

Requires [Jetpack](http://jetpack.me/).

[Plugin Homepage](http://www.jamesfishwick.com/software/auto-jetpack-playlist/)


== Installation ==


1. Upload `jetpack_easy_playlists.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the [jplaylist] shortcode to display a playlist of all the mp3s attached to your post

== Frequently Asked Questions ==

= How do I order the songs in the playlist? =

Click on "Add Audio". Go to "Gallery". Drag and drop your mp3s into the order you want (it ignores images). The plugin goes by ascending order.

= How do I change the title and artist info for a mp3? =

Song title corresponds to the "Title" field, and song artist corresponds to the, um, "Caption" field (I know this makes not a ton of sense, but I'm working with what I'm given folks). Wordpress makes a laughable guess as to the title of the mp3 when you upload it. I recommend you change this info as soon as you upload inside the post/page. You can also change this in the media library. 

= What about other audio formats than mp3? =

Sorry, other audio formats aren't supported by Jetpack's player. 

= What about accessing the player options (color, size, etc)? Can I do that? =

Yes! You can use any of those options. You can either use the janky pipe (|) method to connect everything like you're told to with the [<em>audio</em>] shortcode, or you can use proper attributes.

Example:

[jplaylist bgcolor="000000" lefticon="00ff00" righticon="FF0000" animation="no" loop="yes"]

or

[jplaylist bgcolor=000000|lefticon=00ff00|righticon=FF0000|animation=no|loop=yes]

= Can I use attachments from a different page/post? =

Sure. You use the "pid" attribute with a post/page id or permalink slug.

Examples:

[jplaylist pid="173"]
[jplaylist pid="software/jetpack-easy-playlists/"]

[More details](http://jamesfishwick.com/2012/jetpack-easy-playlists-update/)

= How do I turn on "list mode?" =

Use [jplaylist print="ol"] for an ordered list,  [jplaylist print="ul"] for an unordered one.

= How do I make my playlist have links? =

[playlist linked="true"]

= How do I turn on "random mode?" =

Use [jplaylist random='true'] for random song ordering.

= How do I add a pop-up button =

Use [jplaylist  external="true"] for a window with the dimensions 350x500. If you want to customize the dimensions, then use a comma seperated width and height, like [jplaylist  external="600,800"]. The playlist window will use the title of your post/page. It will also be linked to your default stylesheet so you can make it pretty. I leave it to the user to suss out the page structure of the pop-up.

= Can you support my favorite player WordPess player plugin xyz? =

Maybe! You can talk to me about it at least.

= I'm hitting an upload limit. Help! =

Welp, that's nothing to do with the plugin or even Wordpress. You can edit (or create) the php.ini file in your root directory to tweak this. Better yet, contact your host re: increasing your upload size.

== Changelog ==

= 2.4 =
* Added random mode
* Added list mode, with option to output links too
* Ability to dynamically generate a pop-up player

= 2.3 =
* Added list mode param

= 2.2 =
* More robust check for Jetpack and shortcodes module to avoid future bugs ala http://jamesfishwick.com/2012/jetpack-easy-playlists-update/
* Filters out non-mp3 audio files
* You can call playlists from other posts with the "pid" attribute
* Smarter error messages

= 2.1 =
* Cleaned up code and properly updated readme
 
= 2.0 =
* Fixed "unexpected $end" bug on some servers
* Jetpack was updated to wrap the audio shortcode functionality in a class, so plugin-in now checks for that class rather than the 'audio_shortcode' function.

= 0.02 =
* Added support for all player options (color, size, etc)