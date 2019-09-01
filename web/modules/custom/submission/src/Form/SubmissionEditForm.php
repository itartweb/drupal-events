<?php

namespace Drupal\submission\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Submission entity edit forms.
 *
 * @ingroup event
 */
class SubmissionEditForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'submission_entity_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

}
