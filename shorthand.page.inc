<?php

/**
 * @file
 * Contains shorthand.page.inc.
 *
 * Page callback for Shorthand stories entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Shorthand stories templates.
 *
 * Default template: shorthand-story.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_shorthand_story(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
