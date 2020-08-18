<?php

namespace Drupal\omnipedia_content;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\omnipedia_content\OmnipediaElementInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for implementing OmnipediaElement plug-ins.
 */
abstract class OmnipediaElementBase extends PluginBase implements ContainerFactoryPluginInterface, OmnipediaElementInterface {

  use StringTranslationTrait;

  /**
   * The DOM elements this plug-in instance is to parse and render.
   *
   * @var \Symfony\Component\DomCrawler\Crawler
   */
  protected $elements;

  /**
   * Any errors logged by this plug-in.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * Constructs an OmnipediaElementBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plug-in instance.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition. PluginBase defines this as mixed,
   *   but we should always have an array so the type is specified.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    TranslationInterface $stringTranslation
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->stringTranslation  = $stringTranslation;

    // Pass on the Symfony DomCrawler instance provided by our plug-in manager.
    $this->elements = $configuration['elements'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlElementName(): string {
    return $this->getPluginDefinition()['html_element'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasErrors(): bool {
    return \count($this->errors) > 0;
  }

  /**
   * Set an error for this element.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $error
   *   An error message as a TranslatableMarkup instance. Use $this->t() to
   *   generate this.
   */
  protected function setError(TranslatableMarkup $error): void {
    $this->errors[] = $error;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors(): array {
    return $this->errors;
  }

}
