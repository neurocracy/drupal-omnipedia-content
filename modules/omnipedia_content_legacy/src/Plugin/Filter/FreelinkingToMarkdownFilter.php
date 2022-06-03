<?php

namespace Drupal\omnipedia_content_legacy\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\omnipedia_content_legacy\Service\FreelinkingToMarkdown;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to convert legacy Freelinking links to Markdown.
 *
 * @Filter(
 *   id           = "omnipedia_freelinking_to_markdown",
 *   title        = @Translation("Omnipedia: convert legacy Freelinking links to Markdown"),
 *   description  = @Translation("This converts legacy Freelinking links to Markdown. This should be placed <strong>after</strong> the Freelinking filter in the processing order."),
 *   type         = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 *
 * @see \Drupal\freelinking\FreelinkingManagerInterface::createFreelinkElement()
 */
class FreelinkingToMarkdownFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The Omnipedia legacy Freelinking links to Markdown converter service.
   *
   * @var \Drupal\omnipedia_content_legacy\Service\FreelinkingToMarkdown
   */
  protected $freelinkingToMarkdown;

  /**
   * Constructs this filter object; saves dependencies.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginId
   *   The plugin_id for the plug-in instance.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition. PluginBase defines this as mixed,
   *   but we should always have an array so the type is set.
   *
   * @param \Drupal\omnipedia_content_legacy\Service\FreelinkingToMarkdown $freelinkingToMarkdown
   *   The Omnipedia legacy Freelinking links to Markdown converter service.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    FreelinkingToMarkdown $freelinkingToMarkdown
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->freelinkingToMarkdown = $freelinkingToMarkdown;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginId, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginId, $pluginDefinition,
      $container->get('omnipedia_content_legacy.freelinking_to_markdown')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langCode) {
    return new FilterProcessResult(
      $this->freelinkingToMarkdown->process($text)
    );
  }

}
