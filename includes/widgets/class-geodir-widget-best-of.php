<?php
/**
 * GeoDirectory Best of widget
 *
 * @since 1.3.9
 *
 * @package GeoDirectory
 */

/**
 * GeoDirectory Best of widget widget class.
 *
 * @since 1.3.9
 */
class GeoDir_Widget_Best_Of extends WP_Widget {
    
    /**
     * Register the best of widget with WordPress.
     *
     * @since 1.3.9
     * @since 1.5.1 Changed from PHP4 style constructors to PHP5 __construct.
     */
    public function __construct() {
        $widget_ops = array(
            'classname' => 'geodir_bestof_widget',
            'description' => __( 'GD > Best of widget', 'geodirectory' ),
            'customize_selective_refresh' => true,
            'geodirectory' => true,
            'gd_show_pages' => array(),
        );
        parent::__construct( 'bestof_widget', __( 'GD > Best of widget', 'geodirectory' ), $widget_ops );
    }

    /**
     * Front-end display content for best of widget.
     *
     * @since 1.3.9
     * @since 1.5.1 Added filter to view all link.
     * @since 1.5.1 Declare function public.
     *
     * @param array $args Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance) {
        extract($args);
        /**
         * Filter the best of widget tab layout.
         *
         * @since 1.3.9
         *
         * @param string $instance ['tab_layout'] Best of widget tab layout name.
         */
        $tab_layout = empty($instance['tab_layout']) ? 'bestof-tabs-on-top' : apply_filters('bestof_widget_tab_layout', $instance['tab_layout']);
        echo '<div class="bestof-widget-tab-layout ' . $tab_layout . '">';
        echo $before_widget;
        $loc_terms = geodir_get_current_location_terms();
        if (!empty($loc_terms)) {
            $cur_location = ' : ' . geodir_ucwords(str_replace('-', ' ', end($loc_terms)));
        } else {
            $cur_location = '';
        }

        /**
         * Filter the current location name.
         *
         * @since 1.3.9
         *
         * @param string $cur_location Current location name.
         */
        $cur_location = apply_filters('bestof_widget_cur_location', $cur_location);

        /**
         * Filter the widget title.
         *
         * @since 1.3.9
         *
         * @param string $instance ['title'] The widget title.
         */
        $title = empty($instance['title']) ? wp_sprintf(__('Best of %s', 'geodirectory'), get_bloginfo('name') . $cur_location) : apply_filters('bestof_widget_title', __($instance['title'], 'geodirectory'));

        /**
         * Filter the post type.
         *
         * @since 1.3.9
         *
         * @param string $instance ['post_type'] The post type.
         */
        $post_type = empty($instance['post_type']) ? 'gd_place' : apply_filters('bestof_widget_post_type', $instance['post_type']);

        /**
         * Filter the excerpt type.
         *
         * @since 1.5.4
         *
         * @param string $instance ['excerpt_type'] The excerpt type.
         */
        $excerpt_type = empty($instance['excerpt_type']) ? 'show-desc' : apply_filters('bestof_widget_excerpt_type', $instance['excerpt_type']);


        /**
         * Filter the listing limit.
         *
         * @since 1.3.9
         *
         * @param int $instance ['post_limit'] No. of posts to display.
         */
        $post_limit = empty($instance['post_limit']) ? '5' : apply_filters('bestof_widget_post_limit', $instance['post_limit']);

        /**
         * Filter the category limit.
         *
         * @since 1.3.9
         *
         * @param int $instance ['categ_limit'] No. of categories to display.
         */
        $categ_limit = empty($instance['categ_limit']) ? '3' : apply_filters('bestof_widget_categ_limit', $instance['categ_limit']);
        $use_viewing_post_type = !empty($instance['use_viewing_post_type']) ? true : false;

