<?php

namespace Drupal\omnipedia_content_legacy;

use Drupal\Core\Render\RendererInterface;

/**
 * Defines an interface for OmnipediaElementLegacy plug-in managers.
 */
interface OmnipediaElementLegacyManagerInterface {

  /**
   * Set additional dependencies.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   *
   * @see https://symfony.com/doc/3.4/service_container/parent_services.html#overriding-parent-dependencies
   */
  public function setAddtionalDependencies(
    RendererInterface $renderer
  ): void;

  /**
   * Convert all legacy Mustache elements into their corresponding HTML.
   *
   * @param string $html
   *   The HTML to parse.
   *
   * @return string
   *   The $html parameter with any Mustache elements that have
   *   OmnipediaElementLegacy plug-ins rendered as their corresponding HTML.
   */
  public function convertElements(string $html): string;

  /**
   * Get theme definitions from all legacy element plug-ins.
   *
   * @return array
   *   An array keyed by plug-in IDs, each containing an array with the
   *   following values:
   *
   *   - 'provider': The machine name of the provider, e.g. the module.
   *
   *   - 'theme': The theme array, containing 'variables' and 'template' keys.
   */
  public function getTheme(): array;

}
