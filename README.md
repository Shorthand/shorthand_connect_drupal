# Shorthand

This module provides integration with [Shorthand](https://shorthand.com/), an application which describes itself as "beautifully simple storytelling". It connects your Shorthand account with Drupal and allows you to publish your stories on a Drupal website.


## Dependencies

- Entity API
- JQuery Update


## Installation

- Install as any other Drupal module
- Add your token, input format and (if version 1) user ID to settings.php

`
$settings['shorthand_version'] = '1';
$settings['shorthand_user_id'] = '11111';
$settings['shorthand_token'] = '111-1111111111111111';
$settings['shorthand_input_format'] = 'full_unrestricted';
`

- Create a new input format called "full_unrestricted" that supports HTML (if using a new input format)
- Go to Content > Shorthand story list and add a Shorthand Story

