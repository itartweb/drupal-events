<?php

namespace Drupal\submission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;

class ConfirmForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['markup'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="submission-confirm">',
      '#suffix' => '</div>',
      '#markup' => $this->t('Uw inschrijving is bewaard. U ontvangt spoedig een bevestigingsmail. Hierin vind u ook de nodige informatie om uw inschrijving indien nodig aan te passen.'),
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
