<?php

namespace Drupal\shorthand\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\shorthand\ShorthandApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure shorthand settings for this site.
 */
class ShorthandSettingsForm extends ConfigFormBase {

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
   * The constructor method.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The manages modules.
   * @param \Drupal\shorthand\ShorthandApiInterface $shorthand_api
   *   The shorthand api connector.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    AccountInterface $current_user,
    ModuleHandlerInterface $module_handler,
    ShorthandApiInterface $shorthand_api,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->shorthandApi = $shorthand_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('shorthand_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shorthand.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorthand_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shorthand.settings');

    $form['shorthand_token'] = [
      '#title' => $this->t('API token'),
      '#description' => $this->t('Read how to obtain Shorthand API token <a href=":url" title="Shorthand api documentation" target="_blank">here</a>', [
        ':url' => 'https://support.shorthand.com/en/articles/62-programmatic-publishing-with-the-shorthand-api',
      ]),
      '#maxlength' => 100,
      '#required' => TRUE,
      '#size' => 50,
      '#type' => 'textfield',
      '#default_value' => $config->get('shorthand_token'),
    ];

    $form['keep_previous_versions'] = [
      '#title' => $this->t('Keep previously downloaded versions'),
      '#description' => $this->t('When unchecked, older downloaded versions are deleted whenever a story is updated, keeping only the latest version in local storage.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('keep_previous_versions') ?? TRUE,
    ];

    $text_format_options = [];
    foreach (filter_formats() as $key => $filter) {
      $text_format_options[$key] = $filter->label();
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $isValid = $this->shorthandApi->validateApiKey($form_state->getValue('shorthand_token'));
    if (!$isValid) {
      $form_state->setErrorByName('shorthand_token', $this->t('API key is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('shorthand.settings');
    $config
      ->set('shorthand_token', $form_state->getValue('shorthand_token'))
      ->set('keep_previous_versions', $form_state->getValue('keep_previous_versions'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
