<?php

namespace Drupal\content_dependency_tracker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldStorageConfig;

class ReferenceFinder {

  protected $entityTypeManager;
  protected $entityFieldManager;
  protected $configFactory;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->configFactory = $configFactory;
  }

  public function getReferencingEntities(EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $map = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    $results = [];

    // Use the getFieldsFromConfig method to get the list of fields to consider.
    $node_fields = $this->getFieldsFromConfig();

    foreach ($map as $referring_entity_type => $fields) {
      // If the referring entity type is not 'node', skip the iteration.
      if ($referring_entity_type !== 'node') {
        continue;
      }

      foreach ($fields as $field_name => $field_info) {
        // If the field name is not in the list of node fields from getFieldsFromConfig, skip it.
        if (!in_array($field_name, $node_fields)) {
          continue;
        }

        // Load field storage configuration to check target entity type.
        $config = FieldStorageConfig::loadByName($referring_entity_type, $field_name);

        // Check if the field is configured to target the given entity type.
        if ($config && $config->getSetting('target_type') == $entity_type_id) {
          // Query for entities of the referring type that reference the given entity via this field.
          $ids = $this->entityTypeManager->getStorage($referring_entity_type)
            ->getQuery()
            ->condition($field_name, $entity->id())
            ->accessCheck(TRUE)
            ->execute();

          // Add the results to the array, keyed by entity type and field name.
          if (!empty($ids)) {
            $results[$referring_entity_type][$field_name] = $ids;
          }
        }
      }
    }

    return $results;
  }

  public function getFieldsFromConfig() {
    $config = \Drupal::config('content_dependency_tracker.settings');
    $fields_to_track_config = $config->get('fields_to_track.node') ?: [];

    // Initialize an array to hold the field names.
    $fields_to_use = [];

    // If specific fields are selected, use those; otherwise, use all fields listed in config'.
    foreach ($fields_to_track_config as $field_name => $enabled) {
      if ($enabled) {
        $fields_to_use[] = $field_name;
      }
    }

    // If no specific fields are selected, use all fields listed.
    if (empty($fields_to_use)) {
      $fields_to_use = array_keys($fields_to_track_config);
    }

    return $fields_to_use;
  }
}
