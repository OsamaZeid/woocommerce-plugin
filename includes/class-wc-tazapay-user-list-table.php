<?php
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 * 
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 * 
 * Our theme for this list table is going to be movies.
 */
class TazaPay_User_List_Table extends WP_List_Table {
    
    /** ************************************************************************
     * Normally we would be querying data from a database and manipulating that
     * for use in your list table. For this example, we're going to simplify it
     * slightly and create a pre-built array. Think of this as the data that might
     * be returned by $wpdb->query()
     * 
     * In a real-world scenario, you would make your own custom query inside
     * this class' prepare_items() method.
     * 
     * @var array 
     **************************************************************************/
    
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'tazapayuser',    //singular name of the listed records
            'plural'    => 'tazapayusers',   //plural name of the listed records
            'ajax'      => false             //does this table support ajax?
        ) );        
    }


    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/

    private function table_data()
    {
        global $wpdb;

        $data = array();
        $userArray = array();

        $tablename      = $wpdb->prefix.'tazapay_user';
        $results = $wpdb->get_results("SELECT * FROM $tablename");

        foreach($results as $result){

            $first_name         = $result->first_name;
            $last_name          = $result->last_name;
            $user_type          = $result->user_type;
            $contact_code       = $result->contact_code;
            $contact_number     = $result->contact_number;
            $country_name       = $result->country;
            $ind_bus_type       = $result->ind_bus_type;
            $business_name      = $result->business_name;
            $partners_customer  = $result->partners_customer_id;
            $created            = $result->created;
            $environment        = $result->environment;
            $account_id         = $result->account_id;
            $countryName        = WC()->countries->countries[$country_name];
          

            if($account_id){
                $data[] = array(
                    'id'                => $result->id,
                    'account_id'        => $account_id,
                    'user_type'         => $user_type,
                    'email'             => $result->email,
                    'first_name'        => $first_name,
                    'last_name'         => $last_name,
                    'contact_code'      => $contact_code,
                    'contact_number'    => $contact_number,
                    'country_name'      => $countryName,
                    'ind_bus_type'      => $ind_bus_type,
                    'created'           => $created,
                    'business_name'     => $business_name,
                    'partners_customer' => $partners_customer,
                    'environment'       => $environment,
                );
            }            
        }            
        return $data;
    }
    function column_default($item, $column_name){

        switch( $column_name ) {
            case 'id':
            case 'account_id':
            case 'user_type':
            case 'email':
            case 'first_name':
            case 'last_name':
            case 'contact_code':
            case 'contact_number':
            case 'country_name':
            case 'ind_bus_type':
            case 'created':
            case 'business_name':
            case 'partners_customer':
            case 'environment':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }        
    }

    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_title($item){
        
        //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&user=%s">Edit</a>','tazapay-user-edit','edit',$item['id']),
            //'delete'    => sprintf('<a href="?page=%s&action=%s&user=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
        );
        
        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['id'],
            /*$2%s*/ $item['id'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
        );
    }

    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        $columns = array(
            //'cb'                => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'             => __( 'ID', 'wc-tp-payment-gateway' ),
            //'id'                => __( 'ID', 'wc-tp-payment-gateway' ),
            'account_id'        => __( 'TazaPay Account UUID', 'wc-tp-payment-gateway' ),
            'user_type'         => __( 'User Type', 'wc-tp-payment-gateway' ),
            'email'             => __( 'Email', 'wc-tp-payment-gateway' ),
            'ind_bus_type'      => __( 'Entity Type', 'wc-tp-payment-gateway' ),
            'first_name'        => __( 'First Name', 'wc-tp-payment-gateway' ),
            'last_name'         => __( 'Last Name', 'wc-tp-payment-gateway' ),
            'business_name'     => __( 'Bussiness Name', 'wc-tp-payment-gateway' ),
            'partners_customer' => __( 'Partners Customer ID', 'wc-tp-payment-gateway' ),
            'contact_code'      => __( 'Contact Code', 'wc-tp-payment-gateway' ),
            'contact_number'    => __( 'Contact Number', 'wc-tp-payment-gateway' ),
            'country_name'      => __( 'Country', 'wc-tp-payment-gateway' ),            
            'environment'       => __( 'Environment', 'wc-tp-payment-gateway' ),            
            'created'           => __( 'Created', 'wc-tp-payment-gateway' ),
        );
        return $columns;
    }

    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns() {
        $sortable_columns = array(
             'title'         => array('id',false),
             'country_name'  => array('country_name',false),
             'environment'   => array('environment',false)
        );
        return $sortable_columns;
    }

    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    // function get_bulk_actions() {
    //     $actions = array(
    //         'delete'    => 'Delete'
    //     );
    //     return $actions;
    // }

    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {
        global $wpdb;
        $table = $wpdb->prefix.'tazapay_user';

        $user_id = isset($_GET['user']);

        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
            $wpdb->delete($table, array('ID'=>$user_id));
            $_GET['msg']="delete";
        }                
    }

    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {
        global $wpdb; //This is used only if making any database queries

        $prefix = $wpdb->prefix;

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 10;        
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();        
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);        
        
        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        //$this->process_bulk_action();        
        
        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example 
         * package slightly different than one you might build on your own. In 
         * this example, we'll be using array manipulation to sort and paginate 
         * our data. In a real-world implementation, you will probably want to 
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */

        $data = $this->table_data();           

        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         * 
         * In a real-world situation involving a database, you would probably want 
         * to handle sorting by passing the 'orderby' and 'order' values directly 
         * to a custom query. The returned data will be pre-sorted, and this array
         * sorting technique would be unnecessary.
         */
        function usort_reorder($a,$b){
          $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'id'; //If no sort, default to title
          $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'DESC'; //If no order, default to asc
          $result = strnatcmp($a[$orderby], $b[$orderby]); //Determine sort order
          return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');        
        
        /*****************************------------------------------------------------------
         * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
         * ******************************************
         * ---------------
         * In a real-world situation, this is where you would place your query.
         *
         * For information on making queries in WordPress, see this Codex entry:
         * http://codex.wordpress.org/Class_Reference/wpdb
         * 
         * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         * ---------------------------------------------------------------------
         **********************************************************************/
                        
        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently 
         * looking at. We'll need this later, so you should always include it in 
         * your own package classes.
         */
        $current_page = $this->get_pagenum();
        
        /**
         * REQUIRED for pagination. Let's check how many items are in our data array. 
         * In real-world use, this would be the total number of items in your database, 
         * without filtering. We'll need this later, so you should always include it 
         * in your own package classes.
         */
        $total_items = count($data);        
        
        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to 
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);    
        
        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $data;        
        
        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
}


