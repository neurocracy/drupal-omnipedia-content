<?php

namespace Drupal\omnipedia_content\EventSubscriber\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeEvent;
use Drupal\omnipedia_content\OmnipediaElementManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OmnipediaElement hook_theme() event subscriber.
 */
class ThemeOmnipediaElementEventSubscriber implements EventSubscriberInterface {

  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The OmnipediaElement plug-in manager.
   *
   * @var Drupal\omnipedia_content\OmnipediaElementManagerInterface
   */
  protected $elementManager;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   *
   * @param \Drupal\omnipedia_content\OmnipediaElementManagerInterface $elementManager
   *   The OmnipediaElement plug-in manager.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    OmnipediaElementManagerInterface $elementManager
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->elementManager = $elementManager;
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
   * Defines the OmnipediaElement theme elements.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function theme(ThemeEvent $event) {
    /** @var array */
    $plugins = $this->elementManager->getTheme();

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
