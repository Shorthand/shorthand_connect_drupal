<?php

/**
 * Implements hook_help().
 *
 * Displays help and module information.
 *
 * @param path 
 *   Which path of the site we're using to display help
 * @param arg 
 *   Array that holds the current path as returned from arg() function
 */
function shorthand_connect_help($path, $arg) {
  switch ($path) {
    case "admin/help#shorthand_connect":
      return '' . t("A module that allows the publishing of Shorthand stories directly to Drupal.") . '';
      break;
  }
} 