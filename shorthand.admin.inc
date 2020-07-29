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
    '#default_value' => variable_get('shorthand_version', 'v1'),
    '#options' => [
      'v1' => t('Version 1'),
      'v2' => t('Version 2'),
    ],
    '#description' => t('Shorthand Version'),
    '#required' => TRUE,
    '#suffix' => '
      <script>
        document.getElementById("edit-shorthand-version").addEventListener("change", updateShOptions);
        function updateShOptions() {
          var x = document.getElementById("edit-shorthand-version");
          console.log(x.value);
          switch (x.value) {
            case "v1":
              for(var i=0; i < document.getElementsByClassName("v1row").length; i++) {
                document.getElementsByClassName("v1row")[i].setAttribute("class", "v1row");
              }
              break;
            case "v2":
              for(var i=0; i < document.getElementsByClassName("v1row").length; i++) {
                if (document.getElementById("edit-shorthand-user-id").value === "") {
                  document.getElementById("edit-shorthand-user-id").value = "0";
                }
                document.getElementsByClassName("v1row")[i].setAttribute("class", "v1row v1row-hidden");
              }
              break;
          }
        }
      </script>
      <style>
        .v1row-hidden {
          display: none;
        }
      </style>
    ',
  ];

  $user_id_class = "v1row";
  if (variable_get('shorthand_version', 'v1') == 'v2') {
    $user_id_class = "v1row v1row-hidden";
  }

  $form['shorthand_user_id'] = [
    '#type' => 'textfield',
    '#title' => t('User ID'),
    '#default_value' => variable_get('shorthand_user_id', ''),
    '#size' => 6,
    '#maxlength' => 6,
    '#description' => t('Your Shorthand User ID.'),
    '#required' => TRUE,
    '#prefix' => '<div class="' . $user_id_class . '">',
    '#suffix' => '</div>',
  ];

  $form['shorthand_token_v1'] = [
    '#type' => 'textfield',
    '#title' => t('V1 API Token'),
    '#default_value' => variable_get('shorthand_token_v1', ''),
    '#size' => 40,
    '#maxlength' => 40,
    '#description' => t('Your Shorthand v1 API Token.'),
  ];

  $form['shorthand_token_v2'] = [
    '#type' => 'textfield',
    '#title' => t('V2 API Token'),
    '#default_value' => variable_get('shorthand_token_v2', ''),
    '#size' => 40,
    '#maxlength' => 40,
    '#description' => t('Your Shorthand v2 API Token.'),
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
  $version = $form_state['values']['shorthand_version'];
  if ($version == 'v2') {
    $token_var = 'shorthand_token_v2';
    $user_id = 0;
  }
  else {
    $token_var = 'shorthand_token_v1';
    $user_id = $form_state['values']['shorthand_user_id'];
  }

  if (!is_numeric($user_id)) {
    form_set_error('shorthand_user_id', t('User ID must be an integer.'));
  }
  else {
    $data = sh_get_profile($user_id, $form_state['values'][$token_var], $version);
    if (!isset($data->username)) {
      form_set_error($token_var, t('Invalid token.'));
    }
  }
}