/** ************************ REGISTER THE TAZAPAY USER PAGE ****************************
 *******************************************************************************
 * Now we just need to define an admin page. For this example, we'll add a top-level
 * menu item to the bottom of the admin menus.
 */
function tt_add_menu_items(){

    $woocommerce_tz_tazapay_settings = get_option( 'woocommerce_tz_tazapay_settings' );
    $tazapay_payment_method          = $woocommerce_tz_tazapay_settings['enabled'];
    
    if( $tazapay_payment_method == 'yes' ){
        add_submenu_page( 'woocommerce', __('TazaPay Users', 'wc-tp-payment-gateway' ), __('TazaPay Users', 'wc-tp-payment-gateway' ), 'manage_options', 'tazapay-user', 'tazapay_render_list_page' );
        add_submenu_page( '', '', '', 'manage_options', 'tazapay-user-edit', 'tazapay_render_edit_page' );
        add_submenu_page( '', '', '', 'manage_options', 'tazapay-signup-form', 'tazapay_signup_form' );
    }
} 
add_action('admin_menu', 'tt_add_menu_items');

// Form shortcode
function tazapay_signup_form($atts)
{
    require_once plugin_dir_path(__FILE__) . 'shortcodes/tazapay-accountform-shortcode.php';
}

/** *************************** RENDER TAZAPAY USER ********************************
 *******************************************************************************
 * This function renders the admin page and the example list table. Although it's
 * possible to call prepare_items() and display() from the constructor, there
 * are often times where you may need to include logic here between those steps,
 * so we've instead called those methods explicitly. It keeps things flexible, and
 * it's the way the list tables are used in the WordPress core.
 */
