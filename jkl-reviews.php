<?php
/*
 * Plugin Name: JKL Reviews
 * Plugin URI: http://www.jekkilekki.com
 * Description: A simple Reviews plugin to review books, music, movies, products, or online courses with Star Ratings and links out to related sites.
 * Version: 1.2
 * Author: Aaron Snowberger
 * Author URI: http://www.aaronsnowberger.com
 * Text Domain: jkl-reviews/languages
 * License: GPL2
 */

/*
 * TODO:
 * 1. Add i18n with EN + KO
 * 2. Allow input of mutliple categories as Terms (like Tags)
 * 
 * UPCOMING:
 * 1. Shortcode to allow insertion anywhere in the post (beginning or end)
 * 2. Shortcode parameter 'small' to show a minimalized version of the box
 * 3. Sidebar widget to show latest books/products reviewed (might be dependent on...)
 * 4. Custom Post Type with custom Taxonomies for Review Types (can sort and display in widgets/index pages)
 * 5. WordPress options page to modify box CSS styles
 * 6. Incorporate AJAX for image chooser, Material Connection disclosure, CSS box styles, etc
 */

/*
 * Text Domain: (above) is used for Internationalization and must match the 'slug' of the plugin.
 * Doc: http://codex.wordpress.org/I18n_for_WordPress_Developers
 */

/*
 * Reference Section: (Custom Meta Boxes)
 * Complex Meta boxes in WP (Reference): http://www.wproots.com/complex-meta-boxes-in-wordpress/
 * http://www.smashingmagazine.com/2011/10/04/create-custom-post-meta-boxes-wordpress/
 * http://themefoundation.com/wordpress-meta-boxes-guide/
 * http://code.tutsplus.com/tutorials/how-to-create-custom-wordpress-writemeta-boxes--wp-20336
 */


// ##0 : Enqueue the CSS styles for the metabox (both admin and in the_content)
add_action( 'admin_enqueue_scripts', 'jkl_review_style');
add_action( 'the_content', 'jkl_get_review_box_style');

// ##1 : Create metabox in Post editing page
add_action( 'add_meta_boxes', 'jkl_add_review_metabox' );

// ##2 : Display the actual Metabox and fields
// ##3 : Add and Use the WP Image Manager
add_action( 'admin_enqueue_scripts', 'jklrv_image_enqueue' );

// ##4 : Save metabox data
add_action( 'save_post', 'jkl_save_review_metabox' );

// ##5 : Display metabox data (and CSS style) straight up on a Post
add_filter( 'the_content', 'jkl_display_review_box' );

// ##6 : Call various helper functions for displaying the metabox (no hooks necessary)
// ##7 : Add WP Options page
add_action( 'admin_menu', function() { JKL_Review_Options::add_menu_page(); } );
add_action( 'admin_init', function() { new JKL_Review_Options(); } );


/*
 * ##### 0 #####
 * Before everything else, queue up the CSS styles for this metabox
 */

