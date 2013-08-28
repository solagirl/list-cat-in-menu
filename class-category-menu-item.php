<?php
class WPCM_Category_Menu_Item {
       
    public function __construct() {
        add_filter('wp_edit_nav_menu_walker', array( $this, 'custom_menu_walker') );
        add_action('wp_update_nav_menu_item', array( $this, 'update_nav_menu_item' ), 10, 3);
        add_filter('wp_nav_menu_objects', array( $this,'insert_category_list' ), 10, 2);
        add_action( 'load-nav-menus.php', array( $this, 'load_resources') );
        add_action( 'wp_head', array( $this, 'styles') );
    }
    
    function load_resources(){
        add_action( 'admin_footer', array( $this, 'scripts'), 20 );        
    }
    
    function scripts(){
        ?>
<script>
jQuery( 'document' ).ready( function($){ 
    $('#menu-to-edit li.menu-item div.wpcm-categorylist-options').hide();
    $('#menu-to-edit li.menu-item  p.field-categorylist').each( function(){
        if( $(this).find('input.field-categorylist-trigger').is(':checked') ){
            $(this).next().show();
        }
        $(this).find( 'input.field-categorylist-trigger' ).click( function(){
            if( $(this).is(':checked')) 
                $(this).parent().parent().next().slideDown();
            else 
                $(this).parent().parent().next().slideUp();
        });
    });
});
</script>
        <?php
    }
    
    function styles(){
        $width = get_option( 'wpcm-width');
        $column = get_option( 'wpcm-column');
        ?>
<style>
    <?php if( !empty( $width ) ) : ?>
    li.catlist ul{
        width: <?php echo $width; ?> !important;
    }
    <?php endif; ?>
    <?php if( !empty( $column ) && $column > 1) :
        $column_margin = 2;
        $column_width = round( (100/$column-$column_margin), 0 );
    ?>
    li.catlist ul li{
        width:<?php echo $column_width; ?>%;
        margin-right: <?php echo $column_margin; ?>%;
        margin-left:0;
        float: left;
        max-width: 100%;
    }
    li.catlist ul li a span{
        padding-left:1em;
    }
    <?php endif; ?>
</style>
        <?php
    }
    
    function custom_menu_walker() {
        return 'WPCM_Category_Menu_Walker';
    }
    
    /**
     * Save menu options as post meta
     * 
     * @param int $menu_id
     * @param int $menu_item_db_id
     * @param array $menu_item_data
     * @return void
     */
    function update_nav_menu_item($menu_id = 0, $menu_item_db_id = 0, $menu_item_data = array()) {
        if (!empty($_POST['menu-item-db-id'])) {
            update_post_meta( $menu_item_db_id, 'wpcm-trigger', ( empty($_POST['menu-item-categorylist'][$menu_item_db_id]) ? "" : 'on') );
            update_post_meta( $menu_item_db_id, 'wpcm-showpostnumber', ( empty($_POST['menu-item-categorylist-postnumber'][$menu_item_db_id]) ? "" : 'on') );
            if( in_array( $_POST['menu-item-categorylist-all'][$menu_item_db_id], array( 'top', 'all', 'wc-top', 'wc-all')) 
                    || (int)$_POST['menu-item-categorylist-all'][$menu_item_db_id] > 0
                    || strpos($_POST['menu-item-categorylist-all'][$menu_item_db_id], 'wc-')  !== false )
                update_post_meta( $menu_item_db_id, 'wpcm-display', $_POST['menu-item-categorylist-all'][$menu_item_db_id] );
            
            update_post_meta( $menu_item_db_id, 'wpcm-number', ( empty($_POST['menu-item-categorylist-number'][$menu_item_db_id]) ? "" : $_POST['menu-item-categorylist-number'][$menu_item_db_id]) );
            update_post_meta( $menu_item_db_id, 'wpcm-orderby', ( in_array( $_POST['menu-item-categorylist-orderby'][$menu_item_db_id], array('id', 'name','slug', 'count')) ? $_POST['menu-item-categorylist-orderby'][$menu_item_db_id] : 'name' ) );
            update_post_meta( $menu_item_db_id, 'wpcm-order', ( in_array( $_POST['menu-item-categorylist-order'][$menu_item_db_id], array('ASC', 'DESC')) ? $_POST['menu-item-categorylist-order'][$menu_item_db_id] : 'ASC' ) );
            if( isset($_POST['menu-item-categorylist'][$menu_item_db_id]) && $_POST['menu-item-categorylist'][$menu_item_db_id] == 'on') {
                update_option( 'wpcm-column', (empty($_POST['menu-item-categorylist-column'][$menu_item_db_id]) ? "" : $_POST['menu-item-categorylist-column'][$menu_item_db_id]) );
                update_option( 'wpcm-width', (empty($_POST['menu-item-categorylist-width'][$menu_item_db_id]) ? "" : $_POST['menu-item-categorylist-width'][$menu_item_db_id]) );
            }
        }
    }
    
