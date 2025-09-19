## Shorthand Integration Module

This module provides seamless integration with [Shorthand](https://www.shorthand.com/),  
a platform for creating visually rich, immersive stories. It connects Shorthand account  
to Drupal website, allowing you to publish and manage Shorthand stories directly within Drupal.

### Supported Versions

- Drupal 10  
- Drupal 11 

### Configuration

1. Log in to your Shorthand account and generate your API key.  
2. Navigate to the configuration page at `/admin/config/content/shorthand`.  
3. Enter your API key and configure additional settings as needed.  
4. Ensure that a text format allowing full HTML is available (use an existing format or create a new one).

### Usage

1. Add a **Shorthand field** to any entity type (e.g., content type, taxonomy vocabulary, or user entity).  
2. Create or edit an entity and use the Shorthand field to select a story from your connected Shorthand account.  
3. The story is rendered via the Shorthand field, not the body field.

### Display Customization

To create a clean, immersive story presentation:

- Hide other fields such as Title or Author if they are not needed.  
- Customize your theme templates, such as `page.html.twig`, to adjust layout for story pages.  
- Use the **Context** module or other layout tools to create specific display logic for Shorthand stories.

There are multiple ways to display the story in a full-page format—use the method that best suits your site's design and workflow.
