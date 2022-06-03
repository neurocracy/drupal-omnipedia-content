<?php

namespace Drupal\omnipedia_content_legacy\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\omnipedia_content_legacy\Service\MarkdownPreparer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to prepare content for Markdown.
 *
 * @Filter(
 *   id           = "omnipedia_markdown_preparer",
 *   title        = @Translation("Omnipedia: prepare legacy content for Markdown"),
 *   description  = @Translation("This prepares legacy legacy content so that it works with Markdown. This should be placed <strong>before</strong> the Markdown filter in the processing order."),
 *   type         = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class MarkdownPreparerFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The Omnipedia legacy Markdown preparer service.
   *
   * @var \Drupal\omnipedia_content_legacy\Service\MarkdownPreparer
   */
  protected $markdownPreparer;

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
   * @param \Drupal\omnipedia_content_legacy\Service\MarkdownPreparer $markdownPreparer
   *   The Omnipedia legacy Markdown preparer service.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    MarkdownPreparer $markdownPreparer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->markdownPreparer = $markdownPreparer;
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
      $container->get('omnipedia_content_legacy.markdown_preparer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langCode) {
    return new FilterProcessResult(
      $this->markdownPreparer->process($text)
    );
  }

}
