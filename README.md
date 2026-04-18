# Elementor Taxonomy Loop Widget

A WordPress plugin that extends Elementor with a powerful taxonomy loop widget for displaying and organizing content based on taxonomies.

## Description

Elementor Taxonomy Loop Widget is a WordPress plugin that adds a custom Elementor widget to enhance your website's content organization. The plugin provides a powerful Taxonomy Loop widget that allows you to display and organize content based on taxonomies in a customizable way.

## Features

### Taxonomy Loop Widget

The Taxonomy Loop widget allows you to:
- Display content organized by taxonomies (categories, tags, or custom taxonomies)
- Customize the display of taxonomy terms and their associated posts
- Control the ordering and filtering of terms
- Style the layout with various customization options
- Create custom loop templates for different display styles

## Requirements

- WordPress 5.0 or higher
- Elementor 3.25.0 or higher
- Elementor Pro 3.25.0 or higher

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded zip file
4. Click "Install Now" and then "Activate"

## Usage

### Taxonomy Loop Widget

1. Edit a page or post with Elementor
2. Search for "Taxonomy Loop" in the widget panel
3. Drag and drop the widget into your layout
4. Configure the widget settings:
   - Select the post type
   - Choose the taxonomy to display
   - Select or create a loop template
   - Configure display options (show/hide empty terms, include/exclude terms)
   - Set ordering preferences
   - Customize styling options

## Widget Settings

### Content Settings
- **Post Type**: Select the post type to display
- **Taxonomy**: Choose the taxonomy to organize content
- **Loop Skin**: Select or create a template for displaying the content
- **Hide Empty Terms**: Toggle to show/hide terms with no posts
- **Show Divider**: Add visual separation between items
- **Include/Exclude Terms**: Filter specific terms by ID
- **Order By**: Sort terms by name, ID, slug, menu order, or include order
- **Order Direction**: Choose ascending or descending order

### Style Settings
- **Category Gap**: Adjust spacing between taxonomy items
- **Content Gap**: Control spacing between taxonomy and its posts
- **Border Settings**: Customize borders around taxonomy items
- **Border Radius**: Adjust corner rounding
- **Padding**: Control internal spacing

## Support

For support, feature requests, or bug reports, please visit our [contact page](https://beenacle.com/contact-us/).

## Changelog

### 1.1.0
* Require Elementor Pro as a hard dependency; widget now registers only when Pro is active.
* Replace the per-term query loop with two consolidated queries (1 WP_Query + 1 wp_get_object_terms) bucketed in PHP.
* Rename the `show_empty` control to `hide_empty`; existing widget instances keep their saved toggle via a raw-data fallback.
* Whitelist `orderby`/`order` values before passing to WP_Query.
* Show a "Please select a valid loop template" message when the loop skin is empty or invalid instead of rendering an empty container.
* Change the default `posts_per_term` from `-1` to `6` to avoid unbounded queries on new widgets.
* Use semantic tokens (`left`/`center`/`right`) for the divider alignment control instead of raw CSS fragments.
* Move the widget from Elementor's reserved `basic` category to `general`.
* Consistency: normalize indentation and use `esc_html__()` throughout the widget file.
* Update author name to Beenacle.

### 1.0.0
* Initial release

## License

Elementor Taxonomy Loop Widget is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Elementor Taxonomy Loop Widget is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Elementor Taxonomy Loop Widget. If not, see [GNU GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## Credits

Developed by Beenacle