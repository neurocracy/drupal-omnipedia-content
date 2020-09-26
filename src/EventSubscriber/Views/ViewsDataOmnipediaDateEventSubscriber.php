<?php

namespace Drupal\omnipedia_content\EventSubscriber\Views;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\omnipedia_core\Entity\Node;
use Drupal\views_event_dispatcher\Event\Views\ViewsDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_views_data() event subscriber to define Omnipedia date plug-ins.
 *
 * @see \Drupal\omnipedia_content\Plugin\views\filter\OmnipediaDate
 *   Views plug-in that data is defined for.
 */
class ViewsDataOmnipediaDateEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    TranslationInterface $stringTranslation
  ) {
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::VIEWS_DATA => 'onViewsData',
    ];
  }

  /**
   * Defines the Omnipedia date views plug-ins.
   *
   * @param \Drupal\views_event_dispatcher\Event\Views\ViewsDataEvent $event
   *   The event object.
   */
  public function onViewsData(ViewsDataEvent $event) {
    /** @var string */
    $dateFieldName = Node::getWikiNodeDateFieldName();

    /** @var array */
    $data = [];

    // Top level key is the name of the database table.
    $data['node__' . $dateFieldName]['omnipedia_date_filter'] = [
      'title'   => $this->t('Wiki date'),
      'group'   => $this->t('Omnipedia'),
      'filter'  => [
        'title'   => $this->t('Wiki date'),
        // This is the help text shown on the plug-in options modal in the Views
        // UI.
        'help'    => $this->t('Filter by wiki node dates.'),
        // This is the column in the field's table that this plug-in operates
        // on.
        'field'   => $dateFieldName . '_value',
        // This is the @ViewsFilter() annotation value of our plug-in.
        'id'      => 'omnipedia_date'
      ],
    ];

    $event->addData($data);
  }

}
