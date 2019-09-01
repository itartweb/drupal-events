<?php

namespace Drupal\submission\Plugin\Block;

use Drupal\Core\Block\BlockBase;


/**
 * Provides a 'SubmissionTopSectionBlock' block.
 *
 * @Block(
 *  id = "submission_top_section",
 *  admin_label = @Translation("Submission Top Section"),
 * )
 */
class SubmissionTopSectionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build($preselect = array()) {
    $parameters = \Drupal::service('current_route_match');
    $rout_name = $parameters->getRouteName();
    $nid = $parameters->getParameter('training');
    $build = [];
    if ($rout_name === 'submission.edit_form' || $rout_name === 'submission.add_form' || $rout_name === 'submission.confirm_form') {
      if (is_string($nid)) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($parameters->getParameter('training'));
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
        $node_view = $view_builder->view($node, 'subscription');
        $build['#markup'] = render($node_view);
      }
      else {
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
        $node_view = $view_builder->view($nid, 'subscription');
        $build['#markup'] = render($node_view);
      }
    }

    return $build;
  }

  /**
   * Turn off caching for this block.
   *
   * @return int
   *   Caching number.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
