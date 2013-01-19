<?php
/*
VarkTech Minimum Purchase for WooCommerce
Woo-specific functions
Parent Plugin Integration
*/


class VTMIN_Parent_Cart_Validation {
	
	public function __construct(){
     global $vtmin_info, $woocommerce; //$woocommerce_checkout = $woocommerce->checkout();
     /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++   
     *        Apply Minimum Amount Rules to ecommerce activity
     *                                                          
     *          WOO-Specific Checkout Logic and triggers 
     *                                               
     *  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++   */
                                
    //  add actions for early entry into Woo's 3 shopping cart-related pages, and the "place order" button -

    //if "place order" button hit, this action catches and errors as appropriate
    add_action( 'woocommerce_before_checkout_process', array(&$this, 'vtmin_woo_apply_checkout_cntl') );   
    
    
    $vtmin_info['woo_cart_url']      =  $this->vtmin_woo_get_url('cart'); 
    $vtmin_info['woo_checkout_url']  =  $this->vtmin_woo_get_url('checkout');
    $vtmin_info['woo_pay_url']       =  $this->vtmin_woo_get_url('pay');   
    $vtmin_info['currPageURL']       =  $this->vtmin_currPageURL();
      
    if ( in_array($vtmin_info['currPageURL'], array($vtmin_info['woo_cart_url'],$vtmin_info['woo_checkout_url'], $vtmin_info['woo_pay_url'], $vtmin_info['woo_pay_url'] ) ) )  {      
       add_action( 'init', array(&$this, 'vtmin_woo_apply_checkout_cntl'),99 ); 
       //                                                                 ***
    }  
     /*   Priority of 99 in the action above, to delay add_action execution. The
          priority delays us in the exec sequence until after any quantity change has
          occurred, so we pick up the correct altered state. */                                                                      
	}

 
  // from woocommerce/classes/class-wc-cart.php 
  public function vtmin_woo_get_url ($pageName) {            
     global $woocommerce;
      $checkout_page_id = $this->vtmin_woo_get_page_id($pageName);
  		if ( $checkout_page_id ) {
  			if ( is_ssl() )
  				return str_replace( 'http:', 'https:', get_permalink($checkout_page_id) );
  			else
  				return apply_filters( 'woocommerce_get_checkout_url', get_permalink($checkout_page_id) );
  		}
  }
      
  // from woocommerce/woocommerce-core-functions.php 
  public function vtmin_woo_get_page_id ($pageName) { 
    $page = apply_filters('woocommerce_get_' . $pageName . '_page_id', get_option('woocommerce_' . $pageName . '_page_id'));
		return ( $page ) ? $page : -1;
  }    
 /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++    */
    
    
           
