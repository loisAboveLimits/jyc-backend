# Image URL Formatter

This module adds an FieldFormatter for an Image field,
which let's you grab the URL of an image directly

While most of the code comes from the core module image's
ImageFormatter.php, some rewriting with templating has
been done. Should be stable.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/image_url_formatter).

* To submit bug reports and feature suggestions, or track changes in the
  [issue queue](https://www.drupal.org/project/issues/image_url_formatter).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. After installation of this module you'll get a new
   format type "Image URL Formatter" you can assign to any Image Field
   in a Content Type. Just go to the Type's "Manage Display"
   and choose "Image URL Formatter" instead of "Image" from the Format
   Combo-Box.

2. Same goes for Image Content Fields in Views, choose
   "Image URL" as format and an URL Type out of
   'Full' (default), 'Absolute' or 'Relative'.

3. Now the image's URL in the chosen form will be returned
   instead of the full <img> tag.

4. As there seems to be an issue with "Global: Custom Text"
   at the moment (21/08/15), just use the Template TWIG
   for formatting your output or overwrite the Template
   in your Profile.


## Maintainers

- howard ge - [g089h515r806](https://www.drupal.org/u/g089h515r806)
- Bálint Nagy - [nagy.balint](https://www.drupal.org/u/nagybalint)
- Viktor Holovachek - [AstonVictor](https://www.drupal.org/u/astonvictor)
