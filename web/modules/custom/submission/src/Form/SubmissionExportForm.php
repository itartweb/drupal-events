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
  public function getTrainings(){
    $trainings = [];

    $entityQuery = \Drupal::entityQuery('node');
    $entityQuery->condition('status',1);
    $entityQuery->condition('type', 'training');
    $entityIds = $entityQuery->execute();

    $nodes_controller = \Drupal::entityTypeManager()->getStorage('node');
    $traningObjects = $nodes_controller->loadMultiple($entityIds);

    foreach ($traningObjects as $training) {
      $trainings[$training->id()] = $training->label();
    }

    return $trainings;
  }

	/**
   	* {@inheritdoc}
   	*/
	public function buildForm(array $form,FormStateInterface $form_state){
		$form['training_id'] = [
			'#title'=> $this->t('Trainings'),
			'#type'=> 'select',
			'#options'=> $this->getTrainings()
		];

    $form['actions']['#type'] = 'actions';
    $form['actions']['export'] = [
      '#value'=> $this->t('Training Export'),
      '#type'=> 'submit',
      '#action' => 'training',
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

      $training_id = NULL;
    }
    else {
      $training_id = $form_state->getValue('training_id');
    }
    $submissions = $this->getSubmissions($training_id);

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
          $training_id = $submission->get('training_id')->getValue();
          if ($training_id && !empty($training_id[0]['target_id'])) {
            $training_id = $training_id[0]['target_id'];
            if ($this->hasTrainingByNid($training_id)) {
              $storage = \Drupal::entityTypeManager()->getStorage('node');
              $training = $storage->load($training_id);
              $members = $submission->get('field_members')->getValue();
              if ($members) {
                foreach ($members as $member) {
                  $paragraph = \Drupal::service('entity.manager')->getStorage('paragraph')->load($member['target_id']);
                  $context['results']['items'][] = implode(',', $this->getData($paragraph, $submission, $training));
                }
              }
            }
          }

          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing node :progress of :count', [
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
    $message = $this->t('Number of nodes affected by batch: @count', [
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
      $this->t('Time of Submission'),
      $this->t('Country - Language'),
      $this->t('Store'),
      $this->t('Brand'),
      $this->t('Training'),
      $this->t('Dates'),
      $this->t('Store Name'),
      $this->t('Your Store Address'),
      $this->t('E-mail Address'),
      $this->t('Participant E-mail Address'),
      $this->t('First Name'),
      $this->t('Last Name'),
    ];
  }

  /**
   * Load all nids for specific type.
   *
   * @return array
   *   An array with nids.
   */
  public function getSubmissions($training_id = NULL) {
    $query = $this->entityStorage->getQuery();

    if ($training_id) {
      $query->condition('training_id', $training_id, '=');
    }

    return $query->execute();
  }

  /**
   * Check the training by NID.
   */
  private function hasTrainingByNid($training_id) {
    $entityQuery = \Drupal::entityQuery('node');
    $entityQuery->condition('status',1);
    $entityQuery->condition('type', 'training');
    $entityQuery->condition('nid', $training_id);

    return $entityQuery->execute();
  }

  /**
   * Gets Manipulated Node Data
   */
  private function getData($paragraph, $submission, $training) {
    $data = [];

    try {
      $data[] = '"' . strip_tags($submission->id()) . '"';
      $timestamp = $submission->get('created')->first()->getValue()['value'];
      $date = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'd.m.Y H:i');
      $data[] = '"' . strip_tags($date) . '"';

      $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

      if (!empty($submission->get('country_id')->first()->getValue()['target_id'])) {
        $term = $storage->load($submission->get('country_id')->first()->getValue()['target_id']);
        if ($term) {
          $data[] = '"' . strip_tags($term->label()) . '"';
        }
        else {
          $data[] = '""';
        }
      }
      else {
        $data[] = '""';
      }

      if (!empty($submission->get('retailer_id')) && !empty($submission->get('retailer_id')->first()) && !empty($submission->get('retailer_id')->first()->getValue()['target_id'])) {
        $term = $storage->load($submission->get('retailer_id')->first()->getValue()['target_id']);
        if ($term) {
          $data[] = '"' . strip_tags($term->label()) . '"';
        }
        else {
          $data[] = '""';
        }
      }
      else {
        $data[] = '""';
      }

      $term = $training->get('field_brand')->referencedEntities();
      $data[] = '"' . strip_tags($term[0]->label()) . '"';

      $data[] = '"' . strip_tags($training->field_public_title->value) . '"';

      $timestamp = strtotime($training->field_timeslot->value);
      $date = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'd.m.Y H:i');
      $data[] = '"' . strip_tags($date) . '"';

      $data[] = '"' . strip_tags($submission->store_department->value) . '"';

      $data[] = '"' . strip_tags($submission->store_address->value) . '"';

      $data[] = '"' . strip_tags($submission->email->value) . '"';

      $data[] = '"' . strip_tags($paragraph->field_email->value) . '"';

      $data[] = '"' . strip_tags($paragraph->field_name->value) . '"';

      $data[] = '"' . strip_tags($paragraph->field_lastname->value) . '"';
    }
    catch (\Exception $e) {
      // do nothing.
    }

    return $data;
  }

}
