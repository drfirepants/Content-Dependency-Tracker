<?php

namespace Drupal\content_dependency_tracker\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\field\Entity\FieldStorageConfig;

class ContentDependencyTrackerAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_dependency_tracker.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_dependency_tracker_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('content_dependency_tracker.settings');

    $form['referencing_fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Referencing Fields'),
      '#open' => TRUE,
    ];

    $options = [];
    $default_values = [];
    $fields_to_track_config = $config->get('fields_to_track');
    $entity_reference_fields = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('entity_reference');

    foreach ($entity_reference_fields as $entity_type => $fields) {
      foreach ($fields as $field_name => $field_info) {
        $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);
        if ($field_storage) {
          $field_key = $entity_type . '.' . $field_name;
          $options[$field_key] = $this->t('@entity_type: @field_name', ['@entity_type' => $entity_type, '@field_name' => $field_name]);

          // Determine if this field should be checked by default.
          if (isset($fields_to_track_config[$entity_type][$field_name]) && $fields_to_track_config[$entity_type][$field_name] === true) {
            $default_values[] = $field_key;
          }
        }
      }
    }

    $form['referencing_fields']['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields to Track'),
      '#options' => $options,
      '#default_value' => $default_values,
      '#description' => $this->t('Select which fields you want to use to track dependencies. Leave blank for all.'),
    ];

    // Warning message settings section.
    $form['warning_message_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Show Warning Message on Nodes'),
      '#open' => TRUE,
    ];

    $form['warning_message_settings']['show_warning'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display warning message'),
      '#default_value' => $config->get('show_warning'),
      '#description' => $this->t('Enable to display a warning message on node edit forms when referenced entities are present.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    $config = $this->config('content_dependency_tracker.settings');

    $fields_to_track = [];
    foreach ($values['fields'] as $field_key => $value) {
      // Split the key to separate entity type and field name.
      list($entity_type, $field_name) = explode('.', $field_key, 2);

      if (!isset($fields_to_track[$entity_type])) {
        $fields_to_track[$entity_type] = [];
      }
      $fields_to_track[$entity_type][$field_name] = ($value === $field_key);
    }

    $config->set('fields_to_track', $fields_to_track);
    $config->set('show_warning', !empty($values['show_warning']));
    $config->save();
  }
}
