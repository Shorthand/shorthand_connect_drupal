Shorthand
=========

This module provides integration with [Shorthand](https://shorthand.com/), an application which describes itself as "beautifully simple storytelling". It connects your Shorthand account with Drupal and allows you to publish your stories on a Drupal website. [Create your Shorthand account](https://app.shorthand.com/signup/) to get a User ID and API Token (found on the account settings page) which are prerequisite for using this module.


Dependencies

- Entity API
- JQuery Update


Installation

- Install as any other Drupal module
- Go to admin/config/content/shorthand_connect and enter your User ID and API Token
- Go to node/add and add a Shorthand Story

You may customise the provided `html--shorthand-story.tpl.php` and `shorthand_story_node.tpl.php` template files