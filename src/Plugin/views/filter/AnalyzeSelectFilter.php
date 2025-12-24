<?php

namespace Drupal\analyze\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler providing a select list from database values.
 *
 * Supports three modes for getting options:
 * - options_callback: Call a service method to get options array.
 * - options_table: Query a separate table for id/label pairs.
 * - Fallback: Query distinct values from the current table/field.
 *
 * @ViewsFilter("analyze_select")
 */
final class AnalyzeSelectFilter extends InOperator {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, ContainerInterface $container) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions(): array {
    if ($this->valueOptions !== NULL) {
      return $this->valueOptions;
    }

    // Try options_callback first (service:method format).
    if (!empty($this->definition['options_callback'])) {
      $this->valueOptions = $this->getOptionsFromCallback();
      return $this->valueOptions;
    }

    // Try options_table with id/label columns.
    if (!empty($this->definition['options_table'])) {
      $this->valueOptions = $this->getOptionsFromTable();
      return $this->valueOptions;
    }

    // Fallback: query distinct values from current table/field.
    $this->valueOptions = $this->getOptionsFromDistinct();
    return $this->valueOptions;
  }

  /**
   * Get options from a service callback.
   *
   * @return array
   *   Options array keyed by id with label values.
   */
  protected function getOptionsFromCallback(): array {
    $callback = $this->definition['options_callback'];
    [$service_id, $method] = explode(':', $callback);

    $service = $this->container->get($service_id);
    if (method_exists($service, $method)) {
      return $service->$method();
    }

    return [];
  }

  /**
   * Get options from a separate database table.
   *
   * @return array
   *   Options array keyed by id with label values.
   */
  protected function getOptionsFromTable(): array {
    $table = $this->definition['options_table'];
    $id_column = $this->definition['options_id_column'] ?? 'id';
    $label_column = $this->definition['options_label_column'] ?? $id_column;

    $query = $this->database->select($table, 't')
      ->fields('t', [$id_column, $label_column])
      ->orderBy($label_column);

    // Add status filter if the table has a status column.
    if (!empty($this->definition['options_status_column'])) {
      $query->condition($this->definition['options_status_column'], 1);
    }

    $results = $query->execute()->fetchAllKeyed();

    return $results;
  }

  /**
   * Get options from distinct values in the current table.
   *
   * @return array
   *   Options array with same key and value.
   */
  protected function getOptionsFromDistinct(): array {
    $field = $this->realField;
    $table = $this->table;

    $values = $this->database->select($table, 't')
      ->fields('t', [$field])
      ->distinct()
      ->orderBy($field)
      ->execute()
      ->fetchCol();

    return array_combine($values, $values);
  }

}
