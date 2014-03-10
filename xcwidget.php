<?php
/**
 * Plugin Name: XCWidget
 * Plugin URI: https://github.com/FLHerne/xcwidget
 * Description: Show contents of an XCart basket
 * Version: 0.02
 * Author: Francis Herne
 */

class XCWidget extends WP_Widget {

  //Constructor
  function XCWidget() {
    // Instantiate the parent object
    parent::__construct(false, 'XCWidget');
  }

  //Widget output
  function widget($args, $instance) {
    $xcart_cookies = array();	// WordPress has its own cookie format. Clone the existing cookies to it.
    foreach ($_COOKIE as $name => $value) {
      $xcart_cookies[] = new WP_Http_Cookie(array('name' => $name, 'value' => $value));
    }
    $xcart_request = wp_remote_get($instance['xcart_url'], array('cookies' => $xcart_cookies));
    if (is_wp_error($xcart_request)) {
      $xcart_get_error = $xcart_request->get_error_message();
    } else {
      $xcart = json_decode($xcart_request['body'], true);
    }

    $summary['total_cost']  = price_format(0);
    $summary['total_items'] = 0;

    if (!empty($xcart)) {
      // Assign total cost
      if (!empty($xcart['total_cost'])) {
          $summary['total_cost'] = $xcart['display_subtotal'];
      }

      // Sum up products items
      //Tweaked from XCart
      if (is_array($xcart['products']) && !empty($xcart['products'])) {
        foreach ($xcart['products'] as $product) {
          if (!isset($product['hidden']) || empty($product['hidden'])) {
            $summary['total_items'] += $product['amount'];
          }
        }
      }

      // Sum up giftcerts items
      //Tweaked from XCart
      if (is_array($xcart['giftcerts']) && !empty($xcart['giftcerts'])) {
        foreach ($xcart['giftcerts'] as $product) {
            $summary['total_items'] ++;
        }
      }
    }

    extract($args);
    echo($before_widget);
    echo($before_title . 'Cart contents' . $after_title);
    if (!$xcart_get_error) {
      echo('<p>' . $summary['total_items']);
      echo($summary['total_items'] == 1 ? ' item' : ' items');
      echo('<br>Total cost £'. $summary['total_cost'] . '</p>');
    } else {
      echo('<p>' . $xcart_get_error . '</p>');
    }
    echo($after_widget);
  }

  //Validate URL from admin form
  function update($new_instance, $old_instance) {
    $instance = array();
    if(!empty($new_instance['xcart_url']) && filter_var(strip_tags($new_instance['xcart_url'], FILTER_VALIDATE_URL))) {
      $instance['xcart_url'] = filter_var(strip_tags($new_instance['xcart_url'], FILTER_VALIDATE_URL));
    } else {
      $instance['xcart_url'] = '';
    }
    return $instance;
  }

  //Admin form
  function form($instance) {
    if (isset($instance['xcart_url'])) {
      $xcart_url = $instance['xcart_url'];
    } else {
      $xcart_url = 'http://xcart.example.com/info.php';
    }
    ?>
    <p>
    <label for="<?php echo $this->get_field_id('xcart_url'); ?>"><?php echo('Data URL:'); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('xcart_url'); ?>" name="<?php echo $this->get_field_name('xcart_url'); ?>" type="text" value="<?php echo esc_attr($xcart_url); ?>">
    </p>
    <?php
  }
}

/**
* Convert price to 'XXXXX.XX' format
* Borrowed from XCart, I have no idea how or why this works
*/
function price_format($price) {
  return sprintf("%.2f", round((double)$price + 0.00000000001, 2));
}

function xcwidget_register_widgets() {
  register_widget('XCWidget');
}

add_action('widgets_init', 'xcwidget_register_widgets');
 ?>
