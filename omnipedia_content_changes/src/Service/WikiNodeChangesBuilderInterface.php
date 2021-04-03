<?php

namespace Drupal\omnipedia_content_changes\Service;

use Drupal\omnipedia_core\Entity\NodeInterface;

/**
 * The Omnipedia wiki node changes builder service interface.
 */
interface WikiNodeChangesBuilderInterface {

  /**
   * Build changes content for a wiki node.
   *
   * This renders the current revision node and the previous revision node,
   * generates the diff via the HTML Diff service that the Diff module provides,
   * and alters/adjust the output as follows before returning it.
   *
   * Note that this doesn't do any access checking, so code that calls this is
   * responsible for not displaying information about nodes the user does not
   * have access to.
   *
   * @param \Drupal\omnipedia_core\Entity\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   A render array containing the changes content for the provided wiki node.
   */
  public function build(NodeInterface $node): array;

}
