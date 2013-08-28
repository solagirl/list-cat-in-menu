<?php

class WPCM_Category_Menu_Walker extends Walker_Nav_Menu {
    function get_top_level_categories( $term = '' ) {
        $categories = empty($term) ? get_categories( array('number' => 0) ) : get_terms( $term, array('number'=> 0) );       
        $tmp_cat = array();
        $children_cat = array();
        $top_cat = array();
        foreach ($categories as $cat) {
            if ($cat->parent == 0)
                $tmp_cat[] = $cat;
            else
                $children_cat[] = $cat->parent;
        }
        $children_cat = array_unique($children_cat);
        //Remove top level category that doesn't have children
        foreach ($tmp_cat as $cat) {
            if (in_array($cat->term_id, $children_cat)) {
                $top_cat[] = $cat;
            }
        }
        return $top_cat;
    }
    
    /**
     * @see Walker::start_el()
     * @since 3.0.0
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param object $item Menu item data object.
     * @param int $depth Depth of menu item. Used for padding.
     * @param object $args
     */
    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
        global $_wp_nav_menu_max_depth;
        $_wp_nav_menu_max_depth = $depth > $_wp_nav_menu_max_depth ? $depth : $_wp_nav_menu_max_depth;

        $indent = ( $depth ) ? str_repeat("\t", $depth) : '';

        ob_start();
        $item_id = esc_attr($item->ID);
        $removed_args = array(
            'action',
            'customlink-tab',
            'edit-menu-item',
            'menu-item',
            'page-tab',
            '_wpnonce',
        );

        $original_title = '';
        if ('taxonomy' == $item->type) {
            $original_title = get_term_field('name', $item->object_id, $item->object, 'raw');
            if (is_wp_error($original_title))
                $original_title = false;
        } elseif ('post_type' == $item->type) {
            $original_object = get_post($item->object_id);
            $original_title = $original_object->post_title;
        }

        $classes = array(
            'menu-item menu-item-depth-' . $depth,
            'menu-item-' . esc_attr($item->object),
            'menu-item-edit-' . ( ( isset($_GET['edit-menu-item']) && $item_id == $_GET['edit-menu-item'] ) ? 'active' : 'inactive'),
        );

        $title = $item->title;

        if (!empty($item->_invalid)) {
            $classes[] = 'menu-item-invalid';
            /* translators: %s: title of menu item which is invalid */
            $title = sprintf(__('%s (Invalid)'), $item->title);
        } elseif (isset($item->post_status) && 'draft' == $item->post_status) {
            $classes[] = 'pending';
            /* translators: %s: title of menu item in draft status */
            $title = sprintf(__('%s (Pending)'), $item->title);
        }

        $title = (!isset($item->label) || '' == $item->label ) ? $title : $item->label;

        $submenu_text = '';
        if (0 == $depth)
            $submenu_text = 'style="display: none;"';
        ?>
        <li id="menu-item-<?php echo $item_id; ?>" class="<?php echo implode(' ', $classes); ?>">
            <dl class="menu-item-bar">
                <dt class="menu-item-handle">
                <span class="item-title"><span class="menu-item-title"><?php echo esc_html($title); ?></span> <span class="is-submenu" <?php echo $submenu_text; ?>><?php _e('sub item'); ?></span></span>
                <span class="item-controls">
                    <span class="item-type"><?php echo esc_html($item->type_label); ?></span>
                    <span class="item-order hide-if-js">
                        <a href="<?php
        echo wp_nonce_url(
                add_query_arg(
                        array(
                    'action' => 'move-up-menu-item',
                    'menu-item' => $item_id,
                        ), remove_query_arg($removed_args, admin_url('nav-menus.php'))
                ), 'move-menu_item'
        );
        ?>" class="item-move-up"><abbr title="<?php esc_attr_e('Move up'); ?>">&#8593;</abbr></a>
                        |
                        <a href="<?php
        echo wp_nonce_url(
                add_query_arg(
                        array(
                    'action' => 'move-down-menu-item',
                    'menu-item' => $item_id,
                        ), remove_query_arg($removed_args, admin_url('nav-menus.php'))
                ), 'move-menu_item'
        );
        ?>" class="item-move-down"><abbr title="<?php esc_attr_e('Move down'); ?>">&#8595;</abbr></a>
                    </span>
                    <a class="item-edit" id="edit-<?php echo $item_id; ?>" title="<?php esc_attr_e('Edit Menu Item'); ?>" href="<?php
            echo ( isset($_GET['edit-menu-item']) && $item_id == $_GET['edit-menu-item'] ) ? admin_url('nav-menus.php') : add_query_arg('edit-menu-item', $item_id, remove_query_arg($removed_args, admin_url('nav-menus.php#menu-item-settings-' . $item_id)));
            ?>"><?php _e('Edit Menu Item'); ?></a>
                </span>
                </dt>
            </dl>

