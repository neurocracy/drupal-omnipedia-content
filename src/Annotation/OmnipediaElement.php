<?php

namespace Drupal\omnipedia_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an OmnipediaElement annotation object.
 *
 * @see \Drupal\omnipedia_content\OmnipediaElementManagerInterface
 *
 * @see plugin_api
 *
 * @Annotation
 */
class OmnipediaElement extends Plugin {

  /**
   * The HTML element name for this plug-in.
   *
   * For example, if the element is "<my-element></my-element>", the element
   * name would be "my-element".
   *
   * @var string
   */
  public $html_element;

  /**
   * The human readable title of the element.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * A brief human readable description of the element.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
