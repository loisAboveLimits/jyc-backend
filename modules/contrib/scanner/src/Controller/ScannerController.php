<?php

namespace Drupal\scanner\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for Search and Replace module.
 */
class ScannerController extends ControllerBase {

  /**
   * Class constructor.
   */
  public function __construct(protected DateFormatter $dateFormatter, protected Connection $database, protected RequestStack $requestStack) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('date.formatter'),
      $container->get('database'),
      $container->get('request_stack')
    );
  }

  /**
   * Queries the database and builds the results for the "Undo" listing.
   *
   * @return array
   *   A render array (table).
   */
  public function undoListing(): array {
    $sort_order = strtoupper((string) $this->requestStack->getCurrentRequest()->query->get('sort', 'asc'));
    if (!in_array($sort_order, ['ASC', 'DESC'])) {
      $sort_order = 'ASC';
    }
    $results = $this->database->select('scanner', 's')
      ->fields('s')
      ->condition('s.undone', 0)
      ->orderBy('s.time', $sort_order)
      ->execute()
      ->fetchAll();
    $header = [
      [
        'data' => $this->t('Date'),
        'sortable' => TRUE,
        'field' => 'date',
      ],
      $this->t('Searched'),
      $this->t('Replaced'),
      $this->t('Count'),
      $this->t('Operation'),
    ];
    $rows = [];
    // Build the rows of the table.
    foreach ($results as $result) {
      $undo_link = Link::fromTextAndUrl($this->t('Undo'), Url::fromUri("internal:/admin/content/scanner/undo/$result->undo_id/confirm"))->toString();
      $rows[$result->time] = [
        'date' => $this->dateFormatter->format($result->time),
        'searched' => $result->searched,
        'replaced' => $result->replaced,
        'count' => $result->count,
        'undo_link' => $undo_link,
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => NULL,
    ];
  }

}
