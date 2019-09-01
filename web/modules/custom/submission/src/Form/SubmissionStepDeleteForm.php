<?php

namespace Drupal\submission\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a submission entity.
 *
 * @ingroup submission
 */
class SubmissionStepDeleteForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'submission_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete submission?');
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the contact list.
   */
  public function getCancelUrl() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $training = NULL, $submission = NULL) {
    $form = parent::buildForm($form, $form_state);

    $form['training'] = [
      '#type' => 'value',
      '#value' => $training,
    ];

    $form['submission'] = [
      '#type' => 'value',
      '#value' => $submission,
    ];

    $training_id = $training->id();
    $submission_id = $submission->id();
    $url = Url::fromUri('internal:' . '/training/' . $training_id . '/submission/' . $submission_id . '/edit/1');
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $url,
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $training = $form_state->getValue('training');
    $training_id = $training->id();

    $submission = $form_state->getValue('submission');
    $submission->delete();

    if ($training_id) {
      $form_state->setRedirectUrl(Url::fromUri('internal:' . '/node/' . $training_id));
    }
  }

}
