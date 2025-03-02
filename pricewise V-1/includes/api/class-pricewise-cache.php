<?php
/**
 * The cache functionality of the plugin.
 *
 * @package    Pricewise
 * @subpackage Pricewise/includes/api
 */

class Pricewise_Cache {

    /**
     * The cache table name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $table_name    The cache table name.
     */
    protected $table_name;

    /**
     * The cache expiry time in seconds.
     *
     * @since    1.0.0
     * @access   protected
     * @var      int    $expiry    The cache expiry time in seconds.
     */
    protected $expiry;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pricewise_cache';
        $this->expiry = intval(get_option('pricewise_cache_expiry', 3600)); // Default: 1 hour
    }

    /**
     * Get cached data.
     *
     * @since    1.0.0
     * @param    string    $key    The cache key.
     * @return   mixed             The cached data or false if not found/expired.
     */
    public function get($key) {
        global $wpdb;
        
        // Clean up expired cache entries
        $this->cleanup();
        
        // Get cached data
        $query = $wpdb->prepare(
            "SELECT cache_value FROM {$this->table_name} WHERE cache_key = %s AND expiry > %s",
            $key,
            current_time('mysql')
        );
        
        $cache_value = $wpdb->get_var($query);
        
        if ($cache_value === null) {
            return false;
        }
        
        return maybe_unserialize($cache_value);
    }

    /**
     * Set data in cache.
     *
     * @since    1.0.0
     * @param    string    $key      The cache key.
     * @param    mixed     $value    The data to cache.
     * @param    int       $expiry   Optional. The expiry time in seconds. Default is the class expiry.
     * @return   boolean             True on success, false on failure.
     */
    public function set($key, $value, $expiry = null) {
        global $wpdb;
        
        // Use default expiry if not specified
        if ($expiry === null) {
            $expiry = $this->expiry;
        }
        
        // Calculate expiry timestamp
        $expiry_timestamp = date('Y-m-d H:i:s', time() + $expiry);
        
        // Serialize data if needed
        $serialized_value = maybe_serialize($value);
        
        // Check if key already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE cache_key = %s", $key)
        );
        
        if ($exists) {
            // Update existing cache entry
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'cache_value' => $serialized_value,
                    'expiry' => $expiry_timestamp,
                ),
                array('cache_key' => $key),
                array('%s', '%s'),
                array('%s')
            );
        } else {
            // Insert new cache entry
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'cache_key' => $key,
                    'cache_value' => $serialized_value,
                    'expiry' => $expiry_timestamp,
                    'created' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%s')
            );
        }
        
        return $result !== false;
    }

    /**
     * Delete cached data.
     *
     * @since    1.0.0
     * @param    string    $key    The cache key.
     * @return   boolean           True on success, false on failure.
     */
    public function delete($key) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('cache_key' => $key),
            array('%s')
        );
        
        return $result !== false;
    }

    /**
     * Clear all cached data.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    public function clear_all() {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        return $result !== false;
    }

    /**
     * Remove expired cache entries.
     *
     * @since    1.0.0
     * @return   int    Number of rows deleted.
     */
    public function cleanup() {
        global $wpdb;
        
        $rows_deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE expiry < %s",
                current_time('mysql')
            )
        );
        
        return $rows_deleted;
    }
}