            <div class="menu-item-settings" id="menu-item-settings-<?php echo $item_id; ?>">
        <?php if ('custom' == $item->type) : ?>
                    <p class="field-url description description-wide">
                        <label for="edit-menu-item-url-<?php echo $item_id; ?>">
            <?php _e('URL'); ?><br />
                            <input type="text" id="edit-menu-item-url-<?php echo $item_id; ?>" class="widefat code edit-menu-item-url" name="menu-item-url[<?php echo $item_id; ?>]" value="<?php echo esc_attr($item->url); ?>" />
                        </label>
                    </p>
        <?php endif; ?>
                <p class="description description-thin">
                    <label for="edit-menu-item-title-<?php echo $item_id; ?>">
        <?php _e('Navigation Label'); ?><br />
                        <input type="text" id="edit-menu-item-title-<?php echo $item_id; ?>" class="widefat edit-menu-item-title" name="menu-item-title[<?php echo $item_id; ?>]" value="<?php echo esc_attr($item->title); ?>" />
                    </label>
                </p>
                <p class="description description-thin">
                    <label for="edit-menu-item-attr-title-<?php echo $item_id; ?>">
        <?php _e('Title Attribute'); ?><br />
                        <input type="text" id="edit-menu-item-attr-title-<?php echo $item_id; ?>" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title[<?php echo $item_id; ?>]" value="<?php echo esc_attr($item->post_excerpt); ?>" />
                    </label>
                </p>
                <p class="field-link-target description">
                    <label for="edit-menu-item-target-<?php echo $item_id; ?>">
                        <input type="checkbox" id="edit-menu-item-target-<?php echo $item_id; ?>" value="_blank" name="menu-item-target[<?php echo $item_id; ?>]"<?php checked($item->target, '_blank'); ?> />
        <?php _e('Open link in a new window/tab'); ?>
                    </label>
                </p>
                <p class="field-css-classes description description-thin">
                    <label for="edit-menu-item-classes-<?php echo $item_id; ?>">
        <?php _e('CSS Classes (optional)'); ?><br />
                        <input type="text" id="edit-menu-item-classes-<?php echo $item_id; ?>" class="widefat code edit-menu-item-classes" name="menu-item-classes[<?php echo $item_id; ?>]" value="<?php echo esc_attr(implode(' ', $item->classes)); ?>" />
                    </label>
                </p>
                <p class="field-xfn description description-thin">
                    <label for="edit-menu-item-xfn-<?php echo $item_id; ?>">
        <?php _e('Link Relationship (XFN)'); ?><br />
                        <input type="text" id="edit-menu-item-xfn-<?php echo $item_id; ?>" class="widefat code edit-menu-item-xfn" name="menu-item-xfn[<?php echo $item_id; ?>]" value="<?php echo esc_attr($item->xfn); ?>" />
                    </label>
                </p>
                <p class="field-description description description-wide">
                    <label for="edit-menu-item-description-<?php echo $item_id; ?>">
        <?php _e('Description'); ?><br />
                        <textarea id="edit-menu-item-description-<?php echo $item_id; ?>" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description[<?php echo $item_id; ?>]"><?php echo esc_html($item->description); // textarea_escaped    ?></textarea>
                        <span class="description"><?php _e('The description will be displayed in the menu if the current theme supports it.'); ?></span>
                    </label>
                </p>

