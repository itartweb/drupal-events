<?php

namespace Drupal\disclaimer\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a Disclaimer.
 *
 * @ingroup disclaimer
 */
class DisclaimerDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete Disclaimer %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the Disclaimer list.
   */
  public function getCancelUrl() {
    return new Url('entity.disclaimer.collection');
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

    $this->logger('disclaimer')->notice('deleted %title.',
      array(
        '%title' => $this->entity->label(),
      ));

    // Redirect to term list after delete.
    $form_state->setRedirect('entity.disclaimer.collection');
  }

}
