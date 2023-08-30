<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\EventSubscriber\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeEvent;
use Drupal\core_event_dispatcher\ThemeHookEvents;
use Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OmnipediaElement hook_theme() event subscriber.
 */
class ThemeOmnipediaElementEventSubscriber implements EventSubscriberInterface {

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   *
   * @param \Drupal\omnipedia_content\PluginManager\OmnipediaElementManagerInterface $elementManager
   *   The OmnipediaElement plug-in manager.
   */
  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly OmnipediaElementManagerInterface $elementManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ThemeHookEvents::THEME => 'theme',
    ];
  }

  /**
   * Defines the OmnipediaElement theme elements.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function theme(ThemeEvent $event): void {
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
