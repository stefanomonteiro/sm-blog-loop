<?php

/**
 * 
 * Plugin Name: SM_ Blog Post Loop
 * Plugin URI: https://stefanomonteiro.com/wp-plugins
 * Author: Stefano Monteiro
 * Author URI: https://stefanomonteiro.com
 * Version: 1.0.0
 * Description: Loop and display blog posts
 * Text Domain: sm_
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Basic security, prevents file from being loaded directly.
defined('ABSPATH') or die('Cheatin&#8217; uh?');

if (!function_exists('add_sm_blog_loop_shortcode')) {
    function add_sm_blog_loop_shortcode($atts)
    {

        $a = shortcode_atts(array(
            'extra_class'           => '',
            'post__in'              => '',
            'orderby'               => 'menu_order',
            'order'                 =>  'ASC',
            'posts_per_page'        => -1,
            'blog_cat'              => '',
            'blog_tag'              => '',
            'show_related'          => false,
            'hide_filter'           => false
        ), $atts);

        // ! Pass shortcode parameters to variables to be used in WP_Query
        $post__in = [];
        if ($a['post__in']) {
            foreach (explode(',', $a['post__in']) as $post_id) {
                array_push($post__in, $post_id);
            }
        }

        // ! Check if Category is being queried / passed as parameter / or if it should be all parents
        $terms_id = [];
        $category_filter = [];
        if ($a['blog_cat']) {
            foreach (explode(',', $a['blog_cat']) as $term_id) {
                array_push($terms_id, $term_id);
                $category_filter = $terms_id;
            }
        } else {
            $current_query = get_queried_object();
            if (is_a($current_query, 'WP_Term')) {
                $terms_id = $current_query->term_id;
                $category_filter = get_term_children($current_query->term_id, 'category');

            } else {

                if ($current_query->post_type === 'post' && $a['show_related']) {
                    $current_cats = get_the_category($current_query->ID);

                    foreach ($current_cats as $cat) {
                        array_push($terms_id, $cat->term_id);
                    }

                } else {

                    // If $term does not return the WP_Term Object (Main Product Archive Page) get all categories to pass on WP_Query below
                    $categories = get_categories(array(
                        'taxonomy'  => 'category',
                        'parent' => 0
                    ));
                    // var_dump($current_query);
                    foreach ($categories as $category) {
                        array_push($terms_id, $category->term_id);
                    }

                    $category_filter = $terms_id;
                }
            }
        }

        // ! Create Filter
        $blog_filter_html = '';
        $blog_filter_cats = '';
        if ($category_filter && !$post__in && !$a['blog_tag'] && !$a['hide_filter']) {
            $a['extra_class'] = $a['extra_class'] . ' sm_has-filter';

            foreach ($category_filter as $cat_id) {

                $category_name = get_the_category_by_ID($cat_id);
                $blog_filter_cats = $blog_filter_cats . '
                    <li data-filter=".' . str_replace(' ', '', $category_name) . '"> ' . $category_name . ' </li>
                ';
            }

            $blog_filter_html = '<ul>
                                    <li data-filter="*">Todos</li>
                                    ' . $blog_filter_cats . '
                                </ul>';
        }

        // ! Create Product Grid
        // Setup custom query
        $args = array(
            'post__in'              => $post__in,
            'post_type'             => 'post',
            'status'                => 'publish',
            'orderby'               => $a['menu_order'],
            'order'                 => $a['ASC'],
            'posts_per_page'        => $a['posts_per_page'],
            'tag__in'               => $a['blog_tag'],
            'tax_query'             => array(array(
                'taxonomy' => 'category', // The taxonomy name
                'field'    => 'term_id', // Type of field ('term_id', 'slug', 'name' or 'term_taxonomy_id')
                'terms'    => $terms_id, // can be an integer, a string or an array
            )),
        );
        $loop = new WP_Query($args);

        $blog_items = '';
        foreach ($loop->posts as $post) {

            // Get post categories
            $post_categories_string = '';
            $post_categories_html = '';
            foreach (get_the_category($post->ID) as $post_category) {
                // var_dump($post_category);
                $post_categories_string = $post_categories_string . str_replace(' ', '', $post_category->name) . ' ';

                $post_categories_html = $post_categories_html . '
                    <h4><a href="/' . $post_category->slug . '">' . $post_category->name . '</a></h4>
                ';
            }

            $blog_items = $blog_items . '
            <div class="sm_blog-loop--grid_item ' . $post_categories_string . '" >
                <article class="sm_blog-loop--article">
                    <div class="sm_blog-loop--image" >
                        <a href="/' . $post->post_name . '">
                            ' . get_the_post_thumbnail($post->ID, 'large') . '
                        </a>
                        <div class="sm_blog-loop--categories" >
                            ' . $post_categories_html . '
                        </div>
                    </div>
                    <div class="sm_blog-loop--title" >
                        <h3>                
                            <a href="/' . $post->post_name . '">' . $post->post_title . '</a>
                        </h3>
                    </div>
                
                </article>
            </div>
            
            ';
        }


        // ! Returned HTML
        $html = '<div class="sm_blog-loop ' . $a['extra_class'] . '">
                        <div class="sm_blog-loop--filter">
                            ' . $blog_filter_html . '
                        </div>
                        <div class="sm_blog-loop--grid">
                            ' . $blog_items . '
                        </div>
                    </div>';

        // Enqueue
        if (!wp_style_is('sm_blog_loop-css', 'enqueued')) {
            wp_enqueue_style('sm_blog_loop-css');
        }
        if (!wp_script_is('sm_blog_loop-js', 'enqueued')) {
            wp_enqueue_script('sm_blog_loop-js');
        }

        return $html;
    }
}
add_shortcode('sm_blog_loop', 'add_sm_blog_loop_shortcode');

wp_register_style('sm_blog_loop-css', plugin_dir_url(__FILE__) . 'css/sm_blog_loop.css', [], '1.0.0');
wp_register_script('isotope-js', plugin_dir_url(__FILE__) . 'js/isotope.pkgd.min.js', [], '1.0.0', true);
wp_register_script('sm_blog_loop-js', plugin_dir_url(__FILE__) . 'js/sm_blog_loop.js', ['isotope-js'], '1.0.0', true);

// Enqueue Scripts Elementor Editor
if (!function_exists('sm_blog_loop_enqueue_styles_elementor_editor')) {
    function sm_blog_loop_enqueue_styles_elementor_editor()
    {

        if (!wp_style_is('sm_blog_loop-css', 'enqueued')) {
            wp_enqueue_style('sm_blog_loop-css');
        }
    }
}

if (!function_exists('sm_blog_loop_enqueue_scripts_elementor_editor')) {
    function sm_blog_loop_enqueue_scripts_elementor_editor()
    {

        if (!wp_script_is('sm_blog_loop-js', 'enqueued')) {
            wp_enqueue_script('sm_blog_loop-js');
        }
    }
}

// Add Action elementor/preview/enqueue_styles 
add_action('elementor/preview/enqueue_styles', 'sm_blog_loop_enqueue_styles_elementor_editor');
add_action('elementor/preview/enqueue_scripts', 'sm_blog_loop_enqueue_scripts_elementor_editor');
