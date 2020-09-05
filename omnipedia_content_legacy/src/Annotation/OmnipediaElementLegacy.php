<?php

namespace Drupal\omnipedia_content_legacy\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an OmnipediaElementLegacy annotation object.
 *
 * Note that the ID for an OmnipediaElementLegacy plug-in must match that of its
 * corresponding OmnipediaElement plug-in and also that of the legacy tag it
 * must process.
 *
 * @see \Drupal\omnipedia_content_legacy\OmnipediaElementLegacyManagerInterface
 *
 * @see plugin_api
 *
 * @Annotation
 */
class OmnipediaElementLegacy extends Plugin {

  /**
   * The human readable title of the element.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
