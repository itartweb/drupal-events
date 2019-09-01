<?php

namespace Drupal\submission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\node\NodeStorageInterface;
use Drupal\node\NodeInterface;

class TrainingExportForm extends FormBase {

  /**
   * Batch Builder.
   *
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  /**
   * Node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * BatchForm constructor.
   */
  public function __construct(NodeStorageInterface $node_storage) {
    $this->nodeStorage = $node_storage;
    $this->batchBuilder = new BatchBuilder();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('node')
    );
  }

	/**
	* {@inheritdoc}
	*/
	public function getFormId(){
		return 'training_export_form';
	}

	/**
   	* {@inheritdoc}
   	*/
	public function buildForm(array $form,FormStateInterface $form_state){
		$form['export'] = [
			'#value'=> $this->t('Training Export'),
			'#type'=> 'submit' 
		]; 

		return $form;
	}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nodes = $this->getNodes('training');

    $this->batchBuilder
      ->setTitle($this->t('Processing'))
      ->setInitMessage($this->t('Initializing.'))
      ->setProgressMessage($this->t('Completed @current of @total.'))
      ->setErrorMessage($this->t('An error has occurred.'));

    $this->batchBuilder->setFile(drupal_get_path('module', 'submission') . '/src/Form/TrainingExportForm.php');
    $this->batchBuilder->addOperation([$this, 'processItems'], [$nodes]);
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
          $context['results']['items'][] = $this->processItem($item);

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
   * Process single item.
   *
   * @param int|string $nid
   *   An id of Node.
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   An object with new published date.
   */
  public function processItem($nid) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->load($nid);
    return implode(',', $this->getData($node));
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

		$filename = 'training_export_'. time() . '.csv';
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
      $this->t('Country - Language'),
      $this->t('Brand'),
      $this->t('Training'),
      $this->t('Dates'),
      $this->t('Market'),
      $this->t('Inscriptions'),
      $this->t('Capacity'),
      $this->t('%'),
    ];
  }

  /**
   * Load all nids for specific type.
   *
   * @return array
   *   An array with nids.
   */
  public function getNodes($type) {
    return $this->nodeStorage->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', $type)
      ->execute();
  }

  /**
   * Gets Manipulated Node Data
   */
  private function getData($nodeObject) {
    $nodeData = [];

    try {
      $nodeData[] = '"' . strip_tags($nodeObject->nid->value) . '"';

      $term = $nodeObject->get('field_country_lang')->referencedEntities();
      $nodeData[] = '"' . strip_tags($term[0]->label()) . '"';

      $term = $nodeObject->get('field_brand')->referencedEntities();
      $nodeData[] = '"' . strip_tags($term[0]->label()) . '"';

      $nodeData[] = '"' . strip_tags($nodeObject->field_public_title->value) . '"';

      $timestamp = strtotime($nodeObject->field_timeslot->value);
      $date = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'd.m.Y H:i');
      $nodeData[] = '"' . strip_tags($date) . '"';

      $term = $nodeObject->get('field_training_type')->referencedEntities();
      $nodeData[] = '"' . strip_tags($term[0]->label()) . '"';

      $field_number_participants = $nodeObject->field_number_participants->value;
      $nodeData[] = '"' . strip_tags($field_number_participants) . '"';

      $field_maximum_participants = $nodeObject->field_maximum_participants->value;
      $nodeData[] = '"' . strip_tags($field_maximum_participants) . '"';

      $percent = round(($field_number_participants / $field_maximum_participants) * 100) . '%';
      $nodeData[] = '"' . $percent . '"';
    }
    catch (\Exception $e) {
      // do nothing.
    }

    return $nodeData;
  }

}