    /**
     * Update the menu with category list
     * 
     * @param array $sorted_menu_items
     * @param array $args
     * @return string
     */
    function insert_category_list($sorted_menu_items, $args) {
        foreach ($sorted_menu_items as $key => $item) {
            if ( get_post_meta($item->db_id, "wpcm-trigger", true) == 'on' ) {
                $showall = get_post_meta( $item->db_id, 'wpcm-display', true );
                $child_of = 0;
                if( !in_array( $showall, array( 'all', 'top', 'wc-top', 'wc-all') ) ){
                    if( strpos( $showall, 'wc-') !== false )
                        $child_of = str_replace( 'wc-', '', $showall );
                    else
                        $child_of = $showall;
                }
                $orderby = get_post_meta( $item->db_id, 'wpcm-orderby', true );
                $order = get_post_meta( $item->db_id, 'wpcm-order', true );
                $number = get_post_meta( $item->db_id, 'wpcm-number', true );
                if( empty($number) ) $number = 0;                
                $showpostnumber = get_post_meta($item->db_id, "wpcm-showpostnumber", true );
                
                // Query the categories according to parameters above
                $args = array(
                    'orderby' => $orderby,
                    'order' => $order,                    
                    'number' => $number
                );
                if( $showall == 'top' || $showall == 'wc-top' || $number = 0 ){
                    $args['number'] = 0;
                } 
                if( !empty($child_of) ){
                    $args['child_of'] = (int)$child_of;
                }
                if( in_array( $showall, array('wc-top', 'wc-all')) || strpos( $showall, 'wc-' ) !== false  ){                   
                    $categories = get_terms('product_cat',$args);
                }else
                    $categories = get_categories($args);
                
                if( ( $showall == 'top' || $showall == 'wc-top') ){                     
                    $newcat = array();
                    foreach( $categories as $cat ){
                        if( $cat->parent == 0 ){
                            $newcat[] = $cat;
                        }
                    }
                    if( $number > 0 ){
                        $newcat = array_slice( $newcat, 0, $number );
                    }
                    $categories = $newcat;
                }
                
                $menu_item_parent = $item->db_id;
                $item->classes = array_merge($item->classes, array('catlist'));
                foreach ($categories as $ckey => $citem) {
                    //echo '<pre>'; print_r($citem); echo '</pre>';
                    $citem = wp_setup_nav_menu_item($citem);
                    $citem->menu_item_parent = $menu_item_parent;
                    $citem->classes = array('catlist-item');
                    if( $showpostnumber )
                        $citem->title .= '<span>(' . $citem->count . ')</span>';
                }
                _wp_menu_item_classes_by_context($categories);
                // Append the new menu_items to the menu array that we're building.
                $sorted_menu_items = array_merge($sorted_menu_items, $categories);
            }
        }
        return $sorted_menu_items;
    }

}
?>
