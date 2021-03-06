<?php

/**
 * @file
 * This file is empty by default because the base theme chain (Alpha & Omega) provides
 * all the basic functionality. However, in case you wish to customize the output that Drupal
 * generates through Alpha & Omega this file is a good place to do so.
 *
 * Alpha comes with a neat solution for keeping this file as clean as possible while the code
 * for your subtheme grows. Please read the README.txt in the /preprocess and /process subfolders
 * for more information on this topic.
 */

/**
* Preprocess variables for html template.
*/
function sbirtheme_preprocess_html(&$variables) {
    // load the DTM js URL from a variable, allowing it to be overridden if needed
    $src = variable_get('adobe_dtm_js', 
        '//assets.adobedtm.com/f1bfa9f7170c81b1a9a9ecdcc6c5215ee0b03c84/satelliteLib-5ad8c106153615d0673f7263de823289c481d7df.js');

    $dtm_script = "<script src='$src'></script>\n";

    // add DTM tag
    $element = array(
        '#type' => 'markup', // use raw markup to correctly build the script tags
        '#markup' => $dtm_script,
        '#weight' => '-9999999', // push this script as high as possible
      );

      // add element to html head
      drupal_add_html_head($element, 'dtm_script_head');
}

/**
 * Implements hook_breadcrumb().
 */
function sbirtheme_breadcrumb($variables) {
  $breadcrumb = $variables['breadcrumb'];
  if (count($breadcrumb) == 1) {
    //return;
  }

  //dpm(sizeof($breadcrumb));
  if (!empty($breadcrumb) /* && sizeof($breadcrumb) > 0 */) {
    // Adding the title of the current page to the breadcrumb.
    if (isset($GLOBALS['_GET']['q'])) {
      $q = $GLOBALS['_GET']['q'];
      $menu_item = menu_get_item($q);
      $menu_item = db_query('SELECT link_title FROM {menu_links}'
          . ' WHERE link_path = :path'
          . ' LIMIT 0, 1', array(':path' => $q))->fetchAssoc();

      if (isset($menu_item['link_title'])) {
        $breadcrumb[] = '<span>' . $menu_item['link_title'] . '</span>';
        //print $menu_item['title'];exit;
      }
      else {
        $breadcrumb[] = '<span>' . drupal_get_title() . '</span>';
      }
    }
    else {
      $breadcrumb[] = '<span>' . drupal_get_title() . '</span>';
    }

    // Provide a navigational heading to give context for breadcrumb links to
    // screen-reader users. Make the heading invisible with .element-invisible.
    $output = '<h2 class="element-invisible">' . t('You are here') . '</h2>';

    //$output .= '<div class="breadcrumb">' . implode(' » ', $breadcrumb) . '</div>';
    $output .= '<div class="breadcrumb">' . implode('&nbsp;&nbsp;\&nbsp;&nbsp;', $breadcrumb) . '</div>';
    return $output;
  }
}

/**
 * Implements hook_validate().
 */
function sbirtheme_validate($node, $form, $form_state) {
  
}

/*
 * Implements hook_block_view
 */

function sbirtheme_block_view_alter(&$data, $block) {
  //dpm($block->delta);
  if ($block->delta == 'menu-social-media') {
    foreach ($data['content'] as $key => $value) {
      if (is_numeric($key) && is_array($value) && isset($value['#title'])) {
        $title = $value['#title'];
        if ($title == 'Sign Up for Updates') {
          $data['content'][$key]['#attributes']['class'][] = 'email-link';
          $data['content'][$key]['#attributes']['class'][] = 'social-media-link';
        }
        if ($title == 'Connect with us on LinkedIn') {
          $data['content'][$key]['#attributes']['class'][] = 'linkedin-link';
          $data['content'][$key]['#attributes']['class'][] = 'social-media-link';
          $data['content'][$key]['#localized_options']['attributes']['class'][] = 'no-exit-notification';
        }
        if ($title == 'Follow us on Twitter') {
          $data['content'][$key]['#attributes']['class'][] = 'twitter-link';
          $data['content'][$key]['#attributes']['class'][] = 'social-media-link';
          $data['content'][$key]['#localized_options']['attributes']['class'][] = 'no-exit-notification';
        }
      }
    }

    $follow_us = array(
      '#markup' => ''
    );
    // $data['content'][] = $follow_us;
    array_unshift($data['content'], $follow_us);
  }

  if ($block->delta == 'menu-social-media') {
    foreach ($data['content'] as $key => $value) {
      if (is_numeric($key) && is_array($value) && isset($value['#title'])) {
        //dpm($value);
        $title = $value['#title'];
        if ($title == 'Site Map' || $title == 'Connect with us on LinkedIn') {
          $data['content'][$key]['#attributes']['class'][] = 'with-image';
        }
      }
    }
  }

  if ($block->delta == 'menu-footer-menu') {
    foreach ($data['content'] as $key => $value) {
      if (is_numeric($key) && is_array($value) && isset($value['#title'])) {
        //dpm($value);
        $title = $value['#title'];
        if ($title == 'Site Map' || $title == 'USA.gov') {
          $data['content'][$key]['#attributes']['class'][] = 'footer-break-point';
        }
        if ($title == 'NIH ... Turning Discovery Into Health') {
          $data['content'][$key]['#attributes']['class'][] = 'discovery';
        }
      }
    }
  }
}

