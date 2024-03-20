<?php

namespace Drupal\content_dependency_tracker\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\core_event_dispatcher\Event\Form\FormBaseAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\content_dependency_tracker\ReferenceFinder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class FormAlterSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  protected $referenceFinder;
  protected $configFactory;
  protected $entityTypeManager;

  public function __construct(ReferenceFinder $referenceFinder, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->referenceFinder = $referenceFinder;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function getSubscribedEvents(): array {
    return [
      'hook_event_dispatcher.form_base_node_form.alter' => 'alterEntityForm',
      'hook_event_dispatcher.form_base_media_form.alter' => 'alterEntityForm',
    ];
  }

  public function alterEntityForm(FormBaseAlterEvent $event): void {
    $form = &$event->getForm();
    $form_state = $event->getFormState();
    $entity = $form_state->getFormObject()->getEntity();
    $config = $this->configFactory->get('content_dependency_tracker.settings');

    $references = $this->referenceFinder->getReferencingEntities($entity);

    if (!empty($references)) {
      $items = [];
      foreach ($references as $referencing_entity_type => $fields) {
        foreach ($fields as $field_name => $ids) {
          foreach ($ids as $id) {
            $referencing_entity = $this->entityTypeManager->getStorage($referencing_entity_type)->load($id);
            if ($referencing_entity) {
              $url = Url::fromRoute("entity.$referencing_entity_type.canonical", ["$referencing_entity_type" => $id]);
              $link = Link::fromTextAndUrl($referencing_entity->label(), $url);
              $items[] = $link->toString();
            }
          }
        }
      }

      if (!empty($items)) {
        if ($config->get('show_warning')) {
          $form['actions']['delete_warning'] = [
            '#type' => 'markup',
            '#markup' => $this->t('Warning: This content is referenced by other content items. Deleting it may affect the integrity of your site.'),
            '#weight' => 1000,
          ];
        }

        $form['content_dependency_tracker_references'] = [
          '#type' => 'details',
          '#group' => "advanced",
          '#title' => $this->t('Referenced By'),
          '#open' => TRUE,
          '#weight' => 90,
          'items' => [
            '#theme' => 'item_list',
            '#items' => $items,
          ],
        ];
      }
    }
  }
}
