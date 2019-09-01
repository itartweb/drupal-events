<?php

namespace Drupal\submission\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides the submission entity.
 *
 * @ContentEntityType(
 *   id = "submission",
 *   label = @Translation("Submission"),
 *   label_collection = @Translation("Submission"),
 *   label_singular = @Translation("Submission"),
 *   label_plural = @Translation("Submission"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Submission",
 *     plural = "@count Submission",
 *   ),
 *   base_table = "submission",
 *   handlers = {
 *     "access" = "Drupal\submission\SubmissionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\submission\Form\SubmissionAddForm",
 *       "edit" = "Drupal\submission\Form\SubmissionEditForm",
 *       "delete" = "Drupal\submission\Form\SubmissionDeleteForm",
 *     },
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\submission\SubmissionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "Administer submission",
 *   entity_keys = {
 *     "id" = "submission_id",
 *   },
 *   field_ui_base_route = "submission.manage",
 *   links = {
 *     "add-page" = "/submission/add",
 *     "add-form" = "/submission/add",
 *     "canonical" = "/submission/{submission}",
 *     "collection" = "/admin/content/submission",
 *     "delete-form" = "/submission/{submission}/delete",
 *     "edit-form" = "/submission/{submission}/edit",
 *   },
 * )
 */
class Submission extends ContentEntityBase {
  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the workspace was last edited.'))
      ->setRevisionable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the workspace was created.'));

    $fields['firstname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First Name'))
      ->setDescription(t('First name.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lastname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last Name'))
      ->setDescription(t('Last name.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-Mail'))
      ->setDescription(t('Submission e-mail.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Phone'))
      ->setDescription(t('Submission phone.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'telephone_default',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['event_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Event'))
      ->setDescription(t('The event ID.'))
      ->setSetting('target_type', 'event')
      ->setSetting('handler', 'default')
      //->setSetting('handler_settings', ['target_bundles' => ['event' => 'event']])
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'event',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('Submission #@id', ['@id' => $this->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {

  }

}
