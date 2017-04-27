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
function shorthand_admin_settings($form, &$form_state) {
  $form['shorthand_user_id'] = array(
    '#type' => 'textfield',
    '#title' => t('User ID'),
    '#default_value' => variable_get('shorthand_user_id', ''),
    '#size' => 6,
    '#maxlength' => 6,
    '#description' => t('Your Shorthand User ID.'),
    '#required' => TRUE,
  );
  $form['shorthand_token'] = array(
    '#type' => 'textfield',
    '#title' => t('Shorthand Token'),
    '#default_value' => variable_get('shorthand_token', ''),
    '#size' => 30,
    '#maxlength' => 30,
    '#description' => t('Your Shorthand API Token.'),
    '#required' => TRUE,
  );

  return system_settings_form($form);
}

/**
 * Form-validate callback for admin settings form.
 */
function shorthand_admin_settings_validate($form, &$form_state) {
  $user_id = $form_state['values']['shorthand_user_id'];
  $token = $form_state['values']['shorthand_token'];
  if (!is_numeric($user_id)) {
    form_set_error('shorthand_user_id', t('User ID must be an integer.'));
  }
  else {
    $data = sh_get_profile($user_id, $token);
    if (!isset($data->username)) {
      form_set_error('shorthand_token', t('Invalid token.'));
    }
  }
}
