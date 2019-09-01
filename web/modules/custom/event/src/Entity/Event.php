<?php

namespace Drupal\event\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides the Event Details entity.
 *
 * @ContentEntityType(
 *   id = "event",
 *   label = @Translation("Event Details"),
 *   label_collection = @Translation("Event Details"),
 *   label_singular = @Translation("Event Details"),
 *   label_plural = @Translation("Event Details"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Event Details",
 *     plural = "@count Event Details",
 *   ),
 *   base_table = "event",
 *   handlers = {
 *     "access" = "Drupal\event\EventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\event\Form\EventAddForm",
 *       "edit" = "Drupal\event\Form\EventEditForm",
 *       "delete" = "Drupal\event\Form\EventDeleteForm",
 *     },
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\event\EventListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "Administer event",
 *   entity_keys = {
 *     "id" = "event_id",
 *   },
 *   field_ui_base_route = "event.manage",
 *   links = {
 *     "add-page" = "/event/add",
 *     "add-form" = "/event/add",
 *     "canonical" = "/event/{event}",
 *     "collection" = "/admin/content/event",
 *     "delete-form" = "/event/{event}/delete",
 *     "edit-form" = "/event/{event}/edit",
 *   },
 * )
 */
class Event extends ContentEntityBase {
  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = '';
    if ($item = $this->get('title')->first()->getValue()) {
      $label = $item['value'];
    }
    if (empty(trim($label))) {
      $label = t('Nameless #@id', ['@id' => $this->id()]);
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the test entity.'))
      ->setReadOnly(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the workspace was last edited.'))
      ->setRevisionable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the workspace was created.'));

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Title.'))
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

    $fields['date'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Date and time'))
      ->setDescription(t('When'))
      ->setRequired(true)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'daterange_default',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
//      //->setDefaultValue(DrupalDateTime::createFromTimestamp(time()));

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-Mail'))
      ->setDescription(t('Event e-mail.'))
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

    // The text of the description.
    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 12,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['send'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Send'))
      ->setDescription(t('Send a message by subscribed.'));

    $fields['category_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Category'))
      ->setDescription(t('The category ID.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['category' => 'category']])
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'category',
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

}