        /**
         * Filter the use of location filter.
         *
         * @since 1.3.9
         *
         * @param int|bool $instance ['add_location_filter'] Filter listings using current location.
         */
        $add_location_filter = empty($instance['add_location_filter']) ? '1' : apply_filters('bestof_widget_location_filter', $instance['add_location_filter']);

        // set post type to current viewing post type
        if ($use_viewing_post_type) {
            $current_post_type = geodir_get_current_posttype();
            if ($current_post_type != '' && $current_post_type != $post_type) {
                $post_type = $current_post_type;
            }
        }

        if (isset($instance['character_count'])) {
            /**
             * Filter the widget's excerpt character count.
             *
             * @since 1.3.9
             *
             * @param int $instance ['character_count'] Excerpt character count.
             */
            $character_count = apply_filters('bestof_widget_list_character_count', $instance['character_count']);
        } else {
            $character_count = '';
        }

        $category_taxonomy = geodir_get_taxonomies($post_type);

        $term_args = array(
            'hide_empty' => true,
            'parent' => 0
        );

        $term_args = apply_filters('bestof_widget_term_args', $term_args);

        if (is_tax()) {
            $taxonomy = get_query_var('taxonomy');
            $cur_term = get_query_var('term');
            $term_data = get_term_by('name', $cur_term, $taxonomy);
            $term_args['parent'] = $term_data->term_id;
        }

        $terms = get_terms($category_taxonomy[0], $term_args);

        $term_reviews = geodir_count_reviews_by_terms();
        $a_terms = array();
        foreach ($terms as $term) {


            if ($term->count > 0) {
                if (isset($term_reviews[$term->term_id])) {
                    $term->review_count = $term_reviews[$term->term_id];
                } else {
                    $term->review_count = '0';
                }

                $a_terms[] = $term;
            }

        }


        $terms = apply_filters('bestof_widget_sort_terms', geodir_sort_terms($a_terms, 'review_count'), $a_terms);

        $query_args = array(
            'posts_per_page' => $post_limit,
            'is_geodir_loop' => true,
            'post_type' => $post_type,
            'gd_location' => $add_location_filter ? true : false,
            'order_by' => 'high_review'
        );
        if ($character_count >= 0) {
            $query_args['excerpt_length'] = $character_count;
        }

        $layout = array();
        if ($tab_layout == 'bestof-tabs-as-dropdown') {
            $layout[] = $tab_layout;
        } else {
            $layout[] = 'bestof-tabs-as-dropdown';
            $layout[] = $tab_layout;
        }


        echo $before_title . __($title,'geodirectory') . $after_title;

        //term navigation - start
        echo '<div class="geodir-tabs gd-bestof-tabs" id="gd-bestof-tabs" style="position:relative;">';

