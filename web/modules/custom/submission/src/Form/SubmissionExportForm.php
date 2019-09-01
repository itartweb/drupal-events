<?php

namespace Drupal\submission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityStorageInterface;

class SubmissionExportForm extends FormBase {

  /**
   * Batch Builder.
   *
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  /**
   * Entity storage.
   */
  protected $entityStorage;

  /**
   * BatchForm constructor.
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
    $this->batchBuilder = new BatchBuilder();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('submission')
    );
  }

	/**
	* {@inheritdoc}
	*/
	public function getFormId(){
		return 'submission_export_form';
	}

  /**
   * Get Content Type List
   */
  public function getEvents(){
    $events = [];

    $entityQuery = \Drupal::entityQuery('event');
    $entityIds = $entityQuery->execute();

    $controller = \Drupal::entityTypeManager()->getStorage('event');
    $eventsObjects = $controller->loadMultiple($entityIds);

    foreach ($eventsObjects as $event) {
      $events[$event->id()] = $event->label();
    }

    return $events;
  }

	/**
   	* {@inheritdoc}
   	*/
	public function buildForm(array $form,FormStateInterface $form_state){
		$form['event_id'] = [
			'#title'=> $this->t('Events'),
			'#type'=> 'select',
			'#options'=> $this->getEvents()
		];

    $form['actions']['#type'] = 'actions';
    $form['actions']['export'] = [
      '#value'=> $this->t('Event Export'),
      '#type'=> 'submit',
      '#action' => 'event',
    ];

    $form['actions']['export_all'] = [
      '#value'=> $this->t('Export all Submissions'),
      '#type'=> 'submit',
      '#action' => 'all',
    ];

    return $form;
	}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getTriggeringElement()['#action'])
      && $form_state->getTriggeringElement()['#action'] == 'all') {

      $event_id = NULL;
    }
    else {
      $event_id = $form_state->getValue('event_id');
    }
    $submissions = $this->getSubmissions($event_id);

    $this->batchBuilder
      ->setTitle($this->t('Processing'))
      ->setInitMessage($this->t('Initializing.'))
      ->setProgressMessage($this->t('Completed @current of @total.'))
      ->setErrorMessage($this->t('An error has occurred.'));

    $this->batchBuilder->setFile(drupal_get_path('module', 'submission') . '/src/Form/SubmissionExportForm.php');
    $this->batchBuilder->addOperation([$this, 'processItems'], [$submissions]);
    $this->batchBuilder->setFinishCallback([$this, 'finished']);

    batch_set($this->batchBuilder->toArray());
  }

  /**
   * Processor for batch operations.
   */
  public function processItems($items, array &$context) {
    // Elements per operation.
    $limit = 1;

    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
    }

    // Save items to array which will be changed during processing.
    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $items;
    }

    $counter = 0;
    if (!empty($context['sandbox']['items'])) {
      // Remove already processed items.
      if ($context['sandbox']['progress'] != 0) {
        array_splice($context['sandbox']['items'], 0, $limit);
      }

      foreach ($context['sandbox']['items'] as $item) {
        if ($counter != $limit) {
          $submission = $this->entityStorage->load($item);
          $event_id = $submission->get('event_id')->getValue();
          if ($event_id && !empty($event_id[0]['target_id'])) {
            $event_id = $event_id[0]['target_id'];
            if ($this->hasEventById($event_id)) {
              $storage = \Drupal::entityTypeManager()->getStorage('event');
              $event = $storage->load($event_id);
              $context['results']['items'][] = implode(',', $this->getData($event, $submission));
            }
          }

          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing item :progress of :count', [
            ':progress' => $context['sandbox']['progress'],
            ':count' => $context['sandbox']['max'],
          ]);

          // Increment total processed item values. Will be used in finished
          // callback.
          $context['results']['processed'] = $context['sandbox']['progress'];
        }
      }
    }

    // If not finished all tasks, we count percentage of process. 1 = 100%.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finished callback for batch.
   */
  public function finished($success, $results, $operations) {
    $message = $this->t('Number of items affected by batch: @count', [
      '@count' => $results['processed'],
    ]);

    $this->messenger()->addStatus($message);

    $csvData = $results['items'];

    $private_path = PrivateStream::basepath();
    $public_path = PublicStream::basepath();
    $file_base = ($private_path) ? $private_path : $public_path;

    $filename = 'submission_export_'. time() . '.csv';
    $filepath = $file_base . '/' . $filename;
    $csvFile = fopen($filepath, "w");
    $header = implode(',' , $this->getHeader());
    fwrite($csvFile,$header . "\n");
    foreach($csvData as $csvDataRow) {
      fwrite($csvFile,$csvDataRow . "\n");
    }
    fclose($csvFile);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . basename($filepath) . '";');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    unlink($filepath);
    exit;
  }

  /**
   * Gets Valid Field List
   */
  public function getHeader() {
    return [
      $this->t('id'),
      $this->t('Event'),
      $this->t('Time of Submission'),
      $this->t('First Name'),
      $this->t('Last Name'),
      $this->t('Phone'),
      $this->t('E-mail Address'),
    ];
  }

  /**
   * Load all nids for specific type.
   *
   * @return array
   *   An array with nids.
   */
  public function getSubmissions($event_id = NULL) {
    $query = $this->entityStorage->getQuery();

    if ($event_id) {
      $query->condition('event_id', $event_id, '=');
    }

    return $query->execute();
  }

  /**
   * Check the event by ID.
   */
  private function hasEventById($event_id) {
    $entityQuery = \Drupal::entityQuery('event');
    $entityQuery->condition('event_id', $event_id);

    return $entityQuery->execute();
  }

  /**
   * Gets Manipulated Node Data
   */
  private function getData($event, $submission) {
    $data = [];

    try {
      $data[] = '"' . strip_tags($submission->id()) . '"';

      $data[] = '"' . strip_tags($event->label()) . '"';

      $timestamp = $submission->get('created')->first()->getValue()['value'];
      $date = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'd.m.Y H:i');
      $data[] = '"' . strip_tags($date) . '"';

      $data[] = '"' . strip_tags($submission->firstname->value) . '"';

      $data[] = '"' . strip_tags($submission->lastname->value) . '"';

      $data[] = '"' . strip_tags($submission->phone->value) . '"';

      $data[] = '"' . strip_tags($submission->email->value) . '"';
    }
    catch (\Exception $e) {
      // do nothing.
    }

    return $data;
  }

}
