<?php
/**
 * Cache Manager Class
 * 
 * Handles all database operations for the caching system.
 * Core implementation of the caching system that provides memory caching,
 * database storage, and cache maintenance operations.
 *
 * @package PriceWise
 * @subpackage Cache
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PriceWise_Cache_Manager {
    /**
     * The single instance of the class.
     *
     * @var PriceWise_Cache_Manager
     */
    protected static $_instance = null;
    
    /**
     * Cache table name
     *
     * @var string
     */
    protected $table_name;
    
    /**
     * Whether we're using external object cache
     *
     * @var bool
     */
    protected $using_object_cache;
    
    /**
     * Cache statistics
     *
     * @var array
     */
    protected $stats = array(
        'hits' => 0,
        'misses' => 0,
        'writes' => 0
    );
    
    /**
     * Internal memory cache for frequently accessed items
     * 
     * @var array
     */
    protected $memory_cache = array();
    
    /**
     * Maximum size of memory cache
     * 
     * @var int
     */
    protected $memory_cache_max_size = 100;
    
    /**
     * Main Cache Manager Instance.
     *
     * Ensures only one instance of Cache Manager is loaded or can be loaded.
     *
     * @return PriceWise_Cache_Manager
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor.
     */
    protected function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pricewise_cache';
        $this->using_object_cache = wp_using_ext_object_cache();
        
        // Adjust memory cache size based on available memory
        if (function_exists('memory_get_usage') && function_exists('ini_get')) {
            $memory_limit = ini_get('memory_limit');
            if ($memory_limit) {
                $memory_limit = $this->convert_to_bytes($memory_limit);
                if ($memory_limit > 0) {
                    // If memory limit is more than 128MB, use larger memory cache
                    if ($memory_limit >= 134217728) { // 128MB
                        $this->memory_cache_max_size = 250;
                    }
                    // If memory limit is more than 256MB, use even larger memory cache
                    if ($memory_limit >= 268435456) { // 256MB
                        $this->memory_cache_max_size = 500;
                    }
                }
            }
        }
        
        // Load existing stats
        $saved_stats = get_option('pricewise_cache_stats', array());
        if (!empty($saved_stats)) {
            $this->stats = array_merge($this->stats, $saved_stats);
        }
        
        // Register cleanup hooks
        add_action('wp_scheduled_delete', array($this, 'cleanup_expired_cache'));
    }
    
    /**
     * Convert memory size string to bytes
     * 
     * @param string $size_str String like '128M'
     * @return int Size in bytes
     */
    private function convert_to_bytes($size_str) {
        $size_str = trim($size_str);
        $last = strtolower($size_str[strlen($size_str) - 1]);
        $value = intval($size_str);
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Set up the database table
     */
    public function setup_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if (!$table_exists) {
            // Table doesn't exist, create it directly with SQL
            $sql = "CREATE TABLE {$this->table_name} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                cache_key varchar(255) NOT NULL,
                cache_group varchar(100) NOT NULL DEFAULT 'default',
                cache_value longtext NOT NULL,
                expiration datetime NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY cache_key_group (cache_key, cache_group),
                KEY cache_group (cache_group),
                KEY expiration (expiration),
                KEY created_cache_group (created_at, cache_group),
                KEY group_expiration (cache_group, expiration),
                KEY key_expiration (cache_key, expiration)
            ) $charset_collate;";
            
            // Execute the query directly
            $wpdb->query($sql);
            
            // Check for errors
            if ($wpdb->last_error) {
                error_log('PriceWise cache table creation error: ' . $wpdb->last_error);
                return false;
            }
            
            return true;
        }
        
        return true;
    }
    
    /**
     * Get an item from the cache.
     *
     * @param string $key    Cache key.
     * @param string $group  Cache group.
     * @return mixed|false   Cached data or false if not found.
     */
    public function get($key, $group = 'default') {
        $cache_key = $this->prepare_key($key);
        
        // Check internal memory cache first (fastest)
        $memory_key = $this->get_memory_key($cache_key, $group);
        if (isset($this->memory_cache[$memory_key])) {
            $this->stats['hits']++;
            return $this->memory_cache[$memory_key];
        }
        
        // Try object cache next (next fastest)
        if ($this->using_object_cache) {
            $object_cache_key = $this->prepare_object_cache_key($key, $group);
            $data = wp_cache_get($object_cache_key, 'pricewise');
            
            if ($data !== false) {
                // Store in memory cache for even faster subsequent access
                $this->set_memory_cache($memory_key, $this->maybe_unserialize($data));
                
                $this->stats['hits']++;
                return $this->maybe_unserialize($data);
            }
        }
        
        // Try database cache (slowest, but most persistent)
        global $wpdb;
        $current_time = current_time('mysql');
        
        $cached = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT cache_value FROM {$this->table_name} 
                WHERE cache_key = %s 
                AND cache_group = %s 
                AND expiration > %s",
                $cache_key,
                $group,
                $current_time
            )
        );
        
        if ($cached) {
            $data = $this->maybe_unserialize($cached);
            
            // Store in object cache for faster subsequent access
            if ($this->using_object_cache) {
                $object_cache_key = $this->prepare_object_cache_key($key, $group);
                // Store the serialized value directly to avoid double serialization
                wp_cache_set($object_cache_key, $cached, 'pricewise', 60); // Short TTL for object cache
            }
            
            // Store in memory cache as well
            $this->set_memory_cache($memory_key, $data);
            
            $this->stats['hits']++;
            return $data;
        }
        
        $this->stats['misses']++;
        return false;
    }
    
    /**
     * Store an item in the cache.
     *
     * @param string $key        Cache key.
     * @param mixed  $data       Data to cache.
     * @param string $group      Cache group.
     * @param int    $expiration Time in seconds until expiration.
     * @return bool              Success or failure.
     */
    public function set($key, $data, $group = 'default', $expiration = 3600) {
        $cache_key = $this->prepare_key($key);
        $cache_value = $this->maybe_serialize($data);
        
        global $wpdb;
        $expiration_date = date('Y-m-d H:i:s', time() + $expiration);
        $created_at = current_time('mysql');
        
        // Use a more efficient REPLACE INTO query instead of delete+insert
        // This is faster for database operation
        $result = $wpdb->query(
            $wpdb->prepare(
                "REPLACE INTO {$this->table_name} 
                (cache_key, cache_group, cache_value, expiration, created_at) 
                VALUES (%s, %s, %s, %s, %s)",
                $cache_key,
                $group,
                $cache_value,
                $expiration_date,
                $created_at
            )
        );
        
        // Store in object cache too, with a shorter TTL to prevent stale data
        if ($this->using_object_cache) {
            $object_cache_key = $this->prepare_object_cache_key($key, $group);
            // Use adaptive TTL - shorter of 60s or expiration time
            $object_cache_ttl = min(60, $expiration);
            wp_cache_set($object_cache_key, $cache_value, 'pricewise', $object_cache_ttl);
        }
        
        // Store in memory cache
        $memory_key = $this->get_memory_key($cache_key, $group);
        $this->set_memory_cache($memory_key, $data);
        
        if ($result !== false) {
            $this->stats['writes']++;
            
            // Periodically update stats in the database (every 10 writes)
            if ($this->stats['writes'] % 10 === 0) {
                $this->save_stats();
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Store an item in the memory cache
     *
     * @param string $key  Memory cache key
     * @param mixed  $data Data to store
     */
    protected function set_memory_cache($key, $data) {
        // If we've reached max size, remove oldest item
        if (count($this->memory_cache) >= $this->memory_cache_max_size) {
            array_shift($this->memory_cache);
        }
        
        $this->memory_cache[$key] = $data;
    }
    
    /**
     * Get memory cache key
     *
     * @param string $cache_key Prepared cache key
     * @param string $group     Cache group
     * @return string Memory cache key
     */
    protected function get_memory_key($cache_key, $group) {
        return $group . '_' . $cache_key;
    }
    
    /**
     * Delete an item from the cache.
     *
     * @param string $key   Cache key.
     * @param string $group Cache group.
     * @return bool         Success or failure.
     */
    public function delete($key, $group = 'default') {
        $cache_key = $this->prepare_key($key);
        
        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            array(
                'cache_key' => $cache_key,
                'cache_group' => $group
            ),
            array('%s', '%s')
        );
        
        // Remove from object cache too
        if ($this->using_object_cache) {
            $object_cache_key = $this->prepare_object_cache_key($key, $group);
            wp_cache_delete($object_cache_key, 'pricewise');
        }
        
        // Remove from memory cache
        $memory_key = $this->get_memory_key($cache_key, $group);
        if (isset($this->memory_cache[$memory_key])) {
            unset($this->memory_cache[$memory_key]);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete cache items by group.
     *
     * @param string $group Cache group.
     * @return int          Number of items deleted.
     */
    public function delete_group($group) {
        global $wpdb;
        
        $count = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE cache_group = %s",
                $group
            )
        );
        
        // Flush object cache for this group if possible
        if ($this->using_object_cache) {
            if (function_exists('wp_cache_flush_group')) {
                // If the object cache implementation supports group flushing
                wp_cache_flush_group('pricewise');
            } else {
                // Otherwise try to flush entire object cache
                // This is not ideal but necessary for compatibility
                wp_cache_flush();
            }
        }
        
        // Clear memory cache entries for this group
        foreach ($this->memory_cache as $key => $value) {
            if (strpos($key, $group . '_') === 0) {
                unset($this->memory_cache[$key]);
            }
        }
        
        return $count;
    }
/**
     * Delete cache items by pattern.
     * 
     * @param string $pattern Pattern to match against keys
     * @param string $group Cache group (optional)
     * @return int Number of items deleted
     */
    public function delete_by_pattern($pattern, $group = null) {
        global $wpdb;
        
        if ($group) {
            $count = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$this->table_name} WHERE cache_key LIKE %s AND cache_group = %s",
                    '%' . $wpdb->esc_like($pattern) . '%', 
                    $group
                )
            );
        } else {
            $count = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$this->table_name} WHERE cache_key LIKE %s",
                    '%' . $wpdb->esc_like($pattern) . '%'
                )
            );
        }
        
        // We need to flush object cache too as we can't selectively delete by pattern
        if ($this->using_object_cache) {
            if (function_exists('wp_cache_flush_group')) {
                wp_cache_flush_group('pricewise');
            } else {
                wp_cache_flush();
            }
        }
        
        // Clear memory cache too
        $this->memory_cache = array();
        
        return $count;
    }
    
    /**
     * Clear all cache data.
     *
     * @return bool Success or failure.
     */
    public function flush() {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        // Flush object cache too
        if ($this->using_object_cache) {
            wp_cache_flush();
        }
        
        // Clear memory cache
        $this->memory_cache = array();
        
        // Reset stats
        $this->stats = array(
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'last_flush' => time()
        );
        $this->save_stats();
        
        return $result !== false;
    }
    
    /**
     * Clean up expired cache entries using batch processing.
     *
     * @param int $batch_size Number of entries to delete in each batch.
     * @param int $max_time Maximum execution time in seconds (0 for no limit).
     * @return array Stats about the cleanup operation.
     */
    public function cleanup_expired_cache($batch_size = 1000, $max_time = 0) {
        global $wpdb;
        $current_time = current_time('mysql');
        $start_time = microtime(true);
        $total_deleted = 0;
        $batches = 0;
        $errors = array();
        
        // Get total number of expired entries for logging
        $total_expired = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE expiration < %s",
                $current_time
            )
        );
        
        if ($wpdb->last_error) {
            $errors[] = "Error counting expired entries: " . $wpdb->last_error;
        }
        
        // If no expired entries, return early
        if ($total_expired == 0) {
            return array(
                'total_deleted' => 0,
                'batches' => 0,
                'duration' => 0,
                'complete' => true,
                'errors' => array()
            );
        }
        
        do {
            // Delete one batch
            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$this->table_name} WHERE expiration < %s ORDER BY expiration ASC LIMIT %d",
                    $current_time,
                    $batch_size
                )
            );
            
            if ($wpdb->last_error) {
                $errors[] = "Error deleting batch: " . $wpdb->last_error;
                break;
            }
            
            if ($deleted) {
                $total_deleted += $deleted;
                $batches++;
            }
            
            // Check if we've reached a time limit
            $elapsed_time = microtime(true) - $start_time;
            $time_limit_reached = ($max_time > 0 && $elapsed_time >= $max_time);
            
            // Small pause to reduce database load if doing many batches
            if ($batches % 5 === 0 && $deleted >= $batch_size) {
                usleep(50000); // 50ms pause every 5 batches
            }
            
            // Continue if we deleted a full batch and haven't reached time limit
        } while ($deleted >= $batch_size && !$time_limit_reached);
        
        // Clear memory cache too to ensure we don't have stale data
        $this->memory_cache = array();
        
        // Log cleanup results
        if ($total_deleted > 0 || !empty($errors)) {
            $this->stats['last_cleanup'] = time();
            $this->stats['last_cleanup_count'] = $total_deleted;
            $this->stats['last_cleanup_duration'] = round(microtime(true) - $start_time, 2);
            $this->stats['last_cleanup_batches'] = $batches;
            $this->stats['last_cleanup_complete'] = ($total_deleted >= $total_expired);
            
            if (!empty($errors)) {
                $this->stats['last_cleanup_errors'] = $errors;
            }
            
            $this->save_stats();
        }
        
        return array(
            'total_deleted' => $total_deleted,
            'batches' => $batches,
            'duration' => round(microtime(true) - $start_time, 2),
            'complete' => ($deleted < $batch_size),
            'errors' => $errors,
            'total_expired' => $total_expired
        );
    }
    
    /**
     * Prune cache if it's too large, using batch processing.
     *
     * @param int $max_entries Maximum number of entries to keep.
     * @param int $batch_size Number of entries to delete in each batch.
     * @param int $max_time Maximum execution time in seconds (0 for no limit).
     * @return array Stats about the prune operation.
     */
    public function prune($max_entries = 10000, $batch_size = 1000, $max_time = 0) {
        global $wpdb;
        $start_time = microtime(true);
        $total_deleted = 0;
        $batches = 0;
        $errors = array();
        
        // Count total entries
        $count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        if ($wpdb->last_error) {
            $errors[] = "Error counting entries: " . $wpdb->last_error;
            
            return array(
                'total_deleted' => 0,
                'batches' => 0,
                'duration' => 0,
                'complete' => false,
                'errors' => $errors
            );
        }
        
        // If not exceeding limit, nothing to do
        if ($count <= $max_entries) {
            return array(
                'total_deleted' => 0,
                'batches' => 0,
                'duration' => 0,
                'complete' => true,
                'errors' => array()
            );
        }
        
        // Calculate how many entries to delete
        $entries_to_delete = $count - $max_entries;
        $remaining_to_delete = $entries_to_delete;
        
        // First attempt to delete expired items
        $expired_deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                WHERE expiration < %s 
                ORDER BY expiration ASC 
                LIMIT %d",
                current_time('mysql'),
                $entries_to_delete
            )
        );
        
        if ($wpdb->last_error) {
            $errors[] = "Error deleting expired items: " . $wpdb->last_error;
        } else {
            $total_deleted += $expired_deleted;
            $remaining_to_delete -= $expired_deleted;
            $batches++;
            
            // If we've deleted enough, we're done
            if ($remaining_to_delete <= 0) {
                // Update stats
                $this->stats['last_prune'] = time();
                $this->stats['last_prune_count'] = $total_deleted;
                $this->stats['last_prune_duration'] = round(microtime(true) - $start_time, 2);
                $this->stats['last_prune_batches'] = $batches;
                $this->stats['last_prune_complete'] = true;
                $this->save_stats();
                
                return array(
                    'total_deleted' => $total_deleted,
                    'batches' => $batches,
                    'duration' => round(microtime(true) - $start_time, 2),
                    'complete' => true,
                    'errors' => $errors,
                    'target_count' => $max_entries,
                    'initial_count' => $count,
                    'entries_to_delete' => $entries_to_delete
                );
            }
        }
        
        // If we still need to delete more, do it in batches by creation date
        while ($remaining_to_delete > 0) {
            // Calculate current batch size (might be smaller for final batch)
            $current_batch_size = min($batch_size, $remaining_to_delete);
            
            // Delete oldest entries first
            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$this->table_name} 
                    ORDER BY created_at ASC 
                    LIMIT %d",
                    $current_batch_size
                )
            );
            
            if ($wpdb->last_error) {
                $errors[] = "Error during pruning: " . $wpdb->last_error;
                break;
            }
            
            if ($deleted) {
                $total_deleted += $deleted;
                $remaining_to_delete -= $deleted;
                $batches++;
            } else {
                // No more entries deleted, something might be wrong
                break;
            }
            
            // Check if we've reached a time limit
            $elapsed_time = microtime(true) - $start_time;
            $time_limit_reached = ($max_time > 0 && $elapsed_time >= $max_time);
            
            if ($time_limit_reached) {
                break;
            }
            
            // Small pause to reduce database load if doing many batches
            if ($batches % 5 === 0) {
                usleep(50000); // 50ms pause every 5 batches
            }
        }
        
        // Clear memory cache after pruning
        $this->memory_cache = array();
        
        // Log pruning results
        if ($total_deleted > 0 || !empty($errors)) {
            $this->stats['last_prune'] = time();
            $this->stats['last_prune_count'] = $total_deleted;
            $this->stats['last_prune_duration'] = round(microtime(true) - $start_time, 2);
            $this->stats['last_prune_batches'] = $batches;
            $this->stats['last_prune_complete'] = ($remaining_to_delete <= 0);
            
            if (!empty($errors)) {
                $this->stats['last_prune_errors'] = $errors;
            }
            
            $this->save_stats();
        }
        
        return array(
            'total_deleted' => $total_deleted,
            'batches' => $batches,
            'duration' => round(microtime(true) - $start_time, 2),
            'complete' => ($remaining_to_delete <= 0),
            'errors' => $errors,
            'target_count' => $max_entries,
            'initial_count' => $count,
            'entries_to_delete' => $entries_to_delete
        );
    }
    
    /**
     * Get cache statistics.
     *
     * @return array Cache statistics.
     */
    public function get_stats() {
        global $wpdb;
        
        // Get current table size
        $table_size = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Get group statistics more efficiently (use single query)
        $group_counts = $wpdb->get_results(
            "SELECT cache_group, COUNT(*) as count 
            FROM {$this->table_name} 
            GROUP BY cache_group",
            ARRAY_A
        );
        
        $current_stats = array(
            'total_entries' => $table_size ?: 0,
            'groups' => array(),
            'hit_ratio' => 0,
            'memory_cache_size' => count($this->memory_cache),
            'memory_cache_max_size' => $this->memory_cache_max_size,
            'using_object_cache' => $this->using_object_cache
        );
        
        // Format group counts
        if (!empty($group_counts)) {
            foreach ($group_counts as $group) {
                $current_stats['groups'][$group['cache_group']] = (int)$group['count'];
            }
        }
        
        // Calculate hit ratio if we have hits or misses
        $total_requests = $this->stats['hits'] + $this->stats['misses'];
        if ($total_requests > 0) {
            $current_stats['hit_ratio'] = round(($this->stats['hits'] / $total_requests) * 100, 2);
        }
        
        return array_merge($this->stats, $current_stats);
    }
    
    /**
     * Save current statistics to the database.
     */
    protected function save_stats() {
        $this->stats['last_updated'] = time();
        update_option('pricewise_cache_stats', $this->stats, false); // Don't autoload this option
    }
    
    /**
     * Prepare a standardized cache key.
     *
     * @param string $key Raw cache key.
     * @return string     Prepared cache key.
     */
    protected function prepare_key($key) {
        return md5($key);
    }
    
    /**
     * Prepare a standardized object cache key.
     *
     * @param string $key   Raw cache key.
     * @param string $group Cache group.
     * @return string       Prepared object cache key.
     */
    protected function prepare_object_cache_key($key, $group) {
        return 'pw_' . $group . '_' . md5($key);
    }
    
    /**
     * Serialize data if needed.
     *
     * @param mixed $data Data to possibly serialize.
     * @return string     Serialized data.
     */
    protected function maybe_serialize($data) {
        // Only serialize if it's not a primitive type
        if (is_array($data) || is_object($data)) {
            return serialize($data);
        }
        return $data;
    }
    
    /**
     * Unserialize data if needed.
     *
     * @param string $data Data to possibly unserialize.
     * @return mixed       Unserialized data.
     */
    protected function maybe_unserialize($data) {
        // Check if data is serialized
        if (is_string($data) && self::is_serialized($data)) {
            return unserialize($data);
        }
        return $data;
    }
    
    /**
     * Check if a string is serialized
     * 
     * More efficient than PHP's built-in is_serialized function
     * 
     * @param string $data String to check
     * @return bool Whether string is serialized
     */
    public static function is_serialized($data) {
        // If it's not a string, it's not serialized
        if (!is_string($data)) {
            return false;
        }
        
        $data = trim($data);
        if ('N;' === $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        
        // Quick check for common serialized formats
        if (
            'a:' === substr($data, 0, 2) || // array
            'O:' === substr($data, 0, 2) || // object
            's:' === substr($data, 0, 2) || // string
            'i:' === substr($data, 0, 2) || // integer
            'd:' === substr($data, 0, 2) || // double
            'b:' === substr($data, 0, 2)    // boolean
        ) {
            // It must end with a semicolon to be valid
            return ';' === substr($data, -1);
        }
        
        return false;
    }
    
    /**
     * Perform cache maintenance tasks - cleanup expired entries and prune if needed.
     * 
     * @param int $batch_size Optional. Number of entries to process per batch.
     * @param int $max_time Optional. Maximum execution time in seconds.
     * @return array Maintenance statistics.
     */
    public function maintenance($batch_size = 1000, $max_time = 30) {
        // First cleanup expired items
        $cleanup_stats = $this->cleanup_expired_cache($batch_size, $max_time / 2);
        
        // Then prune if we have time left
        $elapsed_time = $cleanup_stats['duration'];
        $remaining_time = $max_time - $elapsed_time;
        
        if ($remaining_time > 0) {
            $prune_stats = $this->prune(10000, $batch_size, $remaining_time);
        } else {
            $prune_stats = array('total_deleted' => 0, 'complete' => false, 'duration' => 0);
        }
        
        return array(
            'cleanup' => $cleanup_stats,
            'prune' => $prune_stats,
            'total_deleted' => $cleanup_stats['total_deleted'] + $prune_stats['total_deleted'],
            'duration' => $cleanup_stats['duration'] + ($prune_stats['duration'] ?? 0)
        );
    }
    
    /**
     * Check if an item exists in cache without returning its value
     *
     * @param string $key Cache key
     * @param string $group Cache group
     * @return bool Whether item exists in cache
     */
    public function has($key, $group = 'default') {
        $cache_key = $this->prepare_key($key);
        
        // Check memory cache first
        $memory_key = $this->get_memory_key($cache_key, $group);
        if (isset($this->memory_cache[$memory_key])) {
            return true;
        }
        
        // Check object cache next
        if ($this->using_object_cache) {
            $object_cache_key = $this->prepare_object_cache_key($key, $group);
            if (wp_cache_get($object_cache_key, 'pricewise') !== false) {
                return true;
            }
        }
        
        // Finally check database
        global $wpdb;
        $current_time = current_time('mysql');
        
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$this->table_name} 
                WHERE cache_key = %s 
                AND cache_group = %s 
                AND expiration > %s
                LIMIT 1",
                $cache_key,
                $group,
                $current_time
            )
        );
        
        return !empty($exists);
    }
    
    /**
     * Get or set cache item (convenience function)
     *
     * @param string $key Cache key
     * @param callable $callback Function to generate value if not in cache
     * @param string $group Cache group
     * @param int $expiration Time in seconds until expiration
     * @return mixed Cached data or callback result
     */
    public function remember($key, $callback, $group = 'default', $expiration = 3600) {
        $cached = $this->get($key, $group);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $data = call_user_func($callback);
        
        if (!is_wp_error($data)) {
            $this->set($key, $data, $group, $expiration);
        }
        
        return $data;
    }
    
    /**
     * Get multiple cache items in a batch
     *
     * @param array $keys Array of cache keys to fetch
     * @param string $group Cache group
     * @return array Associative array of found items
     */
    public function get_multiple($keys, $group = 'default') {
        $results = array();
        
        foreach ($keys as $key) {
            $value = $this->get($key, $group);
            if ($value !== false) {
                $results[$key] = $value;
            }
        }
        
        return $results;
    }
    
    /**
     * Set multiple cache items in a batch
     *
     * @param array $items Associative array of key => value pairs to cache
     * @param string $group Cache group
     * @param int $expiration Time in seconds until expiration
     * @return bool Success or failure
     */
    public function set_multiple($items, $group = 'default', $expiration = 3600) {
        $success = true;
        
        foreach ($items as $key => $value) {
            $result = $this->set($key, $value, $group, $expiration);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Increment a numeric cache value
     *
     * @param string $key Cache key
     * @param int $offset Amount to increment by
     * @param string $group Cache group
     * @return int|false New value or false on failure
     */
    public function increment($key, $offset = 1, $group = 'default') {
        $value = $this->get($key, $group);
        
        if (is_numeric($value)) {
            $new_value = $value + $offset;
            if ($this->set($key, $new_value, $group)) {
                return $new_value;
            }
        }
        
        return false;
    }
    
    /**
     * Decrement a numeric cache value
     *
     * @param string $key Cache key
     * @param int $offset Amount to decrement by
     * @param string $group Cache group
     * @return int|false New value or false on failure
     */
    public function decrement($key, $offset = 1, $group = 'default') {
        return $this->increment($key, -$offset, $group);
    }
    
    /**
     * Reset the expiration time for a cache item
     *
     * @param string $key Cache key
     * @param string $group Cache group
     * @param int $expiration New expiration time in seconds
     * @return bool Success or failure
     */
    public function touch($key, $group = 'default', $expiration = 3600) {
        $value = $this->get($key, $group);
        
        if ($value !== false) {
            return $this->set($key, $value, $group, $expiration);
        }
        
        return false;
    }
    
    /**
     * Determine if cache should be bypassed based on request parameters
     *
     * @param array $params Request parameters
     * @return bool Whether to bypass cache
     */
    public function should_bypass_cache($params) {
        // Skip cache for admin or logged-in users if configured
        if (is_admin() && apply_filters('pricewise_bypass_cache_admin', false)) {
            return true;
        }
        
        // Skip cache if explicitly requested
        if (isset($params['nocache']) && $params['nocache']) {
            return true;
        }
        
        // Skip cache if it's a test or debug request
        if (isset($params['test_mode']) && $params['test_mode']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Handle API request with caching
     *
     * @param string $api_id API identifier
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @param callable $fetch_func Function to fetch fresh data
     * @param int $expiration Cache expiration time in seconds
     * @return mixed API response data
     */
    public function api_request($api_id, $endpoint, $params, $fetch_func, $expiration = 3600) {
        // Check if we should bypass cache
        if ($this->should_bypass_cache($params)) {
            return call_user_func($fetch_func, $params);
        }
        
        // Generate cache key
        $cache_key = $this->generate_api_key($api_id, $endpoint, $params);
        
        // Get proper cache group
        $cache_group = "api_{$api_id}_{$endpoint}";
        
        // Try to get from cache
        $cached_data = $this->get($cache_key, $cache_group);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Fetch fresh data
        $fresh_data = call_user_func($fetch_func, $params);
        
        // Cache the result if it's not an error
        if (!is_wp_error($fresh_data)) {
            $this->set($cache_key, $fresh_data, $cache_group, $expiration);
        }
        
        return $fresh_data;
    }
    
    /**
     * Generate a consistent cache key for API requests
     *
     * @param string $api_id API identifier
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return string Cache key
     */
    private function generate_api_key($api_id, $endpoint, $params = array()) {
        // Generate a unique representation of the API request
        $key_parts = array(
            'api' => $api_id,
            'endpoint' => $endpoint,
            'params' => $params
        );
        
        // Sort params to ensure consistency
        if (isset($key_parts['params']) && is_array($key_parts['params'])) {
            ksort($key_parts['params']);
        }
        
        return 'api_' . md5(serialize($key_parts));
    }
}