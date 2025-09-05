<?php
/**
 * Plugin Name: Custom FAQ Manager
 * Description: A custom FAQ system with categories and dynamic shortcodes.
 * Version: 0.1.0
 * Author: W.A. Oliveira Azevedo - TheTechDodo
 * Author URI: https://thetechdodo.com/
 * Author URI Staging: https://staging-eab2-thetechdodo.wpcomstaging.com/veelgestelde-vragen/
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load Admin Scripts
function custom_faq_admin_scripts($hook) {
    if ($hook !== 'faq_page_custom-faq-management') {
        return;
    }

    wp_enqueue_script(
        'custom-faq-admin-js',
        plugin_dir_url(__FILE__) . 'assets/faq-admin.js',
        array('jquery'),
        '1.1.0',
        true
    );

    wp_localize_script('custom-faq-admin-js', 'customFaqAdmin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'ajax_nonce' => wp_create_nonce('custom_faq_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'custom_faq_admin_scripts');

// Handle AJAX: Edit FAQ
function custom_faq_edit() {
    check_ajax_referer('custom_faq_nonce', 'security');

    // Only allow users with "manage_options" (Admin) permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access'), 403);
    }

    $faq_id = intval($_POST['faq_id']);
    $question = sanitize_text_field($_POST['question']);
    $answer = wp_kses_post($_POST['answer']);

    wp_update_post(array(
        'ID'           => $faq_id,
        'post_title'   => $question,
        'post_content' => $answer,
    ));

    wp_send_json_success();
}
add_action('wp_ajax_custom_faq_edit', 'custom_faq_edit');

// Add FAQ Management Page (BackEnd)
function custom_faq_admin_menu() {
    if (!current_user_can('manage_options')) {
        return;
    }
    add_submenu_page(
        'edit.php?post_type=faq',  // Parent menu (FAQ)
        'Manage FAQs',             // Page title
        'Manage FAQs',             // Menu title
        'manage_options',          // Capability
        'custom-faq-management',   // Menu slug
        'custom_faq_admin_page'    // Function to render the page
    );
}
add_action('admin_menu', 'custom_faq_admin_menu');

// Render FAQ Management Page
function custom_faq_admin_page() {
    ?>
    <div class="wrap">
        <h1>Manage FAQs</h1>
        <form id="custom-faq-form">
            <table class="form-table">
                <tr>
                    <th><label for="faq-question">Question</label></th>
                    <td><input type="text" id="faq-question" name="faq-question" required class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="faq-answer">Answer</label></th>
                    <td><textarea id="faq-answer" name="faq-answer" rows="4" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th><label for="faq-category">Category</label></th>
                    <td>
                        <select id="faq-category" name="faq-category">
                            <option value="">Select a Category</option>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'faq_category',
                                'hide_empty' => false,
                            ));
                            foreach ($categories as $category) {
                                echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                            }
                            ?>
                        </select>
                        <input type="text" id="new-faq-category" name="new-faq-category" placeholder="Or enter a new category">
                    </td>
                </tr>

            </table>
            <button type="button" class="button button-primary" id="save-faq">Add FAQ</button>
        </form>

        <hr>

        <h2>Existing FAQs</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Answer</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="faq-list">
                <?php
                $faq_query = new WP_Query(array(
                    'post_type'      => 'faq',
                    'posts_per_page' => -1,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ));

                if ($faq_query->have_posts()) {
                    while ($faq_query->have_posts()) {
                        $faq_query->the_post();
                        $faq_categories = get_the_terms(get_the_ID(), 'faq_category');
                        $category_names = wp_list_pluck($faq_categories, 'name');
                        ?>
                        <tr data-id="<?php echo get_the_ID(); ?>">
                            <td class="faq-question"><?php echo get_the_title(); ?></td>
                            <td class="faq-answer"><?php echo get_the_content(); ?></td>
                            <td class="faq-category"><?php echo implode(', ', $category_names); ?></td>
                            <td>
                                <button class="edit-faq button">Edit</button>
                                <button class="delete-faq button button-danger" data-id="<?php echo get_the_ID(); ?>">Delete</button>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="4">No FAQs found.</td></tr>';
                }

                wp_reset_postdata();
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Modify the FAQ Post Type to Remove "Add New"
function custom_faq_post_type() {
    $labels = array(
        'name'               => 'FAQs',
        'singular_name'      => 'FAQ',
        'menu_name'          => 'FAQs',
        'name_admin_bar'     => 'FAQ',
        'edit_item'          => 'Edit FAQ',
        'view_item'          => 'View FAQ',
        'all_items'          => 'All FAQs',
        'search_items'       => 'Search FAQs',
        'not_found'          => 'No FAQs found.',
        'not_found_in_trash' => 'No FAQs found in Trash.'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => false,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-editor-help',
        'supports'           => array('title', 'editor'),
        'hierarchical'       => false,
        'show_ui'            => true,
        'show_in_rest'       => true,
        'rewrite'            => array('slug' => 'faqs'),
    );

    register_post_type('faq', $args);
}
add_action('init', 'custom_faq_post_type');
  
// Register FAQ Categories (Taxonomy)
function create_faq_taxonomy() {
  $labels = array(
      'name'              => 'FAQ Categories',
      'singular_name'     => 'FAQ Category',
      'search_items'      => 'Search FAQ Categories',
      'all_items'         => 'All FAQ Categories',
      'edit_item'         => 'Edit FAQ Category',
      'update_item'       => 'Update FAQ Category',
      'add_new_item'      => 'Add New FAQ Category',
      'new_item_name'     => 'New FAQ Category Name',
      'menu_name'         => 'FAQ Categories',
  );

  $args = array(
      'hierarchical'      => true,
      'labels'            => $labels,
      'show_ui'           => true,
      'show_admin_column' => true,
      'query_var'         => true,
      'rewrite'           => array( 'slug' => 'faq-category' ),
  );

  register_taxonomy( 'faq_category', array( 'faq' ), $args );
}
add_action( 'init', 'create_faq_taxonomy' );

// Shortcode to display FAQs dynamically
function display_faqs_shortcode($atts) {
    $atts = shortcode_atts(array(
        'only_show' => '', // Allow multiple categories via shortcode
    ), $atts, 'faqs');

    // Convert multiple categories into an array
    $selected_categories = array_map('trim', explode(',', $atts['only_show']));

    // Get categories based on displayed FAQs
    $faq_args = array(
        'post_type'      => 'faq',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if (!empty($atts['only_show'])) {
        $faq_args['tax_query'] = array(
            array(
                'taxonomy' => 'faq_category',
                'field'    => 'slug',
                'terms'    => $selected_categories, // Allow multiple categories
            ),
        );
    }

    // Fetch the relevant FAQs
    $faq_query = new WP_Query($faq_args);
    $displayed_categories = array();

    if ($faq_query->have_posts()) {
        while ($faq_query->have_posts()) {
            $faq_query->the_post();
            $faq_terms = get_the_terms(get_the_ID(), 'faq_category');

            if ($faq_terms && !is_wp_error($faq_terms)) {
                foreach ($faq_terms as $term) {
                    $displayed_categories[$term->slug] = $term->name;
                }
            }
        }
    }

    wp_reset_postdata();

    // Start output buffering
    ob_start();

    // Add Search & Filter Controls
    ?>
    <div class="faq-controls">
        <input type="text" id="faqSearch" placeholder="Search FAQs...">
        <select id="faqFilter">
            <option value="all">All Categories</option>
            <?php
            foreach ($displayed_categories as $slug => $name) {
                echo '<option value="' . esc_attr($slug) . '">' . esc_html($name) . '</option>';
            }
            ?>
        </select>
    </div>

    <div class="faq-container">
        <?php
        $faq_query = new WP_Query($faq_args);

        if ($faq_query->have_posts()) {
            while ($faq_query->have_posts()) {
                $faq_query->the_post();

                // Get FAQ categories for filtering
                $faq_categories = get_the_terms(get_the_ID(), 'faq_category');
                $category_classes = '';

                if ($faq_categories && !is_wp_error($faq_categories)) {
                    $category_slugs = wp_list_pluck($faq_categories, 'slug');
                    $category_classes = implode(' ', $category_slugs);
                }

                ?>
                <div class="faq-item" data-category="<?php echo esc_attr($category_classes); ?>">
                    <div class="faq-question-outer">
                        <div class="faq-question"><?php the_title(); ?></div>
                        <div class="faq-toggle faq-show">+</div>
                    </div>
                    <div class="faq-answer-outer">
                        <div class="faq-answer"><?php the_content(); ?></div>
                        <div class="faq-toggle faq-hide">-</div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<p>No FAQs found.</p>';
        }

        wp_reset_postdata();
        ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('faqs', 'display_faqs_shortcode');

// Handle AJAX: Add FAQ
function custom_faq_add() {
    check_ajax_referer('custom_faq_nonce', 'security');

    $question = sanitize_text_field($_POST['question']);
    $answer   = wp_kses_post($_POST['answer']);
    $category = intval($_POST['category']);
    $new_category = sanitize_text_field($_POST['new_category']);

    if (!empty($new_category)) {
        $new_term = wp_insert_term($new_category, 'faq_category');
        if (!is_wp_error($new_term)) {
            $category = $new_term['term_id'];
        }
    }

    $new_faq = array(
        'post_title'   => $question,
        'post_content' => $answer,
        'post_status'  => 'publish',
        'post_type'    => 'faq',
    );

    $faq_id = wp_insert_post($new_faq);

    if ($faq_id && $category) {
        wp_set_post_terms($faq_id, array($category), 'faq_category');
    }

    wp_send_json_success();
}
add_action('wp_ajax_custom_faq_add', 'custom_faq_add');

// Handle AJAX: Delete FAQ
function custom_faq_delete() {
    check_ajax_referer('custom_faq_nonce', 'security');

    $faq_id = intval($_POST['faq_id']);
    wp_delete_post($faq_id, true);

    wp_send_json_success();
}
add_action('wp_ajax_custom_faq_delete', 'custom_faq_delete');

// Testing connection with local, git and stading deplo