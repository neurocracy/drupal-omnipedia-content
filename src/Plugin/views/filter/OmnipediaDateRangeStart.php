<?php

namespace Drupal\omnipedia_content\Plugin\views\filter;

use Drupal\omnipedia_content\Plugin\views\filter\OmnipediaDateRangeBase;

/**
 * Filter to handle Omnipedia date range start.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("omnipedia_date_range_start")
 *
 * @see \Drupal\omnipedia_content\Plugin\views\filter\OmnipediaDateRangeBase
 *   Base class for this filter; documents the reasoning for and use of this
 *   date range filter.
 */
class OmnipediaDateRangeStart extends OmnipediaDateRangeBase {

  /**
   * {@inheritdoc}
   */
  public $operator = '>=';

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();

    $this->options['expose']['identifier'] = 'date_start';
  }

}
