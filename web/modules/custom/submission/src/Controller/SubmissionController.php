<?php

namespace Drupal\submission\Controller;

use Drupal\Core\Controller\ControllerBase;

class SubmissionController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function addSubmission($event) {
    $form = \Drupal::formBuilder()->getForm('Drupal\submission\Form\SubmissionForm', $event);

    return $form;
  }

}
