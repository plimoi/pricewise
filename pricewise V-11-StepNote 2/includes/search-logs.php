<?php
// includes/search-logs.php - Handles logging of search form submissions and displays search logs in admin submenu.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Function to log search form submissions.
 */
function pricewise_log_search() {
    // בדיקה אם מדובר בשליחת טופס חיפוש (נדרשים שדות בסיסיים)
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['destination'], $_POST['checkin_date'], $_POST['checkout_date'] ) ) {
        // השגת ה-URL הנוכחי
        $current_url = ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) ? "https" : "http" ) . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        
        // הכנת רשומת לוג עם תאריך, URL ופרמטרי החיפוש
        $log_entry = array(
            'timestamp' => current_time( 'mysql' ),
            'url'       => $current_url,
            'params'    => $_POST,
        );
        
        // שליפת רשומות לוג קיימות מהאפשרות ב-WP (אם אין – אתחול מערך)
        $logs = get_option( 'pricewise_search_logs', array() );
        if ( ! is_array( $logs ) ) {
            $logs = array();
        }
        
        // הוספת רשומת הלוג החדשה
        $logs[] = $log_entry;
        update_option( 'pricewise_search_logs', $logs );
    }
}
add_action( 'init', 'pricewise_log_search' );

/**
 * Add Search Logs submenu page under PriceWise.
 */
function pricewise_add_search_logs_menu() {
    add_submenu_page(
        'pricewise',               // Parent slug
        'Search logs',             // Page title
        'Search logs',             // Menu title
        'manage_options',          // Capability
        'pricewise-search-logs',   // Menu slug
        'pricewise_search_logs_page' // Callback function
    );
}
add_action( 'admin_menu', 'pricewise_add_search_logs_menu' );

/**
 * Display the Search Logs admin page.
 */
function pricewise_search_logs_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Access denied' );
    }
    
    $logs = get_option( 'pricewise_search_logs', array() );
    ?>
    <div class="wrap">
        <h1>Search Logs</h1>
        <?php if ( empty( $logs ) ) : ?>
            <p>No search logs available.</p>
        <?php else : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>URL</th>
                        <th>Parameters</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( $log['timestamp'] ); ?></td>
                            <td><?php echo esc_html( $log['url'] ); ?></td>
                            <td><pre><?php echo esc_html( print_r( $log['params'], true ) ); ?></pre></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
?>