                <!-- start customzation -->
                <p class="field-categorylist description">
                    <label for="edit-menu-item-categorylist-<?php echo $item_id; ?>">
                        <input type="checkbox" class="field-categorylist-trigger" id="edit-menu-item-categorylist-<?php echo $item_id; ?>" value="on" name="menu-item-categorylist[<?php echo $item_id; ?>]"<?php checked(get_post_meta($item_id, "wpcm-trigger", true), 'on'); ?> />
        <?php _e('List all categories under this menu item'); ?>
                    </label>
                </p>
                <div class="wpcm-categorylist-options" style="display:none">
                    <p class="field-categorylist-option description">
                    <?php 
                    if( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
                        $woo_activated = true;
                    ?>
                    <?php _e('Display: ', 'wpcmcat'); ?>
                        <label for="edit-menu-item-categorylist-all-<?php echo $item_id; ?>">
                            <select id="edit-menu-item-categorylist-all-<?php echo $item_id; ?>" name="menu-item-categorylist-all[<?php echo $item_id; ?>]">
                                <option value="top" <?php selected(get_post_meta($item_id, 'wpcm-display', true), 'top'); ?>><?php _e('Top level categories', 'wpcmcat'); ?></option>
                                <option value="all" <?php selected(get_post_meta($item_id, 'wpcm-display', true), 'all'); ?>><?php _e('All Categories', 'wpcmcat'); ?></option>
                                <?php if( $woo_activated ) : ?>
                                <option value="wc-top" <?php selected(get_post_meta($item_id, 'wpcm-display', true), 'wc-top'); ?>><?php _e('WooCommerce Top level product categories', 'wpcmcat'); ?></option>
                                <option value="wc-all" <?php selected(get_post_meta($item_id, 'wpcm-display', true), 'wc-all'); ?>><?php _e('All WooCommerce product categories', 'wpcmcat'); ?></option>                                
                                <?php endif; ?>
                                <?php
                                $top_cat = $this->get_top_level_categories();
                                if (!empty($top_cat)) :
                                    ?>
                                    <optgroup label="Subcategories of">
                                        <?php foreach ($top_cat as $cat) : ?>
                                            <option value="<?php echo $cat->term_id; ?>" <?php selected(get_post_meta($item_id, 'wpcm-display', true), $cat->term_id); ?>><?php echo $cat->name; ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                                <?php unset($top_cat); ?>
                                <?php
                                /* List WooCommerce Categories */
                                
                                if ( $woo_activated ) {
                                    $wc_cat = $this->get_top_level_categories( 'product_cat' );
                                    if (!empty($wc_cat)) :
                                    ?>
                                    <optgroup label="[WooCommerce] Subcategories of:">                                        
                                        <?php foreach ($wc_cat as $cat) : ?>
                                            <option value="wc-<?php echo $cat->term_id; ?>" <?php selected(get_post_meta($item_id, 'wpcm-display', true), 'wc-' . $cat->term_id); ?>><?php echo $cat->name; ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>                                
                                <?php unset( $wc_cat );
                                } ?>
                            </select>                            
                        </label>
                    </p>

                    <p class="field-categorylist-option description">
                        <label for="edit-menu-item-categorylist-postnumber-<?php echo $item_id; ?>">
                            <input type="checkbox" id="edit-menu-item-categorylist-postnumber-<?php echo $item_id; ?>" value="on" name="menu-item-categorylist-postnumber[<?php echo $item_id; ?>]"<?php checked(get_post_meta($item_id, "wpcm-showpostnumber", true), 'on'); ?> />
        <?php _e('Show the number of posts after title', 'wpcmcat'); ?>
                        </label>
                    </p>

                    <p class="field-categorylist-option description">
                        <label for="edit-menu-item-categorylist-number-<?php echo $item_id; ?>">
        <?php _e('How many categories to display: '); ?>
                            <input type="text" id="edit-menu-item-categorylist-number-<?php echo $item_id; ?>" value="<?php echo get_post_meta($item_id, 'wpcm-number', true); ?>" name="menu-item-categorylist-number[<?php echo $item_id; ?>]" size="5" />
                            <br /><?php _e('Leave empty to display all categories', 'wpcmcat'); ?>
                        </label>
                    </p>   

                    <p class="field-categorylist-option description">
                        <label for="edit-menu-item-categorylist-orderby-<?php echo $item_id; ?>">
                                <?php _e('Order By: ', 'wpcmcat'); ?>
                            <select id="edit-menu-item-categorylist-orderby-<?php echo $item_id; ?>" name="menu-item-categorylist-orderby[<?php echo $item_id; ?>]">
                                <?php
                                $orderby_options = array(
                                    'id' => __('Category ID', 'wpcmcat'),
                                    'name' => __('Category Name', 'wpcmcat'),
                                    'slug' => __('Category Slug', 'wpcmcat'),
                                    'count' => __('The Number of Posts', 'wpcmcat')
                                );
                                foreach ($orderby_options as $key => $val) {
                                    echo '<option value="' . $key . '" ' . selected(get_post_meta($item_id, 'wpcm-orderby', true), $key) . '>' . $val . '</option>';
                                }
                                ?>
                            </select>
                        </label>
                    </p>

                    <p class="field-categorylist-option description">
                            <?php _e('In Order of: ', 'wpcmcat'); ?>
                        <select id="edit-menu-item-categorylist-order-<?php echo $item_id; ?>" name="menu-item-categorylist-order[<?php echo $item_id; ?>]">
                            <?php
                            $order_options = array(
                                'ASC' => __('Ascending', 'wpcmcat'),
                                'DESC' => __('Descending', 'wpcmcat')
                            );
                            foreach ($order_options as $key => $val) {
                                echo '<option value="' . $key . '" ' . selected(get_post_meta($item_id, 'wpcm-order', true), $key) . '>' . $val . '</option>';
                            }
                            ?>
                        </select>                        
                    </p>

                    <p class="field-categorylist-option description">
                        <?php _e('Category Submenu Width: ', 'wpcmcat'); ?>
                        <input type="text" id="edit-menu-item-categorylist-width-<?php echo $item_id; ?>" name="menu-item-categorylist-width[<?php echo $item_id; ?>]" value="<?php echo get_option('wpcm-width'); ?>" size="5" />
        <?php _e('eg. 600px, 50em, 50% etc'); ?>
                    </p>

                    <p class="field-categorylist-option description">
                        <?php _e('Display items in: ', 'wpcmcat'); ?>
                        <input type="text" id="edit-menu-item-categorylist-column-<?php echo $item_id; ?>" name="menu-item-categorylist-column[<?php echo $item_id; ?>]" value="<?php echo get_option('wpcm-column'); ?>"  size="5"/>
        <?php _e('columns', 'wpcmcat'); ?>
                    </p>
                </div>
                <!-- end customzation -->

                <p class="field-move hide-if-no-js description description-wide">
                    <label>
                        <span><?php _e('Move'); ?></span>
                        <a href="#" class="menus-move-up"><?php _e('Up one'); ?></a>
                        <a href="#" class="menus-move-down"><?php _e('Down one'); ?></a>
                        <a href="#" class="menus-move-left"></a>
                        <a href="#" class="menus-move-right"></a>
                        <a href="#" class="menus-move-top"><?php _e('To the top'); ?></a>
                    </label>
                </p>

                <div class="menu-item-actions description-wide submitbox">
                        <?php if ('custom' != $item->type && $original_title !== false) : ?>
                        <p class="link-to-original">
                        <?php printf(__('Original: %s'), '<a href="' . esc_attr($item->url) . '">' . esc_html($original_title) . '</a>'); ?>
                        </p>
                    <?php endif; ?>
                    <a class="item-delete submitdelete deletion" id="delete-<?php echo $item_id; ?>" href="<?php
            echo wp_nonce_url(
                    add_query_arg(
                            array(
                        'action' => 'delete-menu-item',
                        'menu-item' => $item_id,
                            ), admin_url('nav-menus.php')
                    ), 'delete-menu_item_' . $item_id
            );
            ?>"><?php _e('Remove'); ?></a> <span class="meta-sep hide-if-no-js"> | </span> <a class="item-cancel submitcancel hide-if-no-js" id="cancel-<?php echo $item_id; ?>" href="<?php echo esc_url(add_query_arg(array('edit-menu-item' => $item_id, 'cancel' => time()), admin_url('nav-menus.php')));
            ?>#menu-item-settings-<?php echo $item_id; ?>"><?php _e('Cancel'); ?></a>
                </div>

                <input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo $item_id; ?>]" value="<?php echo $item_id; ?>" />
                <input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[<?php echo $item_id; ?>]" value="<?php echo esc_attr($item->object_id); ?>" />
                <input class="menu-item-data-object" type="hidden" name="menu-item-object[<?php echo $item_id; ?>]" value="<?php echo esc_attr($item->object); ?>" />
                <input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo $item_id; ?>]" value="<?php echo esc_attr($item->menu_item_parent); ?>" />
                <input class="menu-item-data-position" type="hidden" name="menu-item-position[<?php echo $item_id; ?>]" value="<?php echo esc_attr($item->menu_order); ?>" />
                <input class="menu-item-data-type" type="hidden" name="menu-item-type[<?php echo $item_id; ?>]" value="<?php echo esc_attr($item->type); ?>" />
            </div><!-- .menu-item-settings-->
            <ul class="menu-item-transport"></ul>
        <?php
        $output .= ob_get_clean();
    }
}
?>