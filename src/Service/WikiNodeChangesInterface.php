<?php

namespace Drupal\omnipedia_content\Service;

use Drupal\omnipedia_core\Entity\NodeInterface;

/**
 * The Omnipedia wiki node changes service interface.
 */
interface WikiNodeChangesInterface {

  /**
   * Get the base class used to generate HTML/CSS BEM classes.
   *
   * @return string
   */
  public function getChangesBaseClass(): string;

  /**
   * Build changes content for a wiki node.
   *
   * This renders the current revision node and the previous revision node,
   * generates the diff via the HTML Diff service that the Diff module provides,
   * and alters/adjust the output as follows before returning it.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   A render array containing the changes content for the provided wiki node.
   */
  public function build(NodeInterface $node): array;

}
