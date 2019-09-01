<?php

namespace Drupal\submission\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a submission entity.
 *
 * @ingroup submission
 */
class SubmissionDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the contact list.
   */
  public function getCancelUrl() {
    return new Url('view.submission_details.page_1');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. logger() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();
    $this->logger('submission')->notice('deleted %title.',
      array(
        '%title' => $this->entity->label(),
      ));

    // Redirect to term list after delete.
    if (\Drupal::currentUser()->isAnonymous()) {
      $form_state->setRedirectUrl(Url::fromUri('internal:' . '/node/51'));
    }
    else {
      $form_state->setRedirect('view.submission_details.page_1');
    }
  }

}
