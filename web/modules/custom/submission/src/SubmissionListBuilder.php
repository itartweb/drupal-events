<?php

namespace Drupal\submission;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for the Submission entity.
 */
class SubmissionListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new IndividualListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];

    $header['title'] = $this->t('Title');
    $header['name'] = $this->t('Name');
    $header['email'] = $this->t('Email');
    $header['phone'] = $this->t('Phone');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $entity->toUrl(),
    ];

    $row['name'] = $this->getFieldValue($entity, 'firstname') . ' ' . $this->getFieldValue($entity, 'lastname');
    $row['email'] = $this->getFieldValue($entity, 'email');
    $row['phone'] = $this->getFieldValue($entity, 'phone');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t('There are no submissions available. Add one now.');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  private function getFieldValue($entity, $field_name) {
    $value = '';

    $item = $entity->get($field_name)->first();
    if (!empty($item) && !empty($item->getValue())) {
      $value = $item->getValue()['value'];
    }

    return $value;
  }

}
