<?php

namespace Drupal\disclaimer;

/**
 * Class DisclaimerService.
 */
class DisclaimerService {

  /**
   * Constructs a new DisclaimerService object.
   */
  public function __construct(){}

  /**
   * {@inheritdoc}
   */
  public function getDisclaimerList(){
    $list = [];

    $ids = \Drupal::entityQuery('disclaimer')
      ->execute();
    if (!empty($ids)) {
      $storage = \Drupal::entityTypeManager()->getStorage('disclaimer');
      $disclaimers = $storage->loadMultiple($ids);

      if ($disclaimers) {
        foreach ($disclaimers as $disclaimer) {
          $list[$disclaimer->id()] = $disclaimer->label();
        }
      }
    }

    return $list;
  }

}
