<?php

namespace Drupal\event\Controller;

use Drupal\Core\Controller\ControllerBase;

class EventController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function addEvent() {
    $form = \Drupal::formBuilder()->getForm('Drupal\event\Form\EventAddForm');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function editEvent($event) {
    $form = \Drupal::formBuilder()->getForm('Drupal\event\Form\EventEditForm', $event);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEvent($event) {
    $form = \Drupal::formBuilder()->getForm('Drupal\event\Form\EventDeleteForm', $event);

    return $form;
  }

}
