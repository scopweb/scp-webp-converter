=== WebP Converter for WordPress ===
Contributors: your_username
Tags: webp, images, compression, optimization, performance
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically converts JPEG and PNG images to WebP format with support for all image sizes and smart format detection.

== Description ==

WebP Converter for WordPress is a powerful plugin that automatically converts your JPEG and PNG images to the modern WebP format, providing better compression and faster loading times for your website.

**Key Features:**

* **Automatic Conversion**: Converts images to WebP when uploaded to Media Library
* **All Image Sizes**: Supports thumbnails, medium, large, and custom sizes
* **Smart Format Detection**: Configurable file naming (single or double extension)
* **Bulk Conversion**: Convert existing images in batches
* **Content Processing**: Replace hardcoded image URLs in post content
* **Format Unification**: Rename existing WebP files to maintain consistency
* **Browser Support**: Automatically detects WebP support and serves appropriate format
* **Quality Control**: Separate quality settings for JPEG and PNG conversions
* **WP-CLI Integration**: Command line tools for bulk operations

**File Naming Formats:**
* **Double Extension**: `image.jpg.webp` (recommended for compatibility)
* **Single Extension**: `image.webp` (compatible with plugins like Optimus)

**WordPress Standards Compliant:**
* Follows WordPress Coding Standards
* Proper data sanitization and security
* Translation ready
* GPL v2+ licensed

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/scp-webp-converter/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > WebP Converter to configure the plugin
4. Choose your preferred file naming format and quality settings

== Requirements ==

* PHP 7.4 or higher
* WordPress 5.8 or higher
* GD extension with WebP support OR Imagick extension
* Write permissions to wp-content/uploads directory

== Frequently Asked Questions ==

= Does this plugin work with all themes and plugins? =

Yes, the plugin uses WordPress standard hooks and filters to ensure compatibility with most themes and plugins.

= What happens if I deactivate the plugin? =

Your WebP files remain on the server. Original JPEG/PNG images are never deleted, so your site continues to function normally.

= Can I convert existing images? =

Yes, use the Bulk Conversion tab to convert existing images in your Media Library.

= How does browser compatibility work? =

The plugin automatically detects if the visitor's browser supports WebP and serves the appropriate format accordingly.

= Can I change the file naming format? =

Yes, you can switch between double extension (image.jpg.webp) and single extension (image.webp) formats. Use the Format Unification tool to rename existing files.

== Screenshots ==

1. Main settings page with quality and format options
2. Bulk conversion interface with progress tracking
3. Format unification tool with statistics
4. Media Library integration with WebP status indicators
5. Server capabilities verification panel

== Changelog ==

= 1.3.0 =
* Added format unification utility for consistent file naming
* Implemented smart duplicate file handling
* Added comprehensive statistics and recommendations
* Improved bulk processing with batch operations
* Enhanced WordPress repository compliance
* Better error handling and logging

= 1.2.0 =
* Added configurable file naming formats (double vs single extension)
* Implemented content processing for hardcoded image URLs
* Added bulk conversion functionality
* Improved Media Library integration
* WP-CLI command support

= 1.1.0 =
* Added support for all image sizes and thumbnails
* Improved browser detection for WebP support
* Better error handling and logging
* Performance optimizations

= 1.0.0 =
* Initial release
* Basic WebP conversion for uploaded images
* Quality settings for JPEG and PNG
* WordPress Media Library integration

== Upgrade Notice ==

= 1.3.0 =
This version adds powerful file management tools and improved WordPress repository compliance. Backup recommended before upgrading.

== Technical Details ==

**Server Compatibility:**
The plugin automatically detects server capabilities and provides feedback on WebP support through GD or Imagick extensions.

**File Structure:**
* `includes/core/` - Core conversion and processing classes
* `admin/` - Administrative interfaces and bulk operations
* `assets/` - JavaScript and CSS files
* `languages/` - Translation files (ready for community translations)

**Hooks and Filters:**
* `wp_generate_attachment_metadata` - Automatic conversion on upload
* `wp_get_attachment_image_src` - WebP URL replacement
* `wp_calculate_image_srcset` - Responsive image WebP support
* `the_content` - Content processing for hardcoded images

**WP-CLI Commands:**
* `wp scp-webp/convert-missing` - Bulk convert images missing WebP versions

For technical support and feature requests, please visit the plugin's GitHub repository or WordPress.org support forum.