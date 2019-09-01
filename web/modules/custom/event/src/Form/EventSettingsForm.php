<?php

namespace Drupal\event\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EventSettingsForm.
 * @package Drupal\event\Form
 * @ingroup event
 */
class EventSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   * @return string
   * The unique string identifying the form.
   */
  public function getFormId() {
    return 'event_settings';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['manage']['#markup'] = 'Settings form for Event. Manage field settings here.';

    return $form;
  }

}
