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
 * Provides the Submission Details entity.
 *
 * @ContentEntityType(
 *   id = "submission",
 *   label = @Translation("Submission Details"),
 *   label_collection = @Translation("Submission Details"),
 *   label_singular = @Translation("Submission Details"),
 *   label_plural = @Translation("Submission Details"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Submission Details",
 *     plural = "@count Submission Details",
 *   ),
 *   base_table = "submission",
 *   handlers = {
 *     "access" = "Drupal\submission\SubmissionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\submission\Form\SubmissionForm",
 *       "edit" = "Drupal\submission\Form\SubmissionForm",
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

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Code'))
      ->setSetting('max_length', 128)
      ->setRequired(TRUE)
      ->setDescription(new TranslatableMarkup('The code.'));

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-Mail'))
//      ->setDescription(t('Submission e-mail.'))
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

    $fields['store_department'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Winkel / afdeling'))
//      ->setDescription(t('Store department.'))
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

    $fields['store_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Winkeladres'))
//      ->setDescription(t('Store address.'))
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

    $fields['training_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Training'))
      ->setDescription(t('The training ID.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['training' => 'training']])
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'training',
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

    $fields['country_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Country'))
      ->setDescription(t('The country ID.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['countries' => 'countries']])
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'countries',
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

    $fields['retailer_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Retailer'))
      ->setDescription(t('The retailer ID.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['retailers' => 'retailers']])
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'retailers',
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
  public static function getSubmissionIdByCode($code) {
    $ids = \Drupal::entityQuery('submission')
      ->condition('code', $code)
      ->execute();

    return !empty($ids) ? current($ids) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $code = parent::getFields()['code']->__get('value');
    if (empty($code) || $code === NULL) {
      $service = \Drupal::service('submission.custom_services');
      $code = $service->getUniqueCode(8);
      parent::getFields()['code']->__set('value', $code);
    }

    $request = \Drupal::request();
    $session = $request->getSession();
    if (\Drupal::currentUser()->isAnonymous() && !$session->isStarted()) {
      $session->start();
      $session->migrate();
    }
    elseif (!$session->isStarted()) {
      $session->start();
    }
    $session->set('unique_code', $code);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $submission_id = parent::getFields()['submission_id']->__get('value');
    $is_sent = parent::getFields()['field_email_after_creation']->__get('value');
    if (empty($is_sent) || $is_sent != 1) {
      $service = \Drupal::service('submission.custom_services');
      $service->buildMail($submission_id);
    }
  }

}
