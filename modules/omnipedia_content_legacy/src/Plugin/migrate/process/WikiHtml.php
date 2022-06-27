<?php

declare(strict_types=1);

namespace Drupal\omnipedia_content_legacy\Plugin\migrate\process;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\omnipedia_content_legacy\PluginManager\OmnipediaElementLegacyManagerInterface;
use Drupal\omnipedia_content_legacy\Service\FreelinkingToMarkdown;
use Drupal\omnipedia_content_legacy\Service\MarkdownPreparer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a process plug-in to convert the wiki_html filter format.
 *
 * This applies the legacy filters to fields that use the wiki_html filter
 * format during the migration step, so that the legacy filters can be
 * uninstalled along with this module.
 *
 * Note that once the Freelinking filter is uninstalled, this plug-in will cease
 * to work.
 *
 * @MigrateProcessPlugin(
 *   id = "wiki_html"
 * )
 */
class WikiHtml extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The OmnipediaElementLegacy plug-in manager.
   *
   * @var \Drupal\omnipedia_content_legacy\PluginManager\OmnipediaElementLegacyManagerInterface
   */
  protected $elementLegacyManager;

  /**
   * The Drupal filter format config entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $filterFormatStorage;

  /**
   * The Omnipedia legacy Freelinking links to Markdown converter service.
   *
   * @var \Drupal\omnipedia_content_legacy\Service\FreelinkingToMarkdown
   */
  protected $freelinkingToMarkdown;

  /**
   * The Omnipedia legacy Markdown preparer service.
   *
   * @var \Drupal\omnipedia_content_legacy\Service\MarkdownPreparer
   */
  protected $markdownPreparer;

  /**
   * The Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Freelinking filter plug-in instance for the wiki_html filter format.
   *
   * @var \Drupal\freelinking\Plugin\Filter\Freelinking|null
   */
  protected $freelinkingFilterInstance;

  /**
   * Constructs this process plug-in; saves dependencies.
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
   * @param \Drupal\omnipedia_content_legacy\PluginManager\OmnipediaElementLegacyManagerInterface $elementLegacyManager
   *   The OmnipediaElementLegacy plug-in manager.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $filterFormatStorage
   *   The Drupal filter format config entity storage.
   *
   * @param \Drupal\omnipedia_content_legacy\Service\FreelinkingToMarkdown $freelinkingToMarkdown
   *   The Omnipedia legacy Freelinking links to Markdown converter service.
   *
   * @param \Drupal\omnipedia_content_legacy\Service\MarkdownPreparer $markdownPreparer
   *   The Omnipedia legacy Markdown preparer service.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    OmnipediaElementLegacyManagerInterface $elementLegacyManager,
    ConfigEntityStorageInterface  $filterFormatStorage,
    FreelinkingToMarkdown         $freelinkingToMarkdown,
    MarkdownPreparer              $markdownPreparer,
    RendererInterface             $renderer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->elementLegacyManager   = $elementLegacyManager;
    $this->filterFormatStorage    = $filterFormatStorage;
    $this->freelinkingToMarkdown  = $freelinkingToMarkdown;
    $this->markdownPreparer       = $markdownPreparer;
    $this->renderer               = $renderer;
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
      $container->get('plugin.manager.omnipedia_element_legacy'),
      $container->get('entity_type.manager')->getStorage('filter_format'),
      $container->get('omnipedia_content_legacy.freelinking_to_markdown'),
      $container->get('omnipedia_content_legacy.markdown_preparer'),
      $container->get('renderer')
    );
  }

  /**
   * Apply just the Freelinking filter from the wiki_html filter format.
   *
   * This is a bit of a hack, and you shouldn't do this under most
   * circumstances, but rather let Drupal's rendering and filter system handle
   * applying a filter format for you. Unfortunately, there isn't an API for
   * applying a single filter plug-in configured for a specific filter format,
   * which is why we have to load the filter format ourselves, fetch the
   * Freelinking plug-in instance, and manually apply it to $content.
   *
   * @param string $content
   *   The content to apply the Freelinking filter to.
   *
   * @return string
   *   The $content parameter with the Freelinking filter applied.
   *
   * @see \Drupal\filter\Element\ProcessedText::preRenderText()
   *   Inspired by this core method.
   */
  protected function applyFreelinkingFilter(string $content): string {

    if (!\is_object($this->freelinkingFilterInstance)) {

      /** @var \Drupal\filter\FilterFormatInterface|null */
      $wikiHtmlFormat = $this->filterFormatStorage->load('wiki_html');

      /** @var \Drupal\filter\Plugin\FilterInterface[]**/
      $filters = $wikiHtmlFormat->filters();

      $this->freelinkingFilterInstance = $filters->get('freelinking');

    }

    $content = $this->freelinkingFilterInstance->prepare($content, 'und');

    /** @var \Drupal\filter\FilterProcessResult */
    $result = $this->freelinkingFilterInstance->process($content, 'und');

    return $result->getProcessedText();

  }

  /**
   * Convert legacy Omnipedia elements.
   *
   * This calls the legacy element manager in a new render context, as it needs
   * to render using Drupal's renderer which would fail with an error because no
   * render context is active during a migration.
   *
   * @param string $content
   *   The content to convert legacy elements in.
   *
   * @return string
   *   The $content parameter with legacy elements rendered to their Drupal 8+
   *   equivalents.
   */
  protected function convertLegacyElements(string $content): string {

    /** @var \Drupal\Core\Render\RenderContext */
    $renderContext = new RenderContext();

    return (string) $this->renderer->executeInRenderContext(
      $renderContext, function() use ($content) {
        return $this->elementLegacyManager->convertElements($content);
      }
    );

  }

  /**
   * {@inheritdoc}
   */
  public function transform(
    $value,
    MigrateExecutableInterface $migrateExecutable,
    Row $row,
    $destinationProperty
  ) {

    if (
      !\is_array($value) ||
      !isset($value['value']) ||
      !isset($value['format'])
    ) {
      throw new MigrateException(
        '$value does not appear to be a filtered text field. Got:' . "\n" .
        \print_r($value, true)
      );
    }

    if ($value['format'] !== 'wiki_html') {
      throw new MigrateException(
        '$value[\'format\'] is not \'wiki_html\'. Got: \'' .
          $value['format'] . '\''
      );
    }

    $content = $value['value'];

    foreach ([
      [$this,                         'applyFreelinkingFilter'],
      [$this->freelinkingToMarkdown,  'process'],
      [$this,                         'convertLegacyElements'],
      [$this->markdownPreparer,       'process'],
    ] as $callable) {
      try {
        $content = \call_user_func($callable, $content);

      } catch (\Exception $exception) {
        throw new MigrateException($exception->getMessage());
      }
    }

    // Save the altered content to $value, while also decoding HTML entities as
    // the various steps that built and rendered the HTML will have escaped
    // quotes, angle brackets, and ampersands via PHP's DOM parsing and
    // rendering.
    $value['value'] = Html::decodeEntities($content);

    return $value;

  }

}
