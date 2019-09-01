<?php

namespace Drupal\submission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SubmissionSettingsForm.
 * @package Drupal\event\Form
 * @ingroup event
 */
class SubmissionSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   * @return string
   * The unique string identifying the form.
   */
  public function getFormId() {
    return 'submission_settings';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['manage']['#markup'] = 'Settings form for Submission. Manage field settings here.';

    return $form;
  }

}
