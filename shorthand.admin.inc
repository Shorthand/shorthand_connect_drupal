<?php

/**
 * @file
 * Admin settings page.
 */

/**
 * Admin settings form.
 *
 * @param array $form
 *   The settings form.
 * @param array $form_state
 *   The current state of the form.
 */
function shorthand_admin_settings(array $form, array &$form_state) {
  $form['shorthand_version'] = [
    '#type' => 'select',
    '#title' => t('Shorthand API Version'),
    '#default_value' => variable_get('shorthand_version', 'v2'),
    '#options' => [
      'v2' => t('Version 2'),
    ],
    '#required' => TRUE,
    '#disabled' => TRUE,
  ];

  $form['shorthand_token_v2'] = [
    '#type' => 'textfield',
    '#title' => t('V2 API Token'),
    '#default_value' => variable_get('shorthand_token_v2', ''),
    '#size' => 40,
    '#maxlength' => 40,
    '#description' => t('Your Shorthand v2 API Token. Read Shorthand API documentation <a href="@url">here</a>.',
      ['@url' => 'https://support.shorthand.com/en/articles/62-programmatic-publishing-with-the-shorthand-api']),
  ];

  $options = [];
  $formats = filter_formats();
  foreach ($formats as $format) {
    $options[$format->format] = $format->name;
  }

  $form['shorthand_input_format'] = [
    '#type' => 'select',
    '#title' => t('Text format'),
    '#options' => $options,
    '#default_value' => variable_get('shorthand_input_format', filter_default_format()),
    '#weight' => 10,
    '#description' => t('The input format to use for Shorthand story text fields.'),
  ];

  $form['shorthand_curlopt_ssl_verifypeer'] = [
    '#type' => 'select',
    '#title' => t('Curl SSL verification'),
    '#options' => [
      0 => 0,
      1 => 1,
    ],
    '#default_value' => variable_get('shorthand_curlopt_ssl_verifypeer', 1),
    '#weight' => 12,
    '#description' => t('CURLOPT_SSL_VERIFYPEER option for curl command to download the story.'),
  ];

  return system_settings_form($form);
}

/**
 * Form-validate callback for admin settings form.
 */
function shorthand_admin_settings_validate($form, &$form_state) {
  $token = $form_state['values']['shorthand_token_v2'];

  $is_valid = sh_is_token_valid($token);
  if (!$is_valid) {
    form_set_error('shorthand_token_v2', t('Shorthand API token is not valid.'));
  }
}
