# Shorthand

This module provides integration with [Shorthand](https://shorthand.com/), an
application which describes itself as "beautifully simple storytelling". It
connects your Shorthand account with Drupal and allows you to publish your
stories on a Drupal website.

> :warning: **Version 3.0+**: There is no upgrade path from previous version. Uninstall previous version to install version 3.0.

## Installation

### Composer

```
composer require drupal/shorthand
```

### Download

Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/docs/extending-drupal/installing-modules
for further information.

## Configuration

- Login to [shorthand account](https://shorthand.com/signin) and
  [generate API key](https://support.shorthand.com/en/articles/62-programmatic-publishing-with-the-shorthand-api).

- Create a new input format that supports HTML by visiting page `/admin/config/content/formats`, or use an existing one (Full HTML)

  - Ideally for this we want to avoid having filters adjusting the content.

- Visit configuration page at `/admin/config/content/shorthand` to add API key and other settings.

## Usage

- Go to Content > Shorthand story list and add a Shorthand Story

The Story content will be added to the body of the entity, which by default
displays together with Name and Author when visiting the Story page.

You may want to alter the Display settings to hide the Title and the Author, as
well as alter the page display for shorthand stories in order to hide
everything but the story content itself. i.e. by changing the page.html.twig
file, or through the Context contrib module creating a context for the stories
pages.

There are several ways to display the story as a full page, just use the one
who best suits you.

# Authors

* Andrew Dekker <@simultech>
* Deji Akala <@dejiakala>
* Gabriele Maira <@gambry>
* Ben Hoad <@benhoad>
* [Janna M](https://www.drupal.org/u/jannakha)
* [Vladimir R](https://www.drupal.org/u/vladimiraus)
