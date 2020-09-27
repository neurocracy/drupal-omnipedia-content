<?php

namespace Drupal\omnipedia_content;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Defines an interface for OmnipediaElement plug-in managers.
 */
interface OmnipediaElementManagerInterface {

  /**
   * Set additional dependencies.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Drupal messenger service.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   *
   * @see https://symfony.com/doc/3.4/service_container/parent_services.html#overriding-parent-dependencies
   */
  public function setAddtionalDependencies(
    MessengerInterface    $messenger,
    RendererInterface     $renderer,
    TranslationInterface  $stringTranslation
  ): void;

  /**
   * Convert all elements that have element plug-ins into standard HTML.
   *
   * @param string $html
   *   The HTML to parse.
   *
   * @param array $ignorePlugins
   *   An array of zero or more plug-in IDs to skip creating instances for. This
   *   is intended for internal use to prevent infinite recursion.
   *
   * @return string
   *   The $html parameter with any custom elements that have OmnipediaElement
   *   plug-ins rendered as standard HTML.
   */
  public function convertElements(
    string $html, array $ignorePlugins = []
  ): string;

  /**
   * Get theme definitions from all element plug-ins.
   *
   * @return array
   *   An array keyed by plug-in IDs, each containing an array with the
   *   following values:
   *
   *   - 'provider': The machine name of the provider, e.g. the module.
   *
   *   - 'theme': The theme array, containing elements for \hook_theme().
   */
  public function getTheme(): array;

  /**
   * Get all logged element errors.
   *
   * @return array
   *   An array of element errors.
   *
   * @see \Drupal\omnipedia_content\OmnipediaElementManager::elementErrors
   *   Describes the errror structure.
   */
  public function getElementErrors(): array;

}
