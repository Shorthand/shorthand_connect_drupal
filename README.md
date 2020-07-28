# Shorthand

This module provides integration with [Shorthand](https://shorthand.com/), an application which describes itself as "beautifully simple storytelling". It connects your Shorthand account with Drupal and allows you to publish your stories on a Drupal website.


## Dependencies

- Entity API
- JQuery Update


## Installation

- Install as any other Drupal module
- Add your Shorthand API version, token and input format to settings.php

`
$settings['shorthand_version'] = '2';
$settings['shorthand_token'] = '111-1111111111111111';
$settings['shorthand_input_format'] = 'full_unrestricted';
`

- **[ONLY V1]** Add your user ID to settings.php
`
$settings['shorthand_user_id'] = '11111';
`

- Create a new input format called "full_unrestricted" that supports HTML, or if
 you have already an input format
allowing all HTML tags use its machine name as 
`$settings['shorthand_input_format'] = 'your_input_format_machine_name';`
- Go to Content > Shorthand story list and add a Shorthand Story

The Story content will be added to the body of the entity, which by default 
displays together with Name and 
Author when visiting the Story page.

You may want to alter the Display settings to hide the Title and the Author, as 
well as alter the page display for
shorthand stories in order to hide everything but the story content itself. i.e.
 by changing the page.html.twig file, or through the Context contrib module 
 creating a context for the stories pages.
 
There are several ways to display the story as a full page, just use the one 
 who best suits you.
