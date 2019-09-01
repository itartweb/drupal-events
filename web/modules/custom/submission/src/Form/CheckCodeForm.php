<?php

namespace Drupal\submission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\submission\Entity\Submission;
use Drupal\Core\Access\AccessResult;

class CheckCodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_form_by_code';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['unique_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Beheer hier uw inschrijving en geef uw unieke nummer in:'),
      '#required' => TRUE,
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $code = $form_state->getValue('unique_code');

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

    $submission_id = Submission::getSubmissionIdByCode($code);
    if ($submission_id) {
      $submission = \Drupal::entityManager()->getStorage('submission')->load($submission_id);
      $training_id = $submission->training_id->referencedEntities()[0]->id();
      if ($training_id) {
        $form_state->setRedirectUrl(Url::fromUri('internal:' . '/training/' . $training_id . '/submission/' . $submission_id . '/edit/1'));
      }
    }
    else {
      \Drupal::messenger()->addMessage(t('An error occurred and processing did not complete.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess($operation) {
    return AccessResult::allowed();
  }

}
