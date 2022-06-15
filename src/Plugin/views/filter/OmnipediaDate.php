<?php

namespace Drupal\omnipedia_content\Plugin\views\filter;

use Drupal\omnipedia_date\Service\TimelineInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter to handle Omnipedia wiki node dates.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("omnipedia_date")
 *
 * @see \Drupal\omnipedia_content\EventSubscriber\Views\ViewsDataOmnipediaDateEventSubscriber
 *   Defines Views data for this plug-in.
 */
class OmnipediaDate extends InOperator {

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_date\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * Constructs an OmnipediaDate object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginId
   *   The plug-in ID for the plug-in instance.
   *
   * @param mixed $pluginDefinition
   *   The plug-in implementation definition.
   *
   * @param \Drupal\omnipedia_date\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   */
  public function __construct(
    array $configuration, $pluginId, $pluginDefinition,
    TimelineInterface $timeline
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    // Save dependencies.
    $this->timeline = $timeline;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginId,
    $pluginDefinition
  ) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('omnipedia.timeline')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(
    ViewExecutable $view, DisplayPluginBase $display, array &$options = null
  ) {
    parent::init($view, $display, $options);

    $this->definition['options callback'] = array($this, 'generateOptions');
  }

  /**
   * Helper function that generates the filter options.
   *
   * @return array
   */
  public function generateOptions(): array {
    // Contains all dates that have nodes, in the 'storage' format. This
    // includes unpublished nodes.
    /** @var array */
    $definedDates = $this->timeline->getDefinedDates(true);

    /** @var array */
    $options = [];

    foreach ($definedDates as $dateStorage) {
      // Array keys are the storage format stored in the node fields, while the
      // values are the user-friendly strings presented to the user.
      $options[$dateStorage] = $this->timeline->getDateFormatted(
        $dateStorage, 'short'
      );
    }

    return $options;
  }

}
