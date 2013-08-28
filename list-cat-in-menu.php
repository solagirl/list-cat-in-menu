<?php
/**
 * Plugin Name: List Categories In Menu
 * Description: Allows to display categories as sub menu of selected menu item.
 * Author: Sola
 * Author URI: http://www.solagirl.net
 */
require_once( 'class-category-menu-item.php' );
require_once( 'class-category-menu-walker.php' );
new WPCM_Category_Menu_Item();