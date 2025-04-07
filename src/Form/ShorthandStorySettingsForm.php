<?php

namespace Drupal\shorthand\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\shorthand\ShorthandApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure shorthand settings for this site.
 *
 * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
 *
 * @see https://www.drupal.org/project/shorthand/issues/3274487
 */
class ShorthandStorySettingsForm extends ConfigFormBase {

  /**
   * The manages modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Shorthand Api service.
   *
   * @var \Drupal\shorthand\ShorthandApiInterface
   */
  protected $shorthandApi;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The constructor method.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module manager.
   * @param \Drupal\shorthand\ShorthandApiInterface $shorthand_api
   *   The shorthand api connector.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger interface.
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3274487
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, AccountInterface $current_user, ModuleHandlerInterface $module_handler, ShorthandApiInterface $shorthand_api, MessengerInterface $messenger) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->shorthandApi = $shorthand_api;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3274487
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('shorthand.api.v2'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorthand_admin_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3274487
   */
  protected function getEditableConfigNames() {
    return ['shorthand.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3274487
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $error = $this->t('This is deprecated configuration page which will be removed in version 5.0. Use new <a href=":url">configuration page</a>.', [
      ':url' => Url::fromRoute('shorthand.settings_form')->toString(),
    ]);
    $this->messenger->addError($error);

    $config = $this->config('shorthand.settings');

    $form['shorthand_token'] = [
      '#default_value' => $config->get('shorthand_token'),
      '#description' => $this->t('Read how to obtain Shorthand API token <a href=":url" title="Shorthand api documentation">here</a>', [':url' => 'https://support.shorthand.com/en/articles/62-programmatic-publishing-with-the-shorthand-api']),
      '#maxlength' => 100,
      '#disabled' => TRUE,
      '#required' => TRUE,
      '#size' => 50,
      '#title' => $this->t('API token'),
      '#type' => 'textfield',
    ];

    $form['shorthand_request_timeout'] = [
      '#default_value' => $config->get('request_timeout') ?? 120,
      '#description' => $this->t('Number of seconds to wait before the \GuzzleHttp\Client request timeouts. Use 0 to wait indefinitely.'),
      '#disabled' => TRUE,
      '#min' => 0,
      '#title' => $this->t('Request timeout'),
      '#type' => 'number',
    ];

    $text_format_options = [];
    foreach (filter_formats() as $key => $filter) {
      $text_format_options[$key] = $filter->label();
    }

    $form['shorthand_input_format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Text field format'),
      '#description' => $this->t('Text format for the Shorthand text field, it should allow full HTML, JS and CSS. <a href=":url">See all text formats</a>', [':url' => Url::fromRoute('filter.admin_overview')->toString()]),
      '#options' => $text_format_options,
      '#disabled' => TRUE,
      '#default_value' => $config->get('input_format'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3274487
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $isValid = $this->shorthandApi->validateApiKey($form_state->getValue('shorthand_token'));
    if (!$isValid) {
      $form_state->setErrorByName('shorthand_token', $this->t('API key is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in shorthand:4.0.0 and is removed from shorthand:5.0.0. Use shorthand field.
   *
   * @see https://www.drupal.org/project/shorthand/issues/3274487
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('shorthand.settings');
    $config
      ->set('shorthand_token', $form_state->getValue('shorthand_token'))
      ->set('request_timeout', $form_state->getValue('shorthand_request_timeout'))
      ->set('input_format', $form_state->getValue('shorthand_input_format'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
