<?php

namespace Drupal\submission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;

class SubmissionConfirmForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'submission_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['markup'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="submission-confirm">',
      '#suffix' => '</div>',
      '#markup' => $this->t('This operation has can not be undone.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function checkAccess($operation) {
    return AccessResult::allowed();
  }

}
