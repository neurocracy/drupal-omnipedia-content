<?php

namespace Drupal\omnipedia_content_changes\Event\Omnipedia\Changes;

use Drupal\omnipedia_content_changes\Event\Omnipedia\Changes\AbstractDiffEvent;

/**
 * Omnipedia changes diff post-render pre-build event.
 *
 * This event is dispatched after the current and previous wiki node revisions
 * have been rendered, and before the diff is built, allowing alterations to
 * the markup sent to the diffing service.
 */
class DiffPostRenderPreBuildEvent extends AbstractDiffEvent {
}
