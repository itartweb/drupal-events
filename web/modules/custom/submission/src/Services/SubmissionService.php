<?php

namespace Drupal\submission\Services;

/**
 * Class SubmissionService.
 */
class SubmissionService {

  /**
   * Constructs a new SubmissionService object.
   */
  public function __construct(){}

  /**
   * {@inheritdoc}
   */
  public function submissionEventExistsByEmail($event_id, $email) {
    $query = \Drupal::database()->select('submission', 's');
    $query->fields('s', array('submission_id'));
    $query->condition('s.event_id', $event_id);
    $query->condition('s.email', $email);
    $result = count($query->execute()->fetchAll());

    return !empty($result) ? TRUE : FALSE;
  }

}
