<?php

namespace Drupal\shorthand\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Shorthand story edit forms.
 *
 * @ingroup shorthand
 */
class ShorthandStoryForm extends ContentEntityForm {

  /**
   * The book being displayed.
   *
   * @var \Drupal\shorthand\Entity\ShorthandStory
   */
  protected $entity;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Initializes an instance of the Shorthand story.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger interface.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    AccountInterface $current_user,
    MessengerInterface $messenger,
    LoggerInterface $logger
  ) {
    parent::__construct(
      $entity_repository,
      $entity_type_bundle_info,
      $time
    );
    $this->currentUser = $current_user;
    $this->time = $time;
    $this->messenger = $messenger;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('logger.channel.shorthand')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\shorthand\Entity\ShorthandStory $entity */
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'shorthand/shorthandForm';

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    // Check formats.
    $formats = array_keys(filter_formats());
    $config = $this->config('shorthand.settings');
    $input_format = $config->get('input_format', filter_default_format());

    $format_fail = !in_array($input_format, $formats);

    if ($format_fail) {
      $error = $this->t('The <em>shorthand_input_format</em> setting value <em>@format</em> does not match existing text format. It should be one of the following: <strong>@formats</strong>', [
        '@format' => $input_format,
        '@formats' => implode(', ', $formats),
      ]);
      $this->messenger->addError($error);
      $this->logger->error($error);
    }

    if ($format_fail) {
      return new RedirectResponse('/admin/content/shorthand-story');
    }
    else {
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->currentUser->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Shorthand story.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Shorthand story.', [
          '%label' => $entity->label(),
        ]));
    }
    return $form_state->setRedirect(
      'entity.shorthand_story.canonical',
      ['shorthand_story' => $entity->id()]
    );
  }

}
