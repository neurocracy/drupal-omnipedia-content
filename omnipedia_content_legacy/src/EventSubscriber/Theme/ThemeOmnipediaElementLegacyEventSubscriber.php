<?php

namespace Drupal\omnipedia_content_legacy\EventSubscriber\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeEvent;
use Drupal\omnipedia_content_legacy\OmnipediaElementLegacyManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OmnipediaElementLegacy hook_theme() event subscriber.
 */
class ThemeOmnipediaElementLegacyEventSubscriber implements EventSubscriberInterface {

  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The OmnipediaElementLegacy plug-in manager.
   *
   * @var \Drupal\omnipedia_content_legacy\OmnipediaElementLegacyManagerInterface
   */
  protected $elementLegacyManager;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   *
   * @param \Drupal\omnipedia_content_legacy\OmnipediaElementLegacyManagerInterface $elementLegacyManager
   *   The OmnipediaElementLegacy plug-in manager.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    OmnipediaElementLegacyManagerInterface $elementLegacyManager
  ) {
    $this->moduleHandler        = $moduleHandler;
    $this->elementLegacyManager = $elementLegacyManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::THEME => 'theme',
    ];
  }

  /**
   * Defines the OmnipediaElementLegacy theme elements.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function theme(ThemeEvent $event) {
    /** @var array */
    $plugins = $this->elementLegacyManager->getTheme();

    foreach ($plugins as $pluginId => $pluginData) {
      foreach ($pluginData['theme'] as $elementName => $elementData) {
        // If the 'path' key isn't set, we have to build it as
        // hook_event_dispatcher requires this.
        //
        // @see https://www.drupal.org/project/hook_event_dispatcher/issues/3038311
        //
        // @todo What about themes and other paths? Can these be specified in
        //   the individual element classes?
        if (empty($elementData['path'])) {
          $elementData['path'] = $this->moduleHandler
            ->getModule($pluginData['provider'])->getPath()
             . '/templates/omnipedia';
        }

        $event->addNewTheme($elementName, $elementData);
      }
    }
  }

}
