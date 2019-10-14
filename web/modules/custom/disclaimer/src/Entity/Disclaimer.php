<?php

namespace Drupal\disclaimer\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the disclaimer entity.
 *
 * @ContentEntityType(
 *   id = "disclaimer",
 *   label = @Translation("Disclaimer"),
 *   label_collection = @Translation("Disclaimers"),
 *   label_singular = @Translation("Disclaimer"),
 *   label_plural = @Translation("Disclaimers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Disclaimer",
 *     plural = "@count Disclaimers",
 *   ),
 *   base_table = "disclaimer",
 *   handlers = {
 *     "access" = "Drupal\disclaimer\DisclaimerAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\disclaimer\Form\DisclaimerForm",
 *       "add" = "Drupal\disclaimer\Form\DisclaimerForm",
 *       "edit" = "Drupal\disclaimer\Form\DisclaimerForm",
 *       "delete" = "Drupal\disclaimer\Form\DisclaimerDeleteForm",
 *     },
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\disclaimer\DisclaimerListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer disclaimers",
 *   entity_keys = {
 *     "id" = "disclaimer_id",
 *   },
 *   field_ui_base_route = "entity.disclaimer.collection",
 *   links = {
 *     "add-page" = "/disclaimer/add",
 *     "add-form" = "/disclaimer/add",
 *     "canonical" = "/disclaimer/{disclaimer}",
 *     "collection" = "/admin/content/disclaimer",
 *     "delete-form" = "/disclaimer/{disclaimer}/delete",
 *     "edit-form" = "/disclaimer/{disclaimer}/edit",
 *   },
 * )
 */
class Disclaimer extends ContentEntityBase {
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
      $label = $this->t('Nameless #@id', ['@id' => $this->id()]);
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

    $fields['agree'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agree text'))
      ->setDescription(t('Agree text.'))
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

    $fields['disagree'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Disagree text'))
      ->setDescription(t('Disagree text.'))
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

    $fields['redirect'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Redirect'))
      ->setDescription(t('The link of the redirect.'))
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The text of the description.
    $fields['challenge'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Challenge'))
      ->setDescription(t('The question the user must confirm. "Do you agree?" type of question. <em>Agree</em> = User stays on requested page. <em>Disagree</em> = User is redirected to <em>Redirect</em> url specified below.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
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

    // The text of the description.
    $fields['disclaimer'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
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

    return $fields;
  }

}
