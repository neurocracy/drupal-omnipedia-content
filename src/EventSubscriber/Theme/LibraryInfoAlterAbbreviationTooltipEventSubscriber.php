<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\EventSubscriber\Theme;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\LibraryInfoAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * \hook_library_info_alter() abbreviation tooltip event subscriber.
 *
 * This adds 'omnipedia_content/component.omnipedia_tooltip' as a dependency to
 * 'ambientimpact_ux/component.abbr' to ensure our tooltip configuration is
 * applied.
 */
class LibraryInfoAlterAbbreviationTooltipEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::LIBRARY_INFO_ALTER => 'onLibraryInfoAlter',
    ];
  }

  /**
   * Alter library definitions.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\LibraryInfoAlterEvent $event
   *   The event object.
   */
  public function onLibraryInfoAlter(LibraryInfoAlterEvent $event) {

    /** @var array */
    $libraries = &$event->getLibraries();

    if (isset($libraries['component.abbr'])) {
      $libraries['component.abbr']['dependencies'][] =
        'omnipedia_content/component.omnipedia_tooltip';
    }

  }

}
