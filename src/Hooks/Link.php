<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\Hooks;

use Drupal\hux\Attribute\Alter;

/**
 * Link element hook implementations.
 */
class Link {

  #[Alter('link')]
  /**
   * Implements \hook_link_alter().
   *
   * The Login Destination module adds a 'current' query item with the
   * unaliased path of the current route, but it doesn't seem to be needed for
   * our use-case, clutters up URLs, and is sometimes incorrect due to
   * caching. For those reasons and since it doesn't seem like this query item
   * is used by anything in Drupal core or the contrib modules we use, we just
   * unset it.
   *
   * @see https://www.drupal.org/project/login_destination/issues/3097721
   *   Open issue regarding the caching.
   *
   * @see https://www.drupal.org/project/login_destination/issues/3227126
   *   Feature request to conditionally add the 'current' query item only if one
   *   or more redirect destinations configured to the current page.
   */
  public function alter(array &$variables): void {
    unset($variables['options']['query']['current']);
  }

}
