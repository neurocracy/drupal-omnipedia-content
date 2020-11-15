<?php

namespace Drupal\omnipedia_content\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Omnipedia date range Views filter plug-ins.
 *
 * As of Drupal core 8.9/9.2, there isn't yet a Views filter bundled with core,
 * despite @link https://www.drupal.org/project/drupal/issues/2924061 ongoing
 * work in a core issue since 8.5. @endlink Creating a filter plug-in with the
 * full range of options and settings to handle all or most use cases for date
 * range fields is out of scope of Omnipedia, so while this may be revisited at
 * a later date, the quick and simple solution is to treat the start and end
 * dates as their own fields, and apply different filter plug-ins to each
 * independently.
 *
 * Note that the plug-ins do not currently use the 'First date' and 'Last date'
 * labels like the rest of the Omnipedia date code due to the additional
 * complexity of dealing with that in a Views filter. Keeping the exposed
 * filters as not required allows the use of the '- Any -' option, which is
 * identical in functionality. In the future, that label may be altered if
 * feasible, but is currently left as-is.
 *
 * @see https://www.drupal.org/project/drupal/issues/2924061
 *   Ongoing issue to add a date range Views filter plug-in to Drupal core.
 *
 * @see https://drupal.stackexchange.com/questions/226884/how-to-filter-a-view-by-date-range-start-end
 *   Drupal Answers thread with various general use solutions.
 *
 * @see https://www.drupal.org/project/views_daterange_filters
 *   General purpose Views date range filter module. Does not have a stable
 *   release at the time of writing with security coverage.
 *
 * @see https://github.com/barsan-md/interval-filter
 *   General purpose Views filter that can filter based on two fields, one as a
 *   minimum value and another as a maximum value. Does not appear to have a
 *   stable release with security coverage.
 *
 * @see \Drupal\omnipedia_attached_data\Entity\OmnipediaAttachedDataViewsData::getViewsData()
 *   Example of how to assign filter plug-ins to date range start and end
 *   fields.
 */
abstract class OmnipediaDateRangeBase extends FilterPluginBase {

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_core\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * Constructs an OmnipediaDateRangeBase object.
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
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
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
  protected function defineOptions() {
    $options = parent::defineOptions();

    // This sets the default operator based on the plug-in that extends this
    // base class. Why does Views not do this automatically?
    $options['operator']['default'] = $this->operator;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

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

    $form['value'] = [
      '#type'     => 'select',
      '#options'  => $options,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * This replaces the parent method with a simple one that adds a WHERE
   * clause that either compares the value using the operator or matches if the
   * field is null, which would indicate the table row should match any start or
   * end date.
   */
  public function query() {
    $this->ensureMyTable();

    $this->query->addWhere(
      $this->options['group'],
      (new Condition('OR'))
        ->condition(
          "$this->tableAlias.$this->realField", $this->value, $this->operator
        )
        ->condition(
          "$this->tableAlias.$this->realField", $this->value, 'IS NULL'
        )
    );
  }

}
