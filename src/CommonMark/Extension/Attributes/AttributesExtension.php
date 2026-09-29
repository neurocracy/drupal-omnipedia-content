<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content\CommonMark\Extension\Attributes;

use Drupal\omnipedia_content\CommonMark\Extension\Attributes\AttributesInlineRenderer;
use Drupal\omnipedia_content\CommonMark\Extension\Attributes\AttributesBlockRenderer;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\Attributes\Event\AttributesListener;
use League\CommonMark\Extension\Attributes\Node\Attributes;
use League\CommonMark\Extension\Attributes\Node\AttributesInline;
use League\CommonMark\Extension\Attributes\Parser\AttributesBlockParser;
use League\CommonMark\Extension\Attributes\Parser\AttributesInlineParser;
use League\CommonMark\Extension\ExtensionInterface;

class AttributesExtension implements ExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ConfigurableEnvironmentInterface $environment) {

    $environment->addBlockParser(new AttributesBlockParser());
    $environment->addInlineParser(new AttributesInlineParser());
    $environment->addEventListener(
      DocumentParsedEvent::class,
      [new AttributesListener(), 'processDocument'],
    );

    $environment->addInlineRenderer(
      AttributesInline::class, new AttributesInlineRenderer(),
    );

    $environment->addBlockRenderer(
      Attributes::class, new AttributesBlockRenderer(),
    );

  }

}