// CSS styles for the admin area
function jkl_review_style() {
    wp_register_style( 'jkl_review_css', plugin_dir_url( __FILE__ ) . '/css/style.css', false, '1.0.0' );
    wp_enqueue_style( 'jkl_review_css' );
    
    // Also, add Font Awesome to our back-end styles
    wp_enqueue_style( 'fontawesome', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css' );
}

// CSS styles for the Post
function jkl_get_review_box_style() {
    wp_register_style( 'jkl_review_box_display_css', plugin_dir_url( __FILE__ ) . '/css/boxstyle.css', false, '1.0.0' );
    wp_enqueue_style( 'jkl_review_box_display_css' );
    
    // Also, add Font Awesome to our front-end styles
    wp_enqueue_style( 'fontawesome', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css' );
}


/*
 * ##### 1 ##### 
 * First, ADD the metabox
 */

function jkl_add_review_metabox() {
    /* 
     * Doc http://codex.wordpress.org/Function_Reference/add_meta_box/
     * add_meta_box( $id, $title, $callback, $post_type, $context*, $priority*, $callback_args* ); 
     * $post_type cannot take an array of values
     * $context, $priority, $callback_args are optional values
     */ 
    
    add_meta_box( 
            'review_info',                                      // Unique ID
            __('Review Information', 'jkl-reviews/languages'),  // Title
            'display_jkl_review_metabox',                       // Callback function
            'post'                                              // Post type to display on
                                                                // Context
                                                                // Priority
                                                                // Callback_args
    );
}


/*
 * ##### 2 #####
 * Second, DISPLAY Metabox (i.e. This is the Metabox handler)
 * 
 * @param WP_Post $post The object for the current post/page
 */

function display_jkl_review_metabox( $post ) {
    
    /*
     * Documentation on nonces: 
     * http://markjaquith.wordpress.com/2006/06/02/wordpress-203-nonces/
     * http://www.prelovac.com/vladimir/improving-security-in-wordpress-plugins-using-nonces
     */
    wp_nonce_field( basename(__FILE__), 'jklrv_nonce' ); // Add two hidden fields to protect against cross-site scripting.
    
    // Retrieve the current data based on Post ID
    $jklrv_stored_meta = get_post_meta( $post->ID );
    $jklrv_stored_options = get_option( 'jklrv_plugin_options' ); // Get options set on WP Options page
    
    // Call a separate function to evaluate the value stored for the radio button and return a string to correspond to its FontAwesome icon
    $jklrv_fa_icon = jkl_get_fa_icon( $jklrv_stored_meta['jkl-radio'][0] );
    
    /*
     * Metabox fields                                           Validated (on save)     Escaped (output)    Method
     * 0. Review Type (radio)       => jkl_radio                                        unnecessary?        (esc_attr breaks the code)
     * 1. Cover Image               => jkl_review_cover                                 back / front        esc_url
     * 2. Title                     => jkl_review_title         sanitize_text_field()   back / front        esc_attr
     * 3. Author                    => jkl_review_author        sanitize_text_field()   back / front        esc_attr
     * 4. Series                    => jkl_review_series        sanitize_text_field()   back / front        esc_attr
     * 5. Category                  => jkl_review_category      sanitize_text_field()   back / front        esc_attr
     * 6. Rating                    => jkl_review_rating        (float)                 unnecessary?        (use (float) to set as float, or floatval( $val ) to check it's a float
     * 7. Summary                   => jkl_review_summary_area                          back / front        wp_kses_post
     * 8. Affiliate Link            => jkl_review_affiliate_uri                         back / front        esc_url
     * 9. Product Link              => jkl_review_product_uri                           back / front        esc_url
     * 10. Author Link              => jkl_review_author_uri                            back / front        esc_url
     * 11. Resources Link           => jkl_review_resources_uri                         back / front        esc_url
     * 12. Disclosure Type (radio)  => jkl_disclose                                     unnecessary?
     */
    
    // Ref: TutsPlus Working with Meta Boxes Video Course
    
    // If we want to show the values we've stored, there are 2 ways to do that:
    // 0. $jklrv_stored_meta = get_post_meta( $post->ID );
    // 1. if ( isset ( $jklrv_stored_meta['identifier'] ) ) echo $jklrv_stored_meta['identifier'][0];
    // 2. $html .= <input type="text" value="' . get_post_meta( $post->ID, 'identifier', true ) . '" />';
    
    // Test your saved values are stripped of tags by trying to save:
    // <script>alert('Hello world!');</script>
    
    ?>

    <!-- REVIEW INFORMATION TABLE -->
    <table class="jkl_review"> 
        
        <!-- ##### PRODUCT INFORMATION TABLE -->
        <tr><th colspan="2"><?php _e( 'Product Information', 'jkl-reviews/languages') ?></th></tr>
        <tr class="divider"></tr>
        
        <!-- Product Type. Select the radio button corresponding to the product you are reviewing -->
        <tr>
            <td class="left-label">
                <label for="jkl-product-type" class="jkl_label"><?php _e('Product Type: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <div class="radio">
                <label for="jkl-book-type" id="jkl-book-type">
                    <input type="radio" name="jkl_radio" id="jkl-radio-book" value="book" <?php if ( isset( $jklrv_stored_meta['jkl_radio'] ) ) checked( $jklrv_stored_meta['jkl_radio'][0], 'book' ); ?>>
                    <i class="fa fa-book"></i><span class="note"><?php _e('Book', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-audio-type" id="jkl-audio-type">
                    <input type="radio" name="jkl_radio" id="jkl-radio-audio" value="audio" <?php if ( isset( $jklrv_stored_meta['jkl_radio'] ) ) checked( $jklrv_stored_meta['jkl_radio'][0], 'audio' ); ?>>
                    <i class="fa fa-headphones"></i><span class="note"><?php _e('Audio', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-video-type" id="jkl-video-type">
                    <input type="radio" name="jkl_radio" id="jkl-radio-video" value="video" <?php if ( isset( $jklrv_stored_meta['jkl_radio'] ) ) checked( $jklrv_stored_meta['jkl_radio'][0], 'video' ); ?>>
                    <i class="fa fa-play-circle"></i><span class="note"><?php _e('Video', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-course-type" id="jkl-course-type">
                    <input type="radio" name="jkl_radio" id="jkl-radio-course" value="course" <?php if ( isset( $jklrv_stored_meta['jkl_radio'] ) ) checked( $jklrv_stored_meta['jkl_radio'][0], 'course' ); ?>>
                    <i class="fa fa-pencil-square-o"></i><span class="note"><?php _e('Course', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-product-type" id="jkl-product-type">
                    <input type="radio" name="jkl_radio" id="jkl-radio-product" value="product" <?php if ( isset( $jklrv_stored_meta['jkl_radio'] ) ) checked( $jklrv_stored_meta['jkl_radio'][0], 'product' ); ?>>
                    <i class="fa fa-archive"></i><span class="note"><?php _e('Product', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-service-type" id="jkl-service-type">
                    <input type="radio" name="jkl_radio" id="jkl-radio-service" value="service" <?php if ( isset( $jklrv_stored_meta['jkl_radio'] ) ) checked( $jklrv_stored_meta['jkl_radio'][0], 'service' ); ?>>
                    <i class="fa fa-gift"></i><span class="note"><?php _e('Service', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-other-type" id="jkl-other-type">
                    <input type="radio" name="jkl_radio" id="jkl-radio-other" value="other" <?php if ( isset( $jklrv_stored_meta['jkl_radio'] ) ) checked( $jklrv_stored_meta['jkl_radio'][0], 'other' ); ?>>
                    <i class="fa fa-star"></i><span class="note"><?php _e('Other', 'jkl-reviews/languages')?></span>
                </label>
                </div>
            </td>
        </tr>
        
        <tr><td colspan="2"><div class="divider-lite"></div></td></tr>
        
        <!-- Cover image. This should accept and display an image (like a Featured Image) using WP's image Uploader/chooser. -->
        <tr>
            <td class="left-label">
                <label for="jkl_review_cover" class="jkl_label"><?php _e('Product Image: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <input type="url" id="jkl_review_cover" name="jkl_review_cover" 
                           value="<?php if( isset( $jklrv_stored_meta['jkl_review_cover'] ) ) echo esc_url( $jklrv_stored_meta['jkl_review_cover'][0] ); ?>" />
                <input type="button" id="jkl_review_cover_button" class="button" value="<?php _e( 'Choose or Upload an Image', 'jkl_review/languages' )?>" />
            </td>
        </tr>
        
        <!-- Cover image preview. This should only display the cover image IF THERE IS ONE. -->
        <?php if ( $jklrv_stored_meta['jkl_review_cover'][0] != '' ) { ?>
        <tr>
            <td class="left-label">
                <label for="jkl_review_cover_preview" class="jkl_label"><?php _e('Product Image Preview: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <div id="jkl_cover_preview">
                    <img src="<?php echo esc_url( $jklrv_stored_meta['jkl_review_cover'][0] ); ?>" />
                </div>
            </td>
        </tr>
        <?php } ?>

        <!-- Title -->
        <tr>
            <td class="left-label">
                <label for="jkl_review_title" class="jkl_label"><?php _e('Title: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <input type="text" class="input-text" id="jkl_review_title" name="jkl_review_title" 
                           value="<?php if( isset( $jklrv_stored_meta['jkl_review_title'] ) ) echo esc_attr( $jklrv_stored_meta['jkl_review_title'][0] ); ?>" />
            </td>
        </tr>

        <!-- Author -->
        <tr>
            <td class="left-label">
                <label for="jkl_review_author" class="jkl_label"><?php _e('Author: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <input type="text" class="input-text" id="jkl_review_author" name="jkl_review_author" 
                           value="<?php if( isset( $jklrv_stored_meta['jkl_review_author'] ) ) echo esc_attr( $jklrv_stored_meta['jkl_review_author'][0] ); ?>" />
            </td>
        </tr>

        <!-- Series -->
        <tr>
            <td class="left-label">
                <label for="jkl_review_series" class="jkl_label"><?php _e('Series: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <input type="text" class="input-text" id="jkl_review_series" name="jkl_review_series" 
                           value="<?php if( isset( $jklrv_stored_meta['jkl_review_series'] ) ) echo esc_attr( $jklrv_stored_meta['jkl_review_series'][0] ); ?>" />
            </td>
        </tr>

        <!-- Category. Should (eventually) act as WP Tags, separate-able by commas, including the list + X marks to remove categories -->
        <tr>
            <td class="left-label">
                <label for="jkl_review_category" class="jkl_label"><?php _e('Category: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <input type="text" class="input-text" id="jkl_review_category" name="jkl_review_category" 
                           value="<?php if( isset( $jklrv_stored_meta['jkl_review_category'] ) ) echo esc_attr( $jklrv_stored_meta['jkl_review_category'][0] ); ?>" />
                <p class="note"><?php _e( 'Separate multiple values with commas.', 'jkl-reviews/languages' ) ?></p>
            </td>
        </tr>
    </table>
      
    <!-- ##### PRODUCT RATING TABLE -->
    <table class="jkl_review">
        <tr><th colspan="2"><?php _e( 'Product Rating', 'jkl-reviews/languages' ) ?></th></tr>
        <tr class="divider"></tr>
        <!-- 
            Rating. This is a range slider from 0-5 with 0.5 step increments.
            Consider implementing a fallback for older browsers.
            Ref: JS range slider: http://www.developerdrive.com/2012/07/creating-a-slider-control-with-the-html5-range-input/
        -->
        <tr>
            <td class="left-label rating-label">
                <label for="jkl_review_rating" class="jkl_label"><?php _e('Rating: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <span class="range-number-left">0</span> 
                <input type="range" min="0" max="5" step="0.5" list="stars" onchange="showValue(this.value)" 
                           id="jkl-review-rating" name="jkl_review_rating" 
                           value="<?php echo isset( $jklrv_stored_meta['jkl_review_rating'] ) ? $jklrv_stored_meta['jkl_review_rating'][0] : 0; ?>" />
                <datalist id="stars">
                    <option>0</option>
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                    <option>4</option>
                    <option>5</option>
                </datalist>
                <span class="range-number-right">5</span>
                
                <output for="jkl_review_rating" id="star-rating">
                    <?php echo isset( $jklrv_stored_meta['jkl_review_rating'] ) ? $jklrv_stored_meta['jkl_review_rating'][0] : 0; ?>
                </output>
                <span id="star-rating-text"><?php _e( 'Stars', 'jkl-reviews/languages' ) ?></span>
                
                <!-- Simple function to dynamically update the output value of the range slider after user releases the mouse button -->
                <script>
                function showValue(rating) {
                    document.querySelector('#star-rating').value = rating;
                }
                </script>
            </td>
        </tr>
        <tr>
            <td class="left-label">
                <label for=jkl_review_summary" class="jkl_label"><?php _e('Short <a href="#">(why?)</a>Summary: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <textarea id="jkl_review_summary_area" name="jkl_review_summary_area"><?php if( isset( $jklrv_stored_meta['jkl_review_summary_area'] ) ) echo wp_kses_post( $jklrv_stored_meta['jkl_review_summary_area'][0] ); ?></textarea>
                <p class="note"><?php _e( 'Enter any valid HTML in the Summary field.<br /><strong>Note:</strong> Any text entered will be in italics.', 'jkl-reviews/languages' ) ?></p>
            </td>
        </tr>
    </table>

    <!-- ##### PRODUCT LINKS TABLE -->
    <table class="jkl_review">
        <tr><th colspan="2"><?php _e( 'Product Links', 'jkl-reviews/languages' ) ?></th></tr>
        <tr class="divider"></tr>
        
        <!-- Affiliate Link -->
        <tr>
            <td class="left-label">
                <label for="jkl_affiliate_uri" class="jkl_label"><?php _e('Affiliate or Purchase Link: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <input type="url" id="jkl_affiliate_uri" name="jkl_review_affiliate_uri"
                        value="<?php if( isset( $jklrv_stored_meta['jkl_review_affiliate_uri'] ) ) echo esc_url( $jklrv_stored_meta['jkl_review_affiliate_uri'][0] ); ?>" />
        </tr> <!-- TODO: Implement an Affiliate link Disclaimer message and checkbox option to turn it on/off. -->
        
        <!-- Product Homepage -->
        <tr>
            <td class="left-label">
                <label for="jkl_review_product_uri" class="jkl_label"><?php _e('Link to Product Page: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <input type="url" id="jkl_review_product_uri" name="jkl_review_product_uri"
                        value="<?php if( isset( $jklrv_stored_meta['jkl_review_product_uri'] ) ) echo esc_url( $jklrv_stored_meta['jkl_review_product_uri'][0] ); ?>" />
        </tr>
        
        <!-- Author Homepage -->
        <tr>
            <td class="left-label">
                <label for="jkl_review_author_uri" class="jkl_label"><?php _e('Link to Author Homepage: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <input type="url" id="jkl_review_author_uri" name="jkl_review_author_uri"
                        value="<?php if( isset( $jklrv_stored_meta['jkl_review_author_uri'] ) ) echo esc_url( $jklrv_stored_meta['jkl_review_author_uri'][0] ); ?>" />
            </td>
        </tr>       
        
        <!-- Resources Page -->
        <tr>
            <td class="left-label">
                <label for="jkl_review_resources_uri" class="jkl_label"><?php _e('Link to Resources Page: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <input type="url" id="jkl_review_resources_uri" name="jkl_review_resources_uri"
                        value="<?php if( isset( $jklrv_stored_meta['jkl_review_resources_uri'] ) ) echo esc_url( $jklrv_stored_meta['jkl_review_resources_uri'][0] ); ?>" />
            </td>
        </tr> 
        
        <?php if ( $jklrv_stored_options[ 'jklrv_display_disclosure' ] ) { // Only display the following IF Disclosure is enabled on WP Options page ?> 
        
        <tr><td colspan="2"><div class="divider-lite"></div></td></tr>
        
        <!-- Material Disclaimer Type. To comply with guidelines by the FTC (16 CFR, Part 255): http://www.access.gpo.gov/nara/cfr/waisidx_03/16cfr255_03.html -->
        <tr>
            <td class="left-label">
                <label for="jkl-disclosure-type" class="jkl_label"><?php _e('Material Disclosure: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <div class="radio">
                <label for="jkl-remove-type" id="jkl-remove-type">
                    <input type="radio" name="jkl_disclose" id="jkl-disclose-remove" value="remove" <?php if ( isset( $jklrv_stored_meta['jkl_disclose'] ) ) checked( $jklrv_stored_meta['jkl_disclose'][0], 'remove' ); ?>>
                    <span class="note"><?php _e('No Disclosure', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-no-type" id="jkl-no-type">
                    <input type="radio" name="jkl_disclose" id="jkl-disclose-none" value="none" <?php if ( isset( $jklrv_stored_meta['jkl_disclose'] ) ) checked( $jklrv_stored_meta['jkl_disclose'][0], 'none' ); ?>>
                    <span class="note"><?php _e('No Connection', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-aff-type" id="jkl-aff-type">
                    <input type="radio" name="jkl_disclose" id="jkl-disclose-aff" value="affiliate" <?php if ( isset( $jklrv_stored_meta['jkl_disclose'] ) ) checked( $jklrv_stored_meta['jkl_disclose'][0], 'affiliate' ); ?>>
                    <span class="note"><?php _e('Affiliate Link', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-sample-type" id="jkl-sample-type">
                    <input type="radio" name="jkl_disclose" id="jkl-disclose-sample" value="sample" <?php if ( isset( $jklrv_stored_meta['jkl_disclose'] ) ) checked( $jklrv_stored_meta['jkl_disclose'][0], 'sample' ); ?>>
                    <span class="note"><?php _e('Review or Sample', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-sponsored-type" id="jkl-sponsored-type">
                    <input type="radio" name="jkl_disclose" id="jkl-disclose-sponsor" value="sponsored" <?php if ( isset( $jklrv_stored_meta['jkl_disclose'] ) ) checked( $jklrv_stored_meta['jkl_disclose'][0], 'sponsored' ); ?>>
                    <span class="note"><?php _e('Sponsored Post', 'jkl-reviews/languages')?></span>
                </label>
                </div>
                <div class="radio">
                <label for="jkl-shareholder-type" id="jkl-shareholder-type">
                    <input type="radio" name="jkl_disclose" id="jkl-disclose-shareholder" value="shareholder" <?php if ( isset( $jklrv_stored_meta['jkl_disclose'] ) ) checked( $jklrv_stored_meta['jkl_disclose'][0], 'shareholder' ); ?>>
                    <span class="note"><?php _e('Employee/Shareholder', 'jkl-reviews/languages')?></span>
                </label>
                </div>
            </td>
        </tr>
        
        <?php if (isset( $jklrv_stored_meta['jkl_disclose'][0] ) && !checked( $jklrv_stored_meta['jkl_disclose'][0], 'remove' ) ) { ?>
        <tr><td colspan="2"><div class="divider-lite"></div></td></tr>
        
        <tr>
            <td class="left-label">
                <label for="jkl-disclosure-preview" class="jkl_label"><?php _e('Disclosure Preview: ', 'jkl-reviews/languages')?></label>
            </td>
            <td>
                <small class="note"><?php echo wp_kses_post( jkl_get_material_disclosure( $jklrv_stored_meta['jkl_disclose'][0] ) ); ?></small>
            </td>
        </tr>
        <?php 
        
            } // End Disclosure Type check
        } // End Show Material Disclosure from WP Options page check 
            
        ?>
        
    </table>

    <?php
} 


/*
 * ##### 3 #####
 * Third, use the WP IMAGE MANAGER (i.e. load Image Management JS)
 */

function jklrv_image_enqueue() {
    // Determine the current Post type
    global $typenow;
    
    if( $typenow == 'post' ) {
        wp_enqueue_media();
        
        // Registers and enqueues the required JS
        wp_register_script( 'upload-image', plugin_dir_url( __FILE__ ) . 'js/upload-image.js', array( 'jquery' ) );
        wp_localize_script( 'upload-image', 'jkl_review_cover',
                array(
                    'title' => __( 'Select a Cover', 'jkl-reviews/languages' ),
                    'button' => __( 'Use this Cover', 'jkl-reviews/languages' ),
                )
        );
        wp_enqueue_script( 'upload-image' );
        
        wp_register_script( 'box-style', plugin_dir_url( __FILE__ ) . 'js/box-style.js', array( 'jquery' ) );
        wp_enqueue_script( 'box-style' );
    }
}


/*
 * ##### 4 #####
 * Fourth, Save the custom metadata
 */
function jkl_save_review_metabox($post_id) {
    
    /*
     * Ref: WP Codex: http://codex.wordpress.org/Function_Reference/add_meta_box
     * Verify this came from our screen and with proper authorization and that we're ready to save.
     */
    
    // Check if nonce is set
    if ( !isset( $_POST['jklrv_nonce'] ) ) return;
    
    // Verify the nonce is valid
    if ( !wp_verify_nonce( $_POST['jklrv_nonce'], basename(__FILE__) ) ) return;
    
    // Check for autosave (don't save metabox on autosave)
    if ( defined ('DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    
    // Check user's editing permissions
    if ( !current_user_can( 'edit_page', $post_id ) ) return;

    
    /*
     * After all those checks, save. TODO: Sanitize
     */
    
    // Save the Review Type (radio button selection)
    if( isset($_POST[ 'jkl_radio' ] ) ) {
        update_post_meta( $post_id, 'jkl_radio', $_POST['jkl_radio'] ); // Unnecessary sanitization/validation?
    }

    // Save the Cover: Checks for input and saves image if needed
    if( isset($_POST[ 'jkl_review_cover' ] ) ) {
        update_post_meta( $post_id, 'jkl_review_cover', $_POST['jkl_review_cover'] ); // Performing esc_url on all outputs
    } 

    // Save the Title: Checks for input and sanitizes/saves if needed
    if( isset($_POST['jkl_review_title'] ) ) {
        update_post_meta( $post_id, 'jkl_review_title', sanitize_text_field( $_POST['jkl_review_title'] ) );
    } 

    // Save the Author:
    if( isset($_POST['jkl_review_author'] ) ) {
        update_post_meta( $post_id, 'jkl_review_author', sanitize_text_field( $_POST['jkl_review_author'] ) );
    } 

    // Save the Series:
    if( isset($_POST['jkl_review_series'] ) ) {
        update_post_meta( $post_id, 'jkl_review_series', sanitize_text_field( $_POST['jkl_review_series'] ) );
    }

    // Save the Category:
    if( isset($_POST['jkl_review_category'] ) ) {
        update_post_meta( $post_id, 'jkl_review_category', sanitize_text_field( $_POST['jkl_review_category'] ) );
    } 

    // Save the Rating:
    if( isset($_POST['jkl_review_rating'] ) ) {
        update_post_meta( $post_id, 'jkl_review_rating', (float) $_POST['jkl_review_rating'] );
    } 

    // Save the Summary:
    if( isset($_POST['jkl_review_summary_area'] ) ) {
        update_post_meta( $post_id, 'jkl_review_summary_area', $_POST['jkl_review_summary_area'] ); // Performing wp_kses_post on all outputs
    } 

    // Links and Options Below:
    // Save the Links:
    if( isset($_POST['jkl_review_affiliate_uri'] ) ) {
        update_post_meta( $post_id, 'jkl_review_affiliate_uri', $_POST['jkl_review_affiliate_uri'] ); // Performing esc_url on all outputs
    }
    if( isset($_POST['jkl_review_product_uri'] ) ) {
        update_post_meta( $post_id, 'jkl_review_product_uri', $_POST['jkl_review_product_uri'] ); // Performing esc_url on all outputs
    }
    if( isset($_POST['jkl_review_author_uri'] ) ) {
        update_post_meta( $post_id, 'jkl_review_author_uri', $_POST['jkl_review_author_uri'] ); // Performing esc_url on all outputs
    }
    if( isset($_POST['jkl_review_resources_uri'] ) ) {
        update_post_meta( $post_id, 'jkl_review_resources_uri', $_POST['jkl_review_resources_uri'] ); // Performing esc_url on all outputs
    }
    
    // Save the Material Disclosure Type (radio button selection)
    if( isset($_POST[ 'jkl_disclose' ] ) ) {
        update_post_meta( $post_id, 'jkl_disclose', $_POST['jkl_disclose'] ); // Unnecessary sanitization/validation?
    }
}


/*
 * ##### 5 #####
 * Fifth, just display the review content straight up on a Post
 */
function jkl_display_review_box( $content ) {
    
    /* 
     * Dunno why I have to do this, but it's the only way it seemed to work.
     * Get the global post, and store the post_content in $content and custom 
     * metadata in $jklrv_stored_meta
     */
    global $post;
    $content = $post->post_content; // Retrieve content... I guess
    $jklrv_stored_meta = get_post_meta( get_the_ID() ); // Retrieve Post meta info

    print_r($jklrv_stored_options);
    
    // If this is only a single Post and a Review Type is chosen, modify the content and return it
    if ( is_single() && !empty( $jklrv_stored_meta['jkl_radio'] ) ) {
        
        $jklrv_stored_options = get_option( 'jklrv_plugin_options' ); // Retrieve WP Options stored in the Options Page
        $color = $jklrv_stored_options[ 'jklrv_color_scheme' ];
        $style = $jklrv_stored_options[ 'jklrv_display_style' ];
       
        // Get the appropriate string to display the correct FontAwesome icon per Review Type
        $jklrv_fa_icon = jkl_get_fa_icon( $jklrv_stored_meta['jkl_radio'][0] );
        // Get the correct number of FontAwesome stars string
        $jklrv_fa_rating = jkl_get_fa_rating( $jklrv_stored_meta['jkl_review_rating'][0] );

        /*
         * By the way, don't forget, this is how to add images from your plugin directory
         * <img src="' . plugins_url( 'imgs/' . $jklrv_stored_meta['jkl_radio'][0] . '-dk.png', __FILE__ ) . '" alt="Product link" />
         */
        
        // Create a string to hold our Review box display string (code)
        $jkl_thebox = '<div id="jkl_thebox" class="' . $style . '">';
        
        // Review box header
        $jkl_thebox .= '<div id="jkl_review_box" class="' . $style . '"><div id="jkl_review_box_head" class="' . $color . '">';  
            
            // Display the FontAwesome Review Type icon
            $jkl_thebox .= '<i id="jkl_fa_icon" class="fa fa-' . $jklrv_fa_icon . '"></i>';

            // If there's a Category set, display it, otherwise, display nothing (only the Review Type icon)
            if ( $jklrv_stored_meta['jkl_review_category'][0] !== '' )
                $jkl_thebox .= '<p id="jkl_review_box_categories">' . esc_attr( $jklrv_stored_meta['jkl_review_category'][0] ) . '</p>';
            $jkl_thebox .= '</div>'; // End review box head

        // Review box body
        $jkl_thebox .= '<div id="jkl_review_box_body">';

            // If there's no Cover Image set, just show a larger Review Type icon
            if ( $jklrv_stored_meta['jkl_review_cover'][0] === '' ) {
                $jkl_thebox .= '<h1 id="jkl_review_box_cover" class="fa fa-' . $jklrv_fa_icon . '"></h1>';
            } else {
                $jkl_thebox .= '<img id="jkl_review_box_cover" src=' . esc_url( $jklrv_stored_meta['jkl_review_cover'][0] ) . ' alt="' . esc_attr( $jklrv_stored_meta['jkl_review_title'][0] ) . '" />';
            }

            // This is where Review data goes
            $jkl_thebox .= '<div id="jkl_review_box_info">';

                // Show the title (since we already checked for it before showing the Review box itself)
                $jkl_thebox .= '<p><strong>' . esc_attr( $jklrv_stored_meta['jkl_review_title'][0] ) . '</strong></p>'; // Title

                // Check all the other info and if present, show it, but if not, don't show it
                if ( $jklrv_stored_meta['jkl_review_author'][0] !== '' )
                    $jkl_thebox .= jkl_get_author_link($jklrv_stored_meta['jkl_review_author'][0], $jklrv_stored_meta['jkl_review_author_uri'][0] );  // Run the function to return the Author name with OR without a link
                if ( $jklrv_stored_meta['jkl_review_series'][0] !== '' )
                    $jkl_thebox .= '<p>' . __( 'Series', 'jkl-reviews/languages' ) . ': ' . esc_attr( $jklrv_stored_meta['jkl_review_series'][0] ) . '</p>'; // Series
                if ( $jklrv_stored_meta['jkl_review_rating'][0] != 0 )
                    $jkl_thebox .= '<p>' . $jklrv_fa_rating . '<span>' . $jklrv_stored_meta['jkl_review_rating'][0] . ' ' . __( 'Stars', 'jkl-reviews/languages') . '</span></p>'; // Rating

                // Check that there's AT LEAST ONE external link. If not, don't even create the links box.
                if ( $jklrv_stored_meta['jkl_review_affiliate_uri'][0] !== '' or $jklrv_stored_meta['jkl_review_homepage_uri'][0] !== '' or $jklrv_stored_meta['jkl_review_authorpage_uri'][0] !== '' or $jklrv_stored_meta['jkl_review_resources_uri'][0] !== '' ) {
                $jkl_thebox .= '<div id="jkl_review_box_links_box" class="' . $style . '">'; // Links box

                    // Check all the links and if present, show them, if not, don't show them
                    if ( $jklrv_stored_meta['jkl_review_affiliate_uri'][0] !== '' )
                        $jkl_thebox .= '<a class="fa fa-dollar" href="' . esc_url( $jklrv_stored_meta['jkl_review_affiliate_uri'][0] ) . '"> ' . __( 'Purchase', 'jkl-reviews/languages') . '</a>'; // Affiliate link
                    if ( $jklrv_stored_meta['jkl_review_product_uri'][0] !== '' )
                        $jkl_thebox .= '<a class="fa fa-' . $jklrv_fa_icon . '" href="' . esc_url( $jklrv_stored_meta['jkl_review_product_uri'][0] ) . '"> ' . __( 'Home Page', 'jkl-reviews/languages') . '</a>'; // Product link
                    if ( $jklrv_stored_meta['jkl_review_author_uri'][0] !== '' )
                        $jkl_thebox .= '<a class="fa fa-user" href="' . esc_url( $jklrv_stored_meta['jkl_review_author_uri'][0] ) . '"> ' . __( 'Author Page', 'jkl-reviews/languages') . '</a>'; // Author page link
                    if ( $jklrv_stored_meta['jkl_review_resources_uri'][0] !== '' )
                        $jkl_thebox .= '<a class="fa fa-link" href="' . esc_url( $jklrv_stored_meta['jkl_review_resources_uri'][0] ) . '"> ' . __( 'Resources', 'jkl-reviews/languages') . '</a>'; // Resources page link
                $jkl_thebox .= '</div>'; // End links box
                } // End links box IF check

            $jkl_thebox .= '</div>'; // End review info box
        $jkl_thebox .= '</div><div class="jkl_clear"></div></div>'; // End review box body & box (clear is added to give sufficient height to the background-color of taller boxes)

        // Check to see if there's a summary. If not, don't display anything.
        if ( $jklrv_stored_meta['jkl_review_summary_area'][0] !== '' )
            $jkl_thebox .= '<div class="jkl_summary ' . $style . '"><p><strong>' . __( 'Summary', 'jkl-reviews/languages') . '</strong></p><p><em>' . wp_kses_post( $jklrv_stored_meta['jkl_review_summary_area'][0] ) . '</em></p></div>';

        // Print the Material Disclosure if one has been assigned.
        if ( $jklrv_stored_options[ 'jklrv_display_disclosure' ] ) // Only show if you chose to display the Disclosure from the WP Options (default = false)
            $jkl_thebox .= '<div class="jkl_disclosure ' . $style . '"><small>' . jkl_get_material_disclosure ( $jklrv_stored_meta['jkl_disclose'][0] ) . '</small></div>';
        
        $jkl_thebox .= '<div class="jkl_credit"><a href="http://www.jekkilekki.com"><img src="' . plugins_url( 'imgs/logofor-' . $jklrv_stored_options['jklrv_display_style'] . '.svg', __FILE__ ) . '" alt="Coder Credit" /></a></div>';
        
        $jkl_thebox .= '</div>'; // End #jkl_thebox
        
        // Append the Review box to the $content
        $content = $jkl_thebox . $content;
    }       
    
    // Return the content
    return $content;
}


/* 
 * ##### 6 #####
 * Helper functions for the rest of the code. No WordPress hooks needed here.
 */

/*
 * Take the current Review Type value (stored in the radio button) and return a 
 * string that corresponds with the FontAwesome icon linked to that type.
 */
function jkl_get_fa_icon( $name ) {
    switch( $name ) {
        case 'book' : return 'book';
        case 'audio' : return 'headphones';
        case 'video' : return 'play-circle';
        case 'course' : return 'pencil-square-o';
        case 'product' : return 'archive';
        case 'service' : return 'gift';
        default : return 'star';
    }
}

/*
 * This function returns either the author's name WITH a link (if there is one), 
 * or without if no author link is saved
 */
function jkl_get_author_link( $author, $authorlink ) {
    if ( $authorlink == '' ) {
        return '<p><em>' . __( 'by', 'jkl-reviews/languages' ) . ': ' . esc_attr( $author ) . '</em></p>';
    } else {
        return '<p><em>' . __( 'by', 'jkl-reviews/languages' ) . ': <a href="' . esc_attr( $authorlink ) . '">' . esc_attr( $author ) . '</a></em></p>';
    }
}

/*
 * Get the rating value (input via the range slider) and return a string of 
 * FontAwesome star icons that correspond to that numeric value.
 */
function jkl_get_fa_rating( $number ) {
    switch( $number ) {
        case 0 : return '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>';
        case 0.5 : return '<i class="fa fa-star-half-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>';
        case 1 : return '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>';
        case 1.5 : return '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star-half-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>';
        case 2 : return '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>';
        case 2.5 : return '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star-half-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>';
        case 3 : return '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>';
        case 3.5 : return '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star-half-o"></i>'
                    . '<i class="fa fa-star-o"></i>';
        case 4 : return '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star-o"></i>';
        case 4.5 : return '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star-half-o"></i>';
        case 5 : return '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>'
                    . '<i class="fa fa-star"></i>';
        default: return '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i>'
                    . '<i class="fa fa-star-o"></i><span>' . __( 'No rating available.', 'jkl-reviews/languages') . '</span>';
    }
}


/*
 * Return the appropriate Material Disclosure based on the type selected in the Meta Box
 */
function jkl_get_material_disclosure( $type ) {
    switch( $type ) {
        case 'none' :
             $disclosure = __( 'I have received no compensation of any kind for writing this post, '
                . 'nor do I have any connection with the brands, products, or services mentioned. ', 'jkl-reviews/languages' );
            break;
        case 'affiliate' :
            $disclosure = __( 'Some of the links above are "affiliate links." This means that '
                . 'I will receive a small commission if you click on and purchase the item. Nevertheless, ', 'jkl-reviews/languages' );
            break;
        case 'sample' :
            $disclosure = __( 'I received one or more of the products or services mentioned '
                . 'above in the hope that I would mention it on my blog. Nevertheless, ', 'jkl-reviews/languages' );
            break;
        case 'sponsored' :
            $disclosure = __( 'This is a "sponsored post." The company who sponsored it '
                . 'compensated me in some way to write it. Nevertheless, ', 'jkl-reviews/languages' );
            break;
        case 'shareholder' :
            $disclosure = __( 'I am an employee or shareholder of the company that produced '
                . 'this product. Nevertheless, ', 'jkl-reviews/languages' );
            break;
        default :
            $disclosure = '';
    }
    
    return $disclosure . __( 'I only recommend products and services that I personally believe in and '
                . 'use. This disclosure is in accordance with the <a href="http://www.access.gpo.gov/nara/cfr/waisidx_03/16cfr255_03.html"'
                . 'alt="FTC Disclosure Guidelines">FTC\'s 16 CFR, Part 255</a>: "Guides Concerning the Use of Endorsements and '
                . 'Testimonials in Advertising."', 'jkl-reviews/languages' );
}

/* 
 * ##### 7 #####
 * Add the WP Options Page here.
 */
class JKL_Review_Options {
    
    // TODO: Read this: http://codex.wordpress.org/Creating_Options_Pages PERFECT Examples
    
    public $options;
    
    public function __construct() {
        $this->options = get_option( 'jklrv_plugin_options' );
        $this->jkl_register_settings_and_fields();
    }
    
    static public function add_menu_page() {
        // Params (name, menu name, user with access, page_id, callback function to display page contents)
        // The PHP call __FILE__ points to THIS specific file and will be sure our page_id is unique
        add_options_page( 'JKL Reviews Options', __( 'JKL Reviews Options', 'jkl-reviews/languages' ), 'administrator', __FILE__, array('JKL_Review_Options', 'jkl_display_options_page'));
    }
    
    public function jkl_display_options_page() {
        ?>
        <div class="wrap">
            <?php // screen_icon(); // Deprecated function? No icons in any of the menus I can see ?>
            <h2><?php _e( 'JKL Reviews Options', 'jkl-reviews/languages') ?></h2>
            <?php // print_r(get_option('jklrv_plugin_options')); ?>
            <form method="post" action="options.php"> <!-- Add enctype="mutlipart/form-data" if allowing user to upload data -->
                <!-- To add inputs, use the WP Settings API: http://codex.wordpress.org/Settings_API -->
                <?php settings_fields( 'jklrv_plugin_options' ); // WP takes care of security and nonces with this function ?>
                <?php do_settings_sections( __FILE__ ); ?>
                
                <p class="submit">
                    <input name="submit" type="submit" class="button-primary" value="Save Changes" />
                </p>
            </form>
        </div>

        <?php
    }
    
    public function jkl_register_settings_and_fields() {
        
        // Note: WITHIN this function, we don't really have to prefix everything because everything is confined to THIS function and THIS class (one nice benefit of classes right?)
        
        // Doc: http://codex.wordpress.org/Function_Reference/register_setting
        register_setting( 'jklrv_plugin_options', 'jklrv_plugin_options' ); // Params (group name, name, optional callback)
    
        // Doc: http://codex.wordpress.org/Function_Reference/add_settings_section
        add_settings_section( 'jklrv_main_section', __( 'Main Settings', 'jkl-reviews/languages' ), array( $this, 'jklrv_main_section_cb' ), __FILE__ ); // Params (id, title, callback, page)
        // add_settings_section( 'jklrv_cpt_section', __( 'Your Custom Content Types', 'jkl-reviews/languages'), array( $this, 'jklrv_cpt_section_cb' ), __FILE__ );
        
        // Note: You can't access methods within a class without passing an array
        add_settings_field( 'jklrv_display_disclosure', __( 'Show Material Disclosure', 'jkl-reviews/languages' ) , array( $this, 'jklrv_display_disclosure_setting'), __FILE__, 'jklrv_main_section' ); // Params (id, title, callback, page, section)
        add_settings_field( 'jklrv_display_style', __( 'Select Review Box Style', 'jkl-reviews/languages' ), array( $this, 'jklrv_display_style_setting' ), __FILE__, 'jklrv_main_section' );
        add_settings_field( 'jklrv_color_scheme', __( 'Desired Color Scheme', 'jkl-reviews/languages' ), array( $this, 'jklrv_color_scheme_setting' ), __FILE__, 'jklrv_main_section' );
        // add_settings_field( 'jklrv_cpt_option', __( 'Use JKL Reviews Post Type', 'jkl-reviews/languages' ), array( $this, 'jklrv_cpt_option_setting' ), __FILE__, 'jklrv_cpt_section' );
        
    }
    
    public function jklrv_main_section_cb() {
        // optional
    }
    public function jklrv_cpt_section_cb() {
        // optional
    }
    
    /*
     * 
     * Inputs
     * 
     */
    
    // Display Material Disclosures?
    public function jklrv_display_disclosure_setting() {
        $options = get_option( 'jklrv_plugin_options' );
        ?>
        <input type='checkbox' id='jklrv_plugin_options[jklrv_display_disclosure]' name='jklrv_plugin_options[jklrv_display_disclosure]' value='1' <?php checked( $options['jklrv_display_disclosure'], 1 ); ?> />
        <label for='jklrv_plugin_options[jklrv_display_disclosure]' class='note'>
            <?php _e( 'For US users to comply with <a href="http://www.access.gpo.gov/nara/cfr/waisidx_03/16cfr255_03.html">FTC regulations</a> regarding "Endorsements and Testimonials in Advertising."', 'jkl-reviews/languages') ?>
        </label>
        
        <?php
        if( isset( $options['jklrv_display_disclosure'] ) )
            echo "<br /></br><div id='jkl-options-sample-disclosure' class=" . $options['jklrv_display_style'] . "><strong>Example Disclosure</strong><p><small>" . jkl_get_material_disclosure( 'affiliate' ) . "</small></p></div>";
    }
    
    // Dark or Light Scheme
    public function jklrv_display_style_setting() {
        $items = array( 'Light', 'Dark' );
        echo "<select name='jklrv_plugin_options[jklrv_display_style]'>";
        
        foreach( $items as $item ) {
            $selected = ( $this->options['jklrv_display_style'] === $item ) ? 'selected="selected"' : '';
            echo "<option value='$item' $selected>$item</option>";
        }
        echo "</select>";
    }
    
    // Color Scheme
    public function jklrv_color_scheme_setting() {
        $items = array( 'Blue', 'Slate', 'Brown', 'Burgundy', 'Beige', 'Camel', 'Sand', 'Mud' );
        echo "<select name='jklrv_plugin_options[jklrv_color_scheme]'>";
        
        foreach( $items as $item ) {
            $selected = ( $this->options['jklrv_color_scheme'] === $item ) ? 'selected="selected"' : '';
            echo "<option value='$item' $selected>$item</option>";
        }
        echo "</select>";
    }
    
    // Use Custom Post Type?
    public function jklrv_cpt_option_setting() {
        $options = get_option( 'jklrv_plugin_options' );
        ?>
        <input type='checkbox' id='jklrv_plugin_options[jklrv_cpt_option]' name='jklrv_plugin_options[jklrv_cpt_option]' value='1' <?php checked( $options['jklrv_cpt_option'], 1 ); ?> />
        <label for='jklrv_plugin_options[jklrv_cpt_option]' class='note'><?php _e('Enable JKL Reviews Custom Post Type for this site. <a href="#">Learn More</a>', 'jkl-reviews/languages') ?></label>
    <?php
    }
}