function sbirtheme_form_alter(&$form, &$form_state, $form_id) {

  if ($form_id == "search_form") {
    unset($form['advanced']);
    unset($form['basic']);
  }
}

/**
 * Implements hook_preprocess_search_results().
 */
function sbirtheme_preprocess_search_results(&$vars) {
  // search.module shows 20 items per page (this isn't customizable)
  $itemsPerPage = 20;

  // Determine which page is being viewed
  // If $_REQUEST['page'] is not set, we are on page 1
  $currentPage = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 0) + 1;

  // Get the total number of results from the global pager
  $total = isset($GLOBALS['pager_total_items'][0]) ? $GLOBALS['pager_total_items'][0] : 0;

  // get search term
  $searchTerm = request_path();
  $searchTerm = ltrim(substr($searchTerm, strrpos($searchTerm, '/')), '/');

  // Determine which results are being shown ("Showing results x through y")
  $start = (20 * $currentPage) - 19;
  // If on the last page, only go up to $total, not the total that COULD be
  // shown on the page. This prevents things like "Displaying 21-40 of 27".
  $end = (($itemsPerPage * $currentPage) >= $total) ? $total : ($itemsPerPage * $currentPage);

  // If there is more than one page of results:
  if ($total > $itemsPerPage) {
    $vars['search_totals'] = t('Results !start - !end of !total for:', array(
      '!start' => $start,
      '!end' => $end,
      '!total' => $total
    ));
    $vars['search_term'] = t(' !term', array(
      '!term' => $searchTerm
    ));
  }
  else {
    // Only one page of results, so make it simpler
    $vars['search_totals'] = t('Results !start - !end of !total for:', array(
      '!start' => $start,
      '!end' => $end,
      '!total' => $total
    ));
    $vars['search_term'] = t(' !term', array(
      '!term' => $searchTerm
    ));
  }
}

/**
 * Implements hook_preprocess_page().
 */
function sbirtheme_preprocess_page(&$vars) {
  $header = drupal_get_http_header('status'); 
  if ($header == '404 Not Found') {     
    $vars['theme_hook_suggestions'][] = 'page__404';
  }
}

/**
 * Implements hook_date_all_day_label().
 */
function sbirtheme_date_all_day_label() {
  //return '';
}


/**
 * Implements hook_date_display_range().
 */
function sbirtheme_date_display_range($variables) {
  $date1 = $variables['date1'];
  $date2 = $variables['date2'];
  $timezone = $variables['timezone'];
  $attributes_start = $variables['attributes_start'];
  $attributes_end = $variables['attributes_end'];

  $start_date = '<span class="date-display-start"' . drupal_attributes($attributes_start) . '>' . $date1 . '</span>';
  $end_date = '<span class="date-display-end"' . drupal_attributes($attributes_end) . '>' . $date2 . $timezone . '</span>';

  // If microdata attributes for the start date property have been passed in,
  // add the microdata in meta tags.
  if (!empty($variables['add_microdata'])) {
    $start_date .= '<meta' . drupal_attributes($variables['microdata']['value']['#attributes']) . '/>';
    $end_date .= '<meta' . drupal_attributes($variables['microdata']['value2']['#attributes']) . '/>';
  }

  // Wrap the result with the attributes.
  return t('!start-date - !end-date', array(
    '!start-date' => $start_date,
    '!end-date' => $end_date,
  ));
}