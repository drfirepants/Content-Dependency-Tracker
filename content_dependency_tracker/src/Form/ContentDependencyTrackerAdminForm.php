<?php

namespace Drupal\content_dependency_tracker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

class ContentDependencyTrackerAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['content_dependency_tracker.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'content_dependency_tracker_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('content_dependency_tracker.settings');

    // Retrieve all entity types with fields.
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $field_map = $entity_field_manager->getFieldMap();
    $entity_types = array_keys($field_map);

    foreach ($entity_types as $entity_type) {
      // Only include entity types that have fields.
      if (!empty($field_map[$entity_type])) {
        $form[$entity_type] = [
          '#type' => 'details',
          '#title' => $this->t('@entity_type fields', ['@entity_type' => $entity_type]),
          '#open' => FALSE,
        ];

        // Retrieve all fields for this entity type.
        foreach ($field_map[$entity_type] as $field_name => $field_info) {
          if ($field_info['type'] === 'entity_reference') {
            {
              $field_id = $entity_type . '.' . $field_name;
              $form[$entity_type][$field_id] = [
                '#type' => 'checkbox',
                '#title' => $this->t('@field_name', ['@field_name' => $field_name]),
                '#default_value' => $config->get('fields_to_track.' . $field_id) ?: 0,
              ];
            }
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();

    $this->configFactory->getEditable('content_dependency_tracker.settings')
      ->set('content_types', array_filter($values['content_types']))
      ->set('fields_to_track', $values['fields_to_track'])
      ->save();
  }
}