        $final_html = '';
        foreach ($layout as $tab_layout) {
            $nav_html = '';
            $is_dropdown = ($tab_layout == 'bestof-tabs-as-dropdown') ? true : false;

            if ($is_dropdown) {
                $nav_html .= '<select id="geodir_bestof_tab_dd" class="geodir-select" name="geodir_bestof_tab_dd" data-placeholder="' . esc_attr(__('Select Category', 'geodirectory')) . '">';
            } else {
                $nav_html .= '<dl class="geodir-tab-head geodir-bestof-cat-list">';
                $nav_html .= '<dt></dt>';
            }


            $term_icon = geodir_get_term_icon();
            $cat_count = 0;
            if (!empty($terms)) {
                foreach ($terms as $cat) {
                    $cat_count++;
                    if ($cat_count > $categ_limit) {
                        break;
                    }
                    if ($is_dropdown) {
                        $selected = ($cat_count == 1) ? 'selected="selected"' : '';
                        $nav_html .= '<option ' . $selected . ' value="' . $cat->term_id . '">' . geodir_ucwords($cat->name) . '</option>';
                    } else {
                        if ($cat_count == 1) {
                            $nav_html .= '<dd class="geodir-tab-active">';
                        } else {
                            $nav_html .= '<dd class="">';
                        }
                        $term_icon_url = !empty($term_icon) && isset($term_icon[$cat->term_id]) ? $term_icon[$cat->term_id] : '';
                        $nav_html .= '<a data-termid="' . $cat->term_id . '" href="' . get_term_link($cat, $cat->taxonomy) . '">';
                        $nav_html .= '<img alt="' . $cat->name . ' icon" class="bestof-cat-icon" src="' . $term_icon_url . '"/>';
                        $nav_html .= '<span>';
                        $nav_html .= geodir_ucwords($cat->name);
                        $nav_html .= '<small>';
                        if (isset($cat->review_count)) {
                            $num_reviews = $cat->review_count;
                            if ($num_reviews == 0) {
                                $reviews = __('No Reviews', 'geodirectory');
                            } elseif ($num_reviews > 1) {
                                $reviews = $num_reviews . __(' Reviews', 'geodirectory');
                            } else {
                                $reviews = __('1 Review', 'geodirectory');
                            }
                            $nav_html .= $reviews;
                        }
                        $nav_html .= '</small>';
                        $nav_html .= '</span>';
                        $nav_html .= '</a>';
                        $nav_html .= '</dd>';
                    }
                }
            }

            if ($is_dropdown) {
                $nav_html .= '</select>';
            } else {
                $nav_html .= '</dl>';
            }
            $final_html .= $nav_html;
        }
        if ($terms) {
            echo $final_html;
        }
        echo '</div>';
        //term navigation - end

        //first term listings by default - start
        $first_term = '';
        if ($terms) {
            $first_term = $terms[0];
            $tax_query = array(
                'taxonomy' => $category_taxonomy[0],
                'field' => 'id',
                'terms' => $first_term->term_id
            );
            $query_args['tax_query'] = array($tax_query);
        }