function tazapay_render_list_page(){
    
    //Create an instance of our package class...
    $tazapayListTable = new TazaPay_User_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $tazapayListTable->prepare_items();
    
    ?>
    <div class="wrap">        
        <div id="icon-users" class="icon32"><br/></div>
        <h2><?php echo __( 'TazaPay Users', 'wc-tp-payment-gateway' ); ?></h2>
        <div id="response-message">
            <?php if(isset($_GET['msg'])){ ?>
            <div class="notice notice-success">        
            <?php if($_GET['msg'] == 'delete'){ ?>
            <p><?php echo __('TazaPay user successfully deleted.','wc-tp-payment-gateway'); ?></p>
            <?php } ?>
            </div>
            <?php }?>
        </div>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="user-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->

            <?php //$tazapayListTable->search_box( 'search', 'search_id' );?>
            <?php $tazapayListTable->display() ?>
        </form>        
    </div>
    <?php
}

function tazapay_render_edit_page(){

    global $wpdb;   
    $user_id    = $_GET['user'];
    $tablename  = $wpdb->prefix.'tazapay_user';
    $row_user   = $wpdb->get_row("SELECT * FROM $tablename WHERE id = '". $user_id ."'");

    if('edit'===$_REQUEST['action'])
    {    
         $row_user = $wpdb->get_row("SELECT * FROM $tablename WHERE id = '". $user_id ."'");

         if(count($row_user) > 0)
         {              
            $account_id = $row_user->account_id;
            
            if(isset($_POST['submit'])){

                $new_value = !empty($_POST['account_id']) ? $_POST['account_id'] : '';

                $wpdb->query( $wpdb->prepare( "UPDATE $tablename SET account_id = %s WHERE ID = %s", $new_value, $user_id ) );

                $_GET['msg'] = 'updated';
            }
            ?>
             <div class="wrap">
                <h2><?php  echo __('Edit TazaPay Account UUID','wc-tp-payment-gateway'); ?></h2>
                <div id="response-message">
                <?php if($_GET['msg']){ ?>
                <div class="notice notice-success">        
                <?php if($_GET['msg'] == 'updated'){ ?>
                <p><?php echo __('TazaPay Account UUID updated.','wc-tp-payment-gateway'); ?></p>
                <?php } ?>
                </div>
                <?php }?>
                </div>
                <div class="form">
                    <form method="post" action="">
                        <table class="form-table" >
                            <tr valign="top">
                                <th scope="row"><label><?php echo __('TazaPay Account UUID','package'); ?></label></th>
                                <td><input type="text" name="account_id" id="account_id" value="<?php echo $account_id; ?>" placeholder="<?php echo __('TazaPay Account UUID','wc-tp-payment-gateway'); ?>" size="50" required/>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"></th>
                                <td>
                                <input type="submit" name="submit" value="<?php echo __('Update','wc-tp-payment-gateway'); ?>" class="button-primary" />
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
            <?php
         }
    } 
}

add_filter( 'gettext', 'tazapay_nouserfound_keyword' );
/**
  * Change no items found text message.
  *
  * @param $text string 
 
  * @return $text string
  * * * * * * * * * * * * * * * * * * */
function tazapay_nouserfound_keyword( $text ) {

    if( is_admin() ) {
        $text = str_ireplace( 'No items found.', 'No user found.',  $text );
    }
    
    return $text;
}