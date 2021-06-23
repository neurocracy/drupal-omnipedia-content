<?php

namespace Drupal\omnipedia_content\Utility;

/**
 * Table of contents HTML classes trait.
 *
 * This provides methods to retrieve all BEM-style HTML classes used for the
 * table of contents elements.
 */
trait TableOfContentsHtmlClassesTrait {

  /**
   * Get the table of contents base class used to generate BEM classes.
   *
   * @return string
   */
  public function getTableOfContentsBaseClass(): string {
    return 'table-of-contents';
  }

  /**
   * Get the table of contents heading element BEM class.
   *
   * @return string
   */
  public function getTableOfContentsHeadingClass(): string {
    return $this->getTableOfContentsBaseClass() . '__heading';
  }

  /**
   * Get the table of contents list element BEM class.
   *
   * @return string
   */
  public function getTableOfContentsListClass(): string {
    return $this->getTableOfContentsBaseClass() . '__list';
  }

}
