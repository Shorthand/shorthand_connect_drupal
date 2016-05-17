<?php

/**
 * Page callback: Current posts settings
 *
 * @see current_posts_menu()
 */
function shorthand_connect_form($form, &$form_state) {
  $form['shorthand_connect_user_id'] = array(
    '#type' => 'textfield',
    '#title' => t('User ID'),
    '#default_value' => variable_get('shorthand_connect_user_id', ''),
    '#size' => 6,
    '#maxlength' => 6,
    '#description' => t('Your Shorthand User ID.'),
    '#required' => TRUE,
  );
  $form['shorthand_connect_token'] = array(
    '#type' => 'textfield',
    '#title' => t('Shorthand Token'),
    '#default_value' => variable_get('shorthand_connect_token', ''),
    '#size' => 30,
    '#maxlength' => 30,
    '#description' => t('Your Shorthand API Token.'),
    '#required' => TRUE,
  );
  return system_settings_form($form);
}

function shorthand_connect_form_validate($form, &$form_state){
  $user_id = $form_state['values']['shorthand_connect_user_id'];
  $token = $form_state['values']['shorthand_connect_token'];
  if (!is_numeric($user_id)){
    form_set_error('shorthand_connect_user_id', t('User ID must be an integer.'));
  }
  else {
  	$data = sh_get_profile($user_id, $token);
    if(!isset($data->username)) {
      form_set_error('shorthand_connect_token', t('Invalid token.'));
    }
  }
}