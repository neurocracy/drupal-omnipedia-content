<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\CommonMark\Extension\Attributes;

use League\CommonMark\ElementRendererInterface;
use League\CommonMark\Extension\Attributes\Node\AttributesInline;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;

class AttributesInlineRenderer implements InlineRendererInterface {

  /**
   * {@inheritdoc}
   */
  public function render(
    AbstractInline $inline, ElementRendererInterface $htmlRenderer,
  ) {

    if (!($inline instanceof AttributesInline)) {

      throw new \InvalidArgumentException(
        'Incompatible inline type: ' . \get_class($inline),
      );

    }

    return '';

  }

}