        ?>
        <input type="hidden" id="bestof_widget_post_type" name="bestof_widget_post_type"
               value="<?php echo $post_type; ?>">
        <input type="hidden" id="bestof_widget_excerpt_type" name="bestof_widget_excerpt_type"
               value="<?php echo $excerpt_type; ?>">
        <input type="hidden" id="bestof_widget_post_limit" name="bestof_widget_post_limit"
               value="<?php echo $post_limit; ?>">
        <input type="hidden" id="bestof_widget_taxonomy" name="bestof_widget_taxonomy"
               value="<?php echo $category_taxonomy[0]; ?>">
        <input type="hidden" id="bestof_widget_location_filter" name="bestof_widget_location_filter"
               value="<?php if ($add_location_filter) {
                   echo 1;
               } else {
                   echo 0;
               } ?>">
        <input type="hidden" id="bestof_widget_char_count" name="bestof_widget_char_count"
               value="<?php echo $character_count; ?>">
        <div class="geo-bestof-contentwrap geodir-tabs-content" style="position: relative; z-index: 0;">
            <p id="geodir-bestof-loading" class="geodir-bestof-loading"><i class="fa fa-cog fa-spin"></i></p>
            <?php
            echo '<div id="geodir-bestof-places">';
            if ($terms) {
                $view_all_link = add_query_arg(array('sort_by' => 'rating_count_desc'), get_term_link($first_term, $first_term->taxonomy));
                /**
                 * Filter the page link to view all listings.
                 *
                 * @since 1.5.1
                 *
                 * @param array $view_all_link View all listings page link.
                 * @param array $post_type The Post type.
                 * @param array $first_term The category term object.
                 */
                $view_all_link = apply_filters('geodir_bestof_widget_view_all_link', $view_all_link, $post_type, $first_term);

                echo '<h3 class="bestof-cat-title">' . wp_sprintf(__('Best of %s', 'geodirectory'), $first_term->name) . '<a href="' . esc_url($view_all_link) . '">' . __("View all", 'geodirectory') . '</a></h3>';
            }
            if ($excerpt_type == 'show-reviews') {
                add_filter('get_the_excerpt', 'best_of_show_review_in_excerpt');
            }
            geodir_bestof_places_by_term($query_args);
            if ($excerpt_type == 'show-reviews') {
                remove_filter('get_the_excerpt', 'best_of_show_review_in_excerpt');
            }
            echo "</div>";
            ?>
        </div>
        <?php //first term listings by default - end
        ?>
        <?php echo $after_widget;
        echo "</div>";
    }

    /**
     * Sanitize best of widget form values as they are saved.
     *
     * @since 1.3.9
     * @since 1.5.1 Declare function public.
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['post_type'] = strip_tags($new_instance['post_type']);
        $instance['post_limit'] = strip_tags($new_instance['post_limit']);
        $instance['categ_limit'] = strip_tags($new_instance['categ_limit']);
        $instance['character_count'] = $new_instance['character_count'];
        $instance['tab_layout'] = $new_instance['tab_layout'];
        $instance['excerpt_type'] = $new_instance['excerpt_type'];
        if (isset($new_instance['add_location_filter']) && $new_instance['add_location_filter'] != '')
            $instance['add_location_filter'] = strip_tags($new_instance['add_location_filter']);
        else
            $instance['add_location_filter'] = '0';
        $instance['use_viewing_post_type'] = isset($new_instance['use_viewing_post_type']) && $new_instance['use_viewing_post_type'] ? 1 : 0;
        return $instance;
    }

    /**
     * Back-end best of widget settings form.
     *
     * @since 1.3.9
     * @since 1.5.1 Declare function public.
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance) {
        $instance = wp_parse_args((array)$instance,
            array(
                'title' => '',
                'post_type' => '',
                'post_limit' => '5',
                'categ_limit' => '3',
                'character_count' => '20',
                'add_location_filter' => '1',
                'tab_layout' => 'bestof-tabs-on-top',
                'excerpt_type' => 'show-desc',
                'use_viewing_post_type' => ''
            )
        );
        $title = strip_tags($instance['title']);
        $post_type = strip_tags($instance['post_type']);
        $post_limit = strip_tags($instance['post_limit']);
        $categ_limit = strip_tags($instance['categ_limit']);
        $character_count = strip_tags($instance['character_count']);
        $tab_layout = strip_tags($instance['tab_layout']);
        $excerpt_type = strip_tags($instance['excerpt_type']);
        $add_location_filter = strip_tags($instance['add_location_filter']);
        $use_viewing_post_type = isset($instance['use_viewing_post_type']) && $instance['use_viewing_post_type'] ? true : false;

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'geodirectory');?>

                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                       name="<?php echo $this->get_field_name('title'); ?>" type="text"
                       value="<?php echo esc_attr($title); ?>"/>
            </label>
        </p>

        <p>
            <label
                for="<?php echo $this->get_field_id('post_type'); ?>"><?php _e('Post Type:', 'geodirectory');?>

                <?php $postypes = geodir_get_posttypes();
                /**
                 * Filter the post types to display in widget.
                 *
                 * @since 1.3.9
                 *
                 * @param array $postypes Post types array.
                 */
                $postypes = apply_filters('geodir_post_type_list_in_p_widget', $postypes); ?>

                <select class="widefat" id="<?php echo $this->get_field_id('post_type'); ?>"
                        name="<?php echo $this->get_field_name('post_type'); ?>"
                        onchange="geodir_change_category_list(this)">

                    <?php foreach ($postypes as $postypes_obj) { ?>

                        <option <?php if ($post_type == $postypes_obj) {
                            echo 'selected="selected"';
                        } ?> value="<?php echo $postypes_obj; ?>"><?php $extvalue = explode('_', $postypes_obj);
                            echo geodir_utf8_ucfirst($extvalue[1]); ?></option>

                    <?php } ?>

                </select>
            </label>
        </p>

        <p>

            <label
                for="<?php echo $this->get_field_id('post_limit'); ?>"><?php _e('Number of posts:', 'geodirectory');?>

                <input class="widefat" id="<?php echo $this->get_field_id('post_limit'); ?>"
                       name="<?php echo $this->get_field_name('post_limit'); ?>" type="text"
                       value="<?php echo esc_attr($post_limit); ?>"/>
            </label>
        </p>

        <p>

            <label
                for="<?php echo $this->get_field_id('categ_limit'); ?>"><?php _e('Number of categories:', 'geodirectory');?>

                <input class="widefat" id="<?php echo $this->get_field_id('categ_limit'); ?>"
                       name="<?php echo $this->get_field_name('categ_limit'); ?>" type="text"
                       value="<?php echo esc_attr($categ_limit); ?>"/>
            </label>
        </p>

        <p>
            <label
                for="<?php echo $this->get_field_id('character_count'); ?>"><?php _e('Post Content excerpt character count :', 'geodirectory');?>
                <input class="widefat" id="<?php echo $this->get_field_id('character_count'); ?>"
                       name="<?php echo $this->get_field_name('character_count'); ?>" type="text"
                       value="<?php echo esc_attr($character_count); ?>"/>
            </label>
        </p>
        <p>
            <label
                for="<?php echo $this->get_field_id('tab_layout'); ?>"><?php _e('Tab Layout:', 'geodirectory');?>

                <select class="widefat" id="<?php echo $this->get_field_id('tab_layout'); ?>"
                        name="<?php echo $this->get_field_name('tab_layout'); ?>">

                    <option <?php if ($tab_layout == 'bestof-tabs-on-top') {
                        echo 'selected="selected"';
                    } ?> value="bestof-tabs-on-top"><?php _e('Tabs on Top', 'geodirectory'); ?></option>
                    <option <?php if ($tab_layout == 'bestof-tabs-on-left') {
                        echo 'selected="selected"';
                    } ?> value="bestof-tabs-on-left"><?php _e('Tabs on Left', 'geodirectory'); ?></option>
                    <option <?php if ($tab_layout == 'bestof-tabs-as-dropdown') {
                        echo 'selected="selected"';
                    } ?>
                        value="bestof-tabs-as-dropdown"><?php _e('Tabs as Dropdown', 'geodirectory'); ?></option>
                </select>
            </label>
        </p>

        <p>
            <label
                for="<?php echo $this->get_field_id('excerpt_type'); ?>"><?php _e('Excerpt Type:', 'geodirectory');?>

                <select class="widefat" id="<?php echo $this->get_field_id('excerpt_type'); ?>"
                        name="<?php echo $this->get_field_name('excerpt_type'); ?>">

                    <option <?php if ($excerpt_type == 'show-desc') {
                        echo 'selected="selected"';
                    } ?> value="show-desc"><?php _e('Show Description', 'geodirectory'); ?></option>
                    <option <?php if ($excerpt_type == 'show-reviews') {
                        echo 'selected="selected"';
                    } ?> value="show-reviews"><?php _e('Show Reviews if Available', 'geodirectory'); ?></option>
                </select>
            </label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('add_location_filter'); ?>">
                <?php _e('Enable Location Filter:', 'geodirectory');?>
                <input type="checkbox" id="<?php echo $this->get_field_id('add_location_filter'); ?>"
                       name="<?php echo $this->get_field_name('add_location_filter'); ?>" <?php if ($add_location_filter) echo 'checked="checked"';?>
                       value="1"/>
            </label>
        </p>

        <p>
            <label
                for="<?php echo $this->get_field_id('use_viewing_post_type'); ?>"><?php _e('Use current viewing post type:', 'geodirectory'); ?>
                <input type="checkbox" id="<?php echo $this->get_field_id('use_viewing_post_type'); ?>"
                       name="<?php echo $this->get_field_name('use_viewing_post_type'); ?>" <?php if ($use_viewing_post_type) {
                    echo 'checked="checked"';
                } ?>  value="1"/>
            </label>
        </p>
    <?php
    }
}