<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\CommonMark\Block\Element;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * Indented content CommonMark element.
 *
 * @see \Drupal\omnipedia_content\EventSubscriber\Markdown\CommonMark\IndentedContentEventSubscriber
 *   Explains the purpose of this element.
 */
class IndentedContent extends AbstractBlock {}