  /* ************************************************
  **   Application - Apply Rules at E-Commerce Checkout
  *************************************************** */
	public function vtmin_woo_apply_checkout_cntl(){
    global $vtmin_cart, $vtmin_cart_item, $vtmin_rules_set, $vtmin_rule, $vtmin_info, $woocommerce;
        
    //input and output to the apply_rules routine in the global variables.
    //    results are put into $vtmin_cart
    
    if ( $vtmin_cart->error_messages_processed == 'yes' ) {  
      $woocommerce->add_error(  __('Minimum Purchase error found.', 'vtmin') );  //supplies an error msg and prevents payment from completing 
      return;
    }
    
     $vtmin_apply_rules = new VTMIN_Apply_Rules;   
    
    //ERROR Message Path
    if ( sizeof($vtmin_cart->error_messages) > 0 ) {      
      //insert error messages into checkout page
      add_action( "wp_enqueue_scripts", array($this, 'vtmin_enqueue_error_msg_css') );
      add_action('wp_head', array(&$this, 'vtmin_display_rule_error_msg_at_checkout') );  //JS to insert error msgs 
      $vtmin_cart->error_messages_processed = 'yes';
      $woocommerce->add_error(  __('Minimum Purchase error found.', 'vtmin') );  //supplies an error msg and prevents payment from completing      
    }     
  } 

  
  /* ************************************************
  **   Application - On Error Display Message on E-Commerce Checkout Screen  
  *************************************************** */ 
  public function vtmin_display_rule_error_msg_at_checkout(){
    global $vtmin_info, $vtmin_cart, $vtmin_setup_options;
     
    //error messages are inserted just above the checkout products, and above the checkout form
      //In this situation, this 'id or class Selector' may not be blank, supply woo checkout default - must include '.' or '#'
    if ( $vtmin_setup_options['show_error_before_checkout_products_selector']  <= ' ' ) {
       $vtmin_setup_options['show_error_before_checkout_products_selector'] = VTMIN_CHECKOUT_PRODUCTS_SELECTOR_BY_PARENT;             
    }
      //In this situation, this 'id or class Selector' may not be blank, supply woo checkout default - must include '.' or '#'
    if ( $vtmin_setup_options['show_error_before_checkout_address_selector']  <= ' ' ) {
       $vtmin_setup_options['show_error_before_checkout_address_selector'] = VTMIN_CHECKOUT_ADDRESS_SELECTOR_BY_PARENT;             
    }
     ?>     
        <script type="text/javascript">
        jQuery(document).ready(function($) {
    <?php 
    //loop through all of the error messages 
    //          $vtmin_info['line_cnt'] is used when table formattted msgs come through.  Otherwise produces an inactive css id. 
    for($i=0; $i < sizeof($vtmin_cart->error_messages); $i++) { 
     ?>
        <?php 
          //default selector for products area (".shop_table") is used on BOTH cart page and checkout page. Only use on cart page
          if ( ( $vtmin_setup_options['show_error_before_checkout_products'] == 'yes' ) &&  ($vtmin_info['currPageURL'] == $vtmin_info['woo_cart_url']) ){ 

        ?>
           $('<div class="vtmin-error" id="line-cnt<?php echo $vtmin_info['line_cnt'] ?>"><h3 class="error-title">Minimum Purchase Error</h3><p> <?php echo $vtmin_cart->error_messages[$i]['msg_text'] ?> </p></div>').insertBefore('<?php echo $vtmin_setup_options['show_error_before_checkout_products_selector'] ?>');
        <?php 
          } 
          //Only message which shows up on actual checkout page.
          if ( $vtmin_setup_options['show_error_before_checkout_address'] == 'yes' ){ 
           
        ?>
           $('<div class="vtmin-error" id="line-cnt<?php echo $vtmin_info['line_cnt'] ?>"><h3 class="error-title">Minimum Purchase Error</h3><p> <?php echo $vtmin_cart->error_messages[$i]['msg_text'] ?> </p></div>').insertBefore('<?php echo $vtmin_setup_options['show_error_before_checkout_address_selector'] ?>');
    <?php 
          }
    }  //end 'for' loop      
     ?>   
            });   
          </script>
     <?php    


     /* ***********************************
        CUSTOM ERROR MSG CSS AT CHECKOUT
        *********************************** */
     if ($vtmin_setup_options[custom_error_msg_css_at_checkout] > ' ' )  {
        echo '<style type="text/css">';
        echo $vtmin_setup_options[custom_error_msg_css_at_checkout];
        echo '</style>';
     }
     
     /*
      Turn off the messages processed switch.  As this function is only executed out
      of wp_head, the switch is only cleared when the next screenful is sent.
     */
     $vtmin_cart->error_messages_processed = 'no';   
 } 
 
   
  /* ************************************************
  **   Application - get current page url
  *************************************************** */ 
 public  function vtmin_currPageURL() {
     $pageURL = 'http';
     if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
        $pageURL .= "://";
     if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
     } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
     }
     return $pageURL;
  } 
 
    

  /* ************************************************
  **   Application - On Error enqueue error style
  *************************************************** */
  public function vtmin_enqueue_error_msg_css() {
    wp_register_style( 'vtmin-error-style', VTMIN_URL.'/core/css/vtmin-error-style.css' );  
    wp_enqueue_style('vtmin-error-style');
  } 
 
} //end class
$vtmin_parent_cart_validation = new VTMIN_Parent_Cart_Validation;