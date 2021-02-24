<?php

namespace Drupal\shorthand\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\shorthand\ShorthandApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure shorthand settings for this site.
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
   * The constructor method.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The manages modules.
   * @param \Drupal\shorthand\ShorthandApiInterface $shorthandApi
   *   The shorthand api connector.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AccountInterface $currentUser,
    ModuleHandlerInterface $moduleHandler,
    ShorthandApiInterface $shorthandApi) {
    parent::__construct($config_factory);
    $this->currentUser = $currentUser;
    $this->moduleHandler = $moduleHandler;
    $this->shorthandApi = $shorthandApi;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorthand_admin_settings';
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shorthand.settings');

    $form['shorthand_token'] = [
      '#default_value' => $config->get('token'),
      '#description' => $this->t('Read how to obtain Shorthand API token <a href=":url" title="Shorthand api documentation">here</a>', [':url' => 'https://support.shorthand.com/en/articles/62-programmatic-publishing-with-the-shorthand-api']),
      '#maxlength' => 100,
      '#required' => TRUE,
      '#size' => 50,
      '#title' => $this->t('API token'),
      '#type' => 'textfield',
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
      '#required' => TRUE,
      '#default_value' => $config->get('input_format'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // $isValid = \Drupal::service($apiservice)->validateApiKey($form_state->getValue('shorthand_token'));
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
      ->set('token', $form_state->getValue('shorthand_token'))
      ->set('input_format', $form_state->getValue('shorthand_input_format'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('shorthand.api.v2')
    );
  }

}
