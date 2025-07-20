<?php
/**
 * Database functionality class
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database functionality class
 */
class XeliteRepostEngine_Database extends XeliteRepostEngine_Abstract_Base implements XeliteRepostEngine_Database_Interface {
    
    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Table names
     *
     * @var array
     */
    private $tables = array();
    
    /**
     * Initialize the class
     */
    protected function init() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Define table names
        $this->tables = array(
            'reposts' => $this->wpdb->prefix . 'xelite_reposts',
        );
        
        $this->log_debug('Database class initialized');
    }
    
    /**
     * Create database tables
     *
     * @return bool
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Reposts table with enhanced schema
        $table_name = $this->tables['reposts'];
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_handle varchar(255) NOT NULL COMMENT 'The X account handle that was reposted',
            original_tweet_id varchar(255) NOT NULL COMMENT 'Original tweet ID from X',
            original_text text NOT NULL COMMENT 'Original tweet content',
            platform varchar(50) DEFAULT 'x' COMMENT 'Platform where the repost occurred (x, twitter, etc.)',
            repost_date datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'When the repost was detected',
            engagement_metrics json DEFAULT NULL COMMENT 'JSON object containing likes, retweets, replies, etc.',
            content_variations json DEFAULT NULL COMMENT 'JSON array of content variations for analysis',
            post_id bigint(20) DEFAULT NULL COMMENT 'WordPress post ID if content was generated from this repost',
            original_post_id bigint(20) DEFAULT NULL COMMENT 'Original post ID if this is a repost of our content',
            user_id bigint(20) DEFAULT NULL COMMENT 'WordPress user ID who generated content from this repost',
            repost_count int(11) DEFAULT 0 COMMENT 'Number of times this tweet was reposted',
            is_analyzed tinyint(1) DEFAULT 0 COMMENT 'Whether this repost has been analyzed for patterns',
            analysis_data json DEFAULT NULL COMMENT 'JSON object containing pattern analysis results',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_tweet (original_tweet_id, source_handle),
            KEY source_handle (source_handle),
            KEY original_tweet_id (original_tweet_id),
            KEY platform (platform),
            KEY repost_date (repost_date),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY is_analyzed (is_analyzed),
            KEY created_at (created_at),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        // Store database version for future migrations
        $this->update_database_version();
        
        $this->log_debug('Database tables created', array('result' => $result));
        
        return true;
    }
    
    /**
     * Drop database tables
     *
     * @return bool
     */
    public function drop_tables() {
        foreach ($this->tables as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        $this->log_debug('Database tables dropped');
        
        return true;
    }
    
    /**
     * Insert data
     *
     * @param string $table Table name
     * @param array  $data  Data to insert
     * @return int|false
     */
    public function insert($table, $data) {
        $table_name = $this->get_table_name($table);
        
        $result = $this->wpdb->insert($table_name, $data);
        
        if ($result === false) {
            $this->log_error('Failed to insert data', array(
                'table' => $table_name,
                'data' => $data,
                'error' => $this->wpdb->last_error
            ));
            return false;
        }
        
        $this->log_debug('Data inserted', array(
            'table' => $table_name,
            'id' => $this->wpdb->insert_id
        ));
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update data
     *
     * @param string $table  Table name
     * @param array  $data   Data to update
     * @param array  $where  Where conditions
     * @return int|false
     */
    public function update($table, $data, $where) {
        $table_name = $this->get_table_name($table);
        
        $result = $this->wpdb->update($table_name, $data, $where);
        
        if ($result === false) {
            $this->log_error('Failed to update data', array(
                'table' => $table_name,
                'data' => $data,
                'where' => $where,
                'error' => $this->wpdb->last_error
            ));
            return false;
        }
        
        $this->log_debug('Data updated', array(
            'table' => $table_name,
            'rows_affected' => $result
        ));
        
        return $result;
    }
    
    /**
     * Delete data
     *
     * @param string $table Table name
     * @param array  $where Where conditions
     * @return int|false
     */
    public function delete($table, $where) {
        $table_name = $this->get_table_name($table);
        
        $result = $this->wpdb->delete($table_name, $where);
        
        if ($result === false) {
            $this->log_error('Failed to delete data', array(
                'table' => $table_name,
                'where' => $where,
                'error' => $this->wpdb->last_error
            ));
            return false;
        }
        
        $this->log_debug('Data deleted', array(
            'table' => $table_name,
            'rows_affected' => $result
        ));
        
        return $result;
    }
    
    /**
     * Get data
     *
     * @param string $table   Table name
     * @param array  $where   Where conditions
     * @param array  $orderby Order by conditions
     * @param int    $limit   Limit
     * @param int    $offset  Offset
     * @return array
     */
    public function get($table, $where = array(), $orderby = array(), $limit = 0, $offset = 0) {
        $table_name = $this->get_table_name($table);
        
        $sql = "SELECT * FROM $table_name";
        
        // Add WHERE clause
        if (!empty($where)) {
            $where_clause = $this->build_where_clause($where);
            $sql .= " WHERE $where_clause";
        }
        
        // Add ORDER BY clause
        if (!empty($orderby)) {
            $orderby_clause = $this->build_orderby_clause($orderby);
            $sql .= " ORDER BY $orderby_clause";
        }
        
        // Add LIMIT clause
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
            if ($offset > 0) {
                $sql .= " OFFSET $offset";
            }
        }
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        $this->log_debug('Data retrieved', array(
            'table' => $table_name,
            'count' => count($results)
        ));
        
        return $results;
    }
    
    /**
     * Get single row
     *
     * @param string $table Table name
     * @param array  $where Where conditions
     * @return object|null
     */
    public function get_row($table, $where = array()) {
        $table_name = $this->get_table_name($table);
        
        $sql = "SELECT * FROM $table_name";
        
        if (!empty($where)) {
            $where_clause = $this->build_where_clause($where);
            $sql .= " WHERE $where_clause";
        }
        
        $sql .= " LIMIT 1";
        
        $result = $this->wpdb->get_row($sql);
        
        $this->log_debug('Single row retrieved', array(
            'table' => $table_name,
            'found' => $result !== null
        ));
        
        return $result;
    }
    
    /**
     * Count rows
     *
     * @param string $table Table name
     * @param array  $where Where conditions
     * @return int
     */
    public function count($table, $where = array()) {
        $table_name = $this->get_table_name($table);
        
        $sql = "SELECT COUNT(*) FROM $table_name";
        
        if (!empty($where)) {
            $where_clause = $this->build_where_clause($where);
            $sql .= " WHERE $where_clause";
        }
        
        $count = $this->wpdb->get_var($sql);
        
        $this->log_debug('Row count retrieved', array(
            'table' => $table_name,
            'count' => $count
        ));
        
        return (int) $count;
    }
    
    /**
     * Check if table exists
     *
     * @param string $table Table name
     * @return bool
     */
    public function table_exists($table) {
        $table_name = $this->get_table_name($table);
        
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )
        );
        
        return $result === $table_name;
    }
    
    /**
     * Get database version
     *
     * @return string
     */
    public function get_database_version() {
        return $this->get_option('database_version', '1.0.0');
    }
    
    /**
     * Update database version
     *
     * @param string $version Version to set
     * @return bool
     */
    public function update_database_version($version = null) {
        if ($version === null) {
            $version = XELITE_REPOST_ENGINE_VERSION;
        }
        
        $result = $this->update_option('database_version', $version);
        
        $this->log_debug('Database version updated', array('version' => $version));
        
        return $result;
    }
    
    /**
     * Check if database needs upgrade
     *
     * @return bool
     */
    public function needs_upgrade() {
        $current_version = $this->get_database_version();
        $plugin_version = XELITE_REPOST_ENGINE_VERSION;
        
        return version_compare($current_version, $plugin_version, '<');
    }
    
    /**
     * Upgrade database schema
     *
     * @return bool
     */
    public function upgrade_database() {
        if (!$this->needs_upgrade()) {
            return true;
        }
        
        $current_version = $this->get_database_version();
        $plugin_version = XELITE_REPOST_ENGINE_VERSION;
        
        $this->log_debug('Starting database upgrade', array(
            'from' => $current_version,
            'to' => $plugin_version
        ));
        
        // Run version-specific upgrades
        if (version_compare($current_version, '1.1.0', '<')) {
            $this->upgrade_to_1_1_0();
        }
        
        // Update database version
        $this->update_database_version($plugin_version);
        
        $this->log_debug('Database upgrade completed');
        
        return true;
    }
    
    /**
     * Upgrade to version 1.1.0
     *
     * @return bool
     */
    private function upgrade_to_1_1_0() {
        $table_name = $this->tables['reposts'];
        
        // Add new columns if they don't exist
        $columns_to_add = array(
            'platform' => "ALTER TABLE $table_name ADD COLUMN platform varchar(50) DEFAULT 'x' COMMENT 'Platform where the repost occurred'",
            'repost_date' => "ALTER TABLE $table_name ADD COLUMN repost_date datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'When the repost was detected'",
            'engagement_metrics' => "ALTER TABLE $table_name ADD COLUMN engagement_metrics json DEFAULT NULL COMMENT 'JSON object containing engagement data'",
            'content_variations' => "ALTER TABLE $table_name ADD COLUMN content_variations json DEFAULT NULL COMMENT 'JSON array of content variations'",
            'post_id' => "ALTER TABLE $table_name ADD COLUMN post_id bigint(20) DEFAULT NULL COMMENT 'WordPress post ID'",
            'original_post_id' => "ALTER TABLE $table_name ADD COLUMN original_post_id bigint(20) DEFAULT NULL COMMENT 'Original post ID'",
            'user_id' => "ALTER TABLE $table_name ADD COLUMN user_id bigint(20) DEFAULT NULL COMMENT 'WordPress user ID'",
            'is_analyzed' => "ALTER TABLE $table_name ADD COLUMN is_analyzed tinyint(1) DEFAULT 0 COMMENT 'Whether analyzed for patterns'",
            'analysis_data' => "ALTER TABLE $table_name ADD COLUMN analysis_data json DEFAULT NULL COMMENT 'Pattern analysis results'"
        );
        
        foreach ($columns_to_add as $column => $sql) {
            if (!$this->column_exists($table_name, $column)) {
                $this->wpdb->query($sql);
                $this->log_debug('Added column', array('table' => $table_name, 'column' => $column));
            }
        }
        
        // Add new indexes
        $indexes_to_add = array(
            'platform' => "ALTER TABLE $table_name ADD INDEX platform (platform)",
            'repost_date' => "ALTER TABLE $table_name ADD INDEX repost_date (repost_date)",
            'post_id' => "ALTER TABLE $table_name ADD INDEX post_id (post_id)",
            'user_id' => "ALTER TABLE $table_name ADD INDEX user_id (user_id)",
            'is_analyzed' => "ALTER TABLE $table_name ADD INDEX is_analyzed (is_analyzed)",
            'updated_at' => "ALTER TABLE $table_name ADD INDEX updated_at (updated_at)"
        );
        
        foreach ($indexes_to_add as $index => $sql) {
            if (!$this->index_exists($table_name, $index)) {
                $this->wpdb->query($sql);
                $this->log_debug('Added index', array('table' => $table_name, 'index' => $index));
            }
        }
        
        // Add unique constraint if it doesn't exist
        if (!$this->index_exists($table_name, 'unique_tweet')) {
            $this->wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY unique_tweet (original_tweet_id, source_handle)");
            $this->log_debug('Added unique constraint', array('table' => $table_name, 'constraint' => 'unique_tweet'));
        }
        
        return true;
    }
    
    /**
     * Check if column exists
     *
     * @param string $table_name Table name
     * @param string $column_name Column name
     * @return bool
     */
    private function column_exists($table_name, $column_name) {
        $result = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM $table_name LIKE %s",
                $column_name
            )
        );
        
        return !empty($result);
    }
    
    /**
     * Check if index exists
     *
     * @param string $table_name Table name
     * @param string $index_name Index name
     * @return bool
     */
    private function index_exists($table_name, $index_name) {
        $result = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW INDEX FROM $table_name WHERE Key_name = %s",
                $index_name
            )
        );
        
        return !empty($result);
    }
    
    /**
     * Get table schema information
     *
     * @param string $table Table name
     * @return array
     */
    public function get_table_schema($table) {
        $table_name = $this->get_table_name($table);
        
        $columns = $this->wpdb->get_results(
            "SHOW COLUMNS FROM $table_name",
            ARRAY_A
        );
        
        $schema = array();
        foreach ($columns as $column) {
            $schema[$column['Field']] = array(
                'type' => $column['Type'],
                'null' => $column['Null'],
                'key' => $column['Key'],
                'default' => $column['Default'],
                'extra' => $column['Extra'],
                'comment' => $column['Comment']
            );
        }
        
        return $schema;
    }
    
    /**
     * Insert repost data with enhanced schema support
     *
     * @param array $data Repost data
     * @return int|false
     */
    public function insert_repost($data) {
        // Ensure required fields
        if (empty($data['source_handle']) || empty($data['original_tweet_id']) || empty($data['original_text'])) {
            $this->log_error('Missing required fields for repost insert', array('data' => $data));
            return false;
        }
        
        // Set defaults
        $data['platform'] = isset($data['platform']) ? $data['platform'] : 'x';
        $data['repost_date'] = isset($data['repost_date']) ? $data['repost_date'] : current_time('mysql');
        
        // Handle JSON fields
        if (isset($data['engagement_metrics']) && is_array($data['engagement_metrics'])) {
            $data['engagement_metrics'] = json_encode($data['engagement_metrics']);
        }
        
        if (isset($data['content_variations']) && is_array($data['content_variations'])) {
            $data['content_variations'] = json_encode($data['content_variations']);
        }
        
        if (isset($data['analysis_data']) && is_array($data['analysis_data'])) {
            $data['analysis_data'] = json_encode($data['analysis_data']);
        }
        
        return $this->insert('reposts', $data);
    }
    
    /**
     * Update repost data with enhanced schema support
     *
     * @param array $data Data to update
     * @param array $where Where conditions
     * @return int|false
     */
    public function update_repost($data, $where) {
        // Handle JSON fields
        if (isset($data['engagement_metrics']) && is_array($data['engagement_metrics'])) {
            $data['engagement_metrics'] = json_encode($data['engagement_metrics']);
        }
        
        if (isset($data['content_variations']) && is_array($data['content_variations'])) {
            $data['content_variations'] = json_encode($data['content_variations']);
        }
        
        if (isset($data['analysis_data']) && is_array($data['analysis_data'])) {
            $data['analysis_data'] = json_encode($data['analysis_data']);
        }
        
        return $this->update('reposts', $data, $where);
    }
    
    /**
     * Get reposts with enhanced schema support
     *
     * @param array $where Where conditions
     * @param array $orderby Order by conditions
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function get_reposts($where = array(), $orderby = array(), $limit = 0, $offset = 0) {
        $results = $this->get('reposts', $where, $orderby, $limit, $offset);
        
        // Decode JSON fields
        foreach ($results as &$row) {
            if (isset($row['engagement_metrics']) && $row['engagement_metrics']) {
                $row['engagement_metrics'] = json_decode($row['engagement_metrics'], true);
            }
            
            if (isset($row['content_variations']) && $row['content_variations']) {
                $row['content_variations'] = json_decode($row['content_variations'], true);
            }
            
            if (isset($row['analysis_data']) && $row['analysis_data']) {
                $row['analysis_data'] = json_decode($row['analysis_data'], true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get repost by tweet ID and source handle
     *
     * @param string $tweet_id Tweet ID
     * @param string $source_handle Source handle
     * @return object|null
     */
    public function get_repost_by_tweet($tweet_id, $source_handle) {
        $result = $this->get_row('reposts', array(
            'original_tweet_id' => $tweet_id,
            'source_handle' => $source_handle
        ));
        
        if ($result) {
            // Decode JSON fields
            if (isset($result->engagement_metrics) && $result->engagement_metrics) {
                $result->engagement_metrics = json_decode($result->engagement_metrics, true);
            }
            
            if (isset($result->content_variations) && $result->content_variations) {
                $result->content_variations = json_decode($result->content_variations, true);
            }
            
            if (isset($result->analysis_data) && $result->analysis_data) {
                $result->analysis_data = json_decode($result->analysis_data, true);
            }
        }
        
        return $result;
    }
    
    /**
     * Batch insert data
     *
     * @param string $table Table name
     * @param array  $data_array Array of data arrays to insert
     * @return int|false Number of rows inserted or false on failure
     */
    public function batch_insert($table, $data_array) {
        if (empty($data_array) || !is_array($data_array)) {
            $this->log_error('Invalid data array for batch insert', array('table' => $table));
            return false;
        }
        
        $table_name = $this->get_table_name($table);
        $inserted_count = 0;
        
        // Start transaction for better performance
        $this->wpdb->query('START TRANSACTION');
        
        try {
            foreach ($data_array as $data) {
                $result = $this->wpdb->insert($table_name, $data);
                if ($result !== false) {
                    $inserted_count++;
                } else {
                    $this->log_error('Failed to insert row in batch', array(
                        'table' => $table_name,
                        'data' => $data,
                        'error' => $this->wpdb->last_error
                    ));
                }
            }
            
            if ($inserted_count > 0) {
                $this->wpdb->query('COMMIT');
                $this->log_debug('Batch insert completed', array(
                    'table' => $table_name,
                    'inserted' => $inserted_count,
                    'total' => count($data_array)
                ));
                return $inserted_count;
            } else {
                $this->wpdb->query('ROLLBACK');
                return false;
            }
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            $this->log_error('Batch insert failed with exception', array(
                'table' => $table_name,
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
    
    /**
     * Batch insert reposts with enhanced schema support
     *
     * @param array $reposts_array Array of repost data arrays
     * @return int|false Number of rows inserted or false on failure
     */
    public function batch_insert_reposts($reposts_array) {
        if (empty($reposts_array) || !is_array($reposts_array)) {
            $this->log_error('Invalid reposts array for batch insert');
            return false;
        }
        
        $processed_data = array();
        
        foreach ($reposts_array as $data) {
            // Ensure required fields
            if (empty($data['source_handle']) || empty($data['original_tweet_id']) || empty($data['original_text'])) {
                $this->log_error('Missing required fields for repost batch insert', array('data' => $data));
                continue;
            }
            
            // Set defaults
            $data['platform'] = isset($data['platform']) ? $data['platform'] : 'x';
            $data['repost_date'] = isset($data['repost_date']) ? $data['repost_date'] : current_time('mysql');
            
            // Handle JSON fields
            if (isset($data['engagement_metrics']) && is_array($data['engagement_metrics'])) {
                $data['engagement_metrics'] = json_encode($data['engagement_metrics']);
            }
            
            if (isset($data['content_variations']) && is_array($data['content_variations'])) {
                $data['content_variations'] = json_encode($data['content_variations']);
            }
            
            if (isset($data['analysis_data']) && is_array($data['analysis_data'])) {
                $data['analysis_data'] = json_encode($data['analysis_data']);
            }
            
            $processed_data[] = $data;
        }
        
        return $this->batch_insert('reposts', $processed_data);
    }
    
    /**
     * Batch update data
     *
     * @param string $table Table name
     * @param array  $data  Data to update
     * @param array  $where_conditions Array of where conditions for each row
     * @return int|false Number of rows updated or false on failure
     */
    public function batch_update($table, $data, $where_conditions) {
        if (empty($where_conditions) || !is_array($where_conditions)) {
            $this->log_error('Invalid where conditions for batch update', array('table' => $table));
            return false;
        }
        
        $table_name = $this->get_table_name($table);
        $updated_count = 0;
        
        // Start transaction for better performance
        $this->wpdb->query('START TRANSACTION');
        
        try {
            foreach ($where_conditions as $where) {
                $result = $this->wpdb->update($table_name, $data, $where);
                if ($result !== false) {
                    $updated_count += $result;
                } else {
                    $this->log_error('Failed to update row in batch', array(
                        'table' => $table_name,
                        'data' => $data,
                        'where' => $where,
                        'error' => $this->wpdb->last_error
                    ));
                }
            }
            
            $this->wpdb->query('COMMIT');
            $this->log_debug('Batch update completed', array(
                'table' => $table_name,
                'updated' => $updated_count,
                'total' => count($where_conditions)
            ));
            return $updated_count;
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            $this->log_error('Batch update failed with exception', array(
                'table' => $table_name,
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
    
    /**
     * Get reposts by user ID
     *
     * @param int $user_id WordPress user ID
     * @param array $orderby Order by conditions
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function get_reposts_by_user($user_id, $orderby = array(), $limit = 0, $offset = 0) {
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->log_error('Invalid user ID for get_reposts_by_user', array('user_id' => $user_id));
            return array();
        }
        
        $where = array('user_id' => (int) $user_id);
        
        return $this->get_reposts($where, $orderby, $limit, $offset);
    }
    
    /**
     * Get top performing reposts based on engagement metrics
     *
     * @param int $limit Number of reposts to return
     * @param string $metric Engagement metric to sort by (likes, retweets, replies, total)
     * @param array $where Additional where conditions
     * @return array
     */
    public function get_top_performing_reposts($limit = 10, $metric = 'total', $where = array()) {
        $table_name = $this->tables['reposts'];
        
        // Build the SQL query with JSON extraction for engagement metrics
        $sql = "SELECT *, 
                JSON_EXTRACT(engagement_metrics, '$.likes') as likes,
                JSON_EXTRACT(engagement_metrics, '$.retweets') as retweets,
                JSON_EXTRACT(engagement_metrics, '$.replies') as replies,
                (JSON_EXTRACT(engagement_metrics, '$.likes') + 
                 JSON_EXTRACT(engagement_metrics, '$.retweets') + 
                 JSON_EXTRACT(engagement_metrics, '$.replies')) as total_engagement
                FROM $table_name";
        
        // Add WHERE clause
        $where_conditions = array();
        if (!empty($where)) {
            foreach ($where as $column => $value) {
                if (is_array($value)) {
                    $placeholders = array_fill(0, count($value), '%s');
                    $where_conditions[] = $this->wpdb->prepare(
                        "$column IN (" . implode(',', $placeholders) . ")",
                        $value
                    );
                } else {
                    $where_conditions[] = $this->wpdb->prepare("$column = %s", $value);
                }
            }
        }
        
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        // Add ORDER BY clause based on metric
        switch ($metric) {
            case 'likes':
                $sql .= " ORDER BY likes DESC";
                break;
            case 'retweets':
                $sql .= " ORDER BY retweets DESC";
                break;
            case 'replies':
                $sql .= " ORDER BY replies DESC";
                break;
            case 'total':
            default:
                $sql .= " ORDER BY total_engagement DESC";
                break;
        }
        
        // Add LIMIT clause
        if ($limit > 0) {
            $sql .= " LIMIT " . (int) $limit;
        }
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        // Decode JSON fields
        foreach ($results as &$row) {
            if (isset($row['engagement_metrics']) && $row['engagement_metrics']) {
                $row['engagement_metrics'] = json_decode($row['engagement_metrics'], true);
            }
            
            if (isset($row['content_variations']) && $row['content_variations']) {
                $row['content_variations'] = json_decode($row['content_variations'], true);
            }
            
            if (isset($row['analysis_data']) && $row['analysis_data']) {
                $row['analysis_data'] = json_decode($row['analysis_data'], true);
            }
        }
        
        $this->log_debug('Top performing reposts retrieved', array(
            'limit' => $limit,
            'metric' => $metric,
            'count' => count($results)
        ));
        
        return $results;
    }
    
    /**
     * Get reposts by source handle
     *
     * @param string $source_handle Source handle to search for
     * @param array $orderby Order by conditions
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function get_reposts_by_source($source_handle, $orderby = array(), $limit = 0, $offset = 0) {
        if (empty($source_handle)) {
            $this->log_error('Invalid source handle for get_reposts_by_source', array('source_handle' => $source_handle));
            return array();
        }
        
        $where = array('source_handle' => $source_handle);
        
        return $this->get_reposts($where, $orderby, $limit, $offset);
    }
    
    /**
     * Get reposts by date range
     *
     * @param string $start_date Start date (Y-m-d H:i:s format)
     * @param string $end_date End date (Y-m-d H:i:s format)
     * @param array $orderby Order by conditions
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function get_reposts_by_date_range($start_date, $end_date, $orderby = array(), $limit = 0, $offset = 0) {
        $table_name = $this->tables['reposts'];
        
        $sql = "SELECT * FROM $table_name WHERE repost_date BETWEEN %s AND %s";
        
        // Add ORDER BY clause
        if (!empty($orderby)) {
            $orderby_clause = $this->build_orderby_clause($orderby);
            $sql .= " ORDER BY $orderby_clause";
        }
        
        // Add LIMIT clause
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
            if ($offset > 0) {
                $sql .= " OFFSET $offset";
            }
        }
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $start_date, $end_date),
            ARRAY_A
        );
        
        // Decode JSON fields
        foreach ($results as &$row) {
            if (isset($row['engagement_metrics']) && $row['engagement_metrics']) {
                $row['engagement_metrics'] = json_decode($row['engagement_metrics'], true);
            }
            
            if (isset($row['content_variations']) && $row['content_variations']) {
                $row['content_variations'] = json_decode($row['content_variations'], true);
            }
            
            if (isset($row['analysis_data']) && $row['analysis_data']) {
                $row['analysis_data'] = json_decode($row['analysis_data'], true);
            }
        }
        
        $this->log_debug('Reposts by date range retrieved', array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'count' => count($results)
        ));
        
        return $results;
    }
    
    /**
     * Get unanalyzed reposts
     *
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function get_unanalyzed_reposts($limit = 0, $offset = 0) {
        $where = array('is_analyzed' => 0);
        $orderby = array('created_at' => 'ASC');
        
        return $this->get_reposts($where, $orderby, $limit, $offset);
    }
    
    /**
     * Mark repost as analyzed
     *
     * @param int $repost_id Repost ID
     * @param array $analysis_data Analysis data to store
     * @return bool
     */
    public function mark_repost_analyzed($repost_id, $analysis_data = array()) {
        if (empty($repost_id) || !is_numeric($repost_id)) {
            $this->log_error('Invalid repost ID for mark_repost_analyzed', array('repost_id' => $repost_id));
            return false;
        }
        
        $data = array(
            'is_analyzed' => 1,
            'analysis_data' => is_array($analysis_data) ? json_encode($analysis_data) : $analysis_data
        );
        
        $where = array('id' => (int) $repost_id);
        
        return $this->update_repost($data, $where);
    }
    
    /**
     * Get database statistics
     *
     * @return array
     */
    public function get_database_stats() {
        $table_name = $this->tables['reposts'];
        
        $stats = array(
            'total_reposts' => 0,
            'analyzed_reposts' => 0,
            'unanalyzed_reposts' => 0,
            'total_users' => 0,
            'total_sources' => 0,
            'date_range' => array(
                'earliest' => null,
                'latest' => null
            )
        );
        
        // Get total reposts
        $stats['total_reposts'] = $this->count('reposts');
        
        // Get analyzed vs unanalyzed
        $stats['analyzed_reposts'] = $this->count('reposts', array('is_analyzed' => 1));
        $stats['unanalyzed_reposts'] = $this->count('reposts', array('is_analyzed' => 0));
        
        // Get unique users
        $unique_users = $this->wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE user_id IS NOT NULL");
        $stats['total_users'] = (int) $unique_users;
        
        // Get unique sources
        $unique_sources = $this->wpdb->get_var("SELECT COUNT(DISTINCT source_handle) FROM $table_name");
        $stats['total_sources'] = (int) $unique_sources;
        
        // Get date range
        $date_range = $this->wpdb->get_row("SELECT MIN(repost_date) as earliest, MAX(repost_date) as latest FROM $table_name");
        if ($date_range) {
            $stats['date_range']['earliest'] = $date_range->earliest;
            $stats['date_range']['latest'] = $date_range->latest;
        }
        
        $this->log_debug('Database statistics retrieved', $stats);
        
        return $stats;
    }
    
    /**
     * Clean up old reposts
     *
     * @param int $days_old Number of days old to consider for cleanup
     * @return int Number of rows deleted
     */
    public function cleanup_old_reposts($days_old = 365) {
        $table_name = $this->tables['reposts'];
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        $sql = $this->wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $cutoff_date
        );
        
        $result = $this->wpdb->query($sql);
        
        if ($result !== false) {
            $this->log_debug('Old reposts cleaned up', array(
                'days_old' => $days_old,
                'deleted_count' => $result
            ));
            return $result;
        } else {
            $this->log_error('Failed to cleanup old reposts', array(
                'days_old' => $days_old,
                'error' => $this->wpdb->last_error
            ));
            return 0;
        }
    }
    
    /**
     * Get table name
     *
     * @param string $table Table key
     * @return string
     */
    private function get_table_name($table) {
        if (isset($this->tables[$table])) {
            return $this->tables[$table];
        }
        
        // If not found, assume it's already a full table name
        return $table;
    }
    
    /**
     * Build WHERE clause
     *
     * @param array $where Where conditions
     * @return string
     */
    private function build_where_clause($where) {
        $conditions = array();
        
        foreach ($where as $column => $value) {
            if (is_array($value)) {
                $placeholders = array_fill(0, count($value), '%s');
                $conditions[] = $this->wpdb->prepare(
                    "$column IN (" . implode(',', $placeholders) . ")",
                    $value
                );
            } else {
                $conditions[] = $this->wpdb->prepare("$column = %s", $value);
            }
        }
        
        return implode(' AND ', $conditions);
    }
    
    /**
     * Build ORDER BY clause
     *
     * @param array $orderby Order by conditions
     * @return string
     */
    private function build_orderby_clause($orderby) {
        $conditions = array();
        
        foreach ($orderby as $column => $direction) {
            $direction = strtoupper($direction);
            if (!in_array($direction, array('ASC', 'DESC'))) {
                $direction = 'ASC';
            }
            $conditions[] = "$column $direction";
        }
        
        return implode(', ', $conditions);
    }
    
    /**
     * Validate repost data
     *
     * @param array $data Repost data to validate
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validate_repost_data($data) {
        $errors = array();
        
        // Check required fields
        if (empty($data['source_handle'])) {
            $errors[] = 'source_handle is required';
        }
        
        if (empty($data['original_tweet_id'])) {
            $errors[] = 'original_tweet_id is required';
        }
        
        if (empty($data['original_text'])) {
            $errors[] = 'original_text is required';
        }
        
        // Validate data types
        if (isset($data['user_id']) && !is_numeric($data['user_id'])) {
            $errors[] = 'user_id must be numeric';
        }
        
        if (isset($data['post_id']) && !is_numeric($data['post_id'])) {
            $errors[] = 'post_id must be numeric';
        }
        
        if (isset($data['repost_count']) && !is_numeric($data['repost_count'])) {
            $errors[] = 'repost_count must be numeric';
        }
        
        // Validate JSON fields
        if (isset($data['engagement_metrics']) && !is_array($data['engagement_metrics'])) {
            $errors[] = 'engagement_metrics must be an array';
        }
        
        if (isset($data['content_variations']) && !is_array($data['content_variations'])) {
            $errors[] = 'content_variations must be an array';
        }
        
        if (isset($data['analysis_data']) && !is_array($data['analysis_data'])) {
            $errors[] = 'analysis_data must be an array';
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Sanitize repost data
     *
     * @param array $data Repost data to sanitize
     * @return array Sanitized data
     */
    public function sanitize_repost_data($data) {
        $sanitized = array();
        
        // Sanitize text fields
        if (isset($data['source_handle'])) {
            $sanitized['source_handle'] = sanitize_text_field($data['source_handle']);
        }
        
        if (isset($data['original_tweet_id'])) {
            $sanitized['original_tweet_id'] = sanitize_text_field($data['original_tweet_id']);
        }
        
        if (isset($data['original_text'])) {
            $sanitized['original_text'] = sanitize_textarea_field($data['original_text']);
        }
        
        if (isset($data['platform'])) {
            $sanitized['platform'] = sanitize_text_field($data['platform']);
        }
        
        // Sanitize numeric fields
        if (isset($data['user_id'])) {
            $sanitized['user_id'] = (int) $data['user_id'];
        }
        
        if (isset($data['post_id'])) {
            $sanitized['post_id'] = (int) $data['post_id'];
        }
        
        if (isset($data['original_post_id'])) {
            $sanitized['original_post_id'] = (int) $data['original_post_id'];
        }
        
        if (isset($data['repost_count'])) {
            $sanitized['repost_count'] = (int) $data['repost_count'];
        }
        
        if (isset($data['is_analyzed'])) {
            $sanitized['is_analyzed'] = (int) $data['is_analyzed'];
        }
        
        // Sanitize date fields
        if (isset($data['repost_date'])) {
            $sanitized['repost_date'] = sanitize_text_field($data['repost_date']);
        }
        
        // Keep JSON fields as arrays (they'll be encoded later)
        if (isset($data['engagement_metrics'])) {
            $sanitized['engagement_metrics'] = $data['engagement_metrics'];
        }
        
        if (isset($data['content_variations'])) {
            $sanitized['content_variations'] = $data['content_variations'];
        }
        
        if (isset($data['analysis_data'])) {
            $sanitized['analysis_data'] = $data['analysis_data'];
        }
        
        return $sanitized;
    }
    
    /**
     * Search reposts by text content
     *
     * @param string $search_term Search term
     * @param array $orderby Order by conditions
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function search_reposts($search_term, $orderby = array(), $limit = 0, $offset = 0) {
        if (empty($search_term)) {
            return array();
        }
        
        $table_name = $this->tables['reposts'];
        
        $sql = "SELECT * FROM $table_name WHERE 
                original_text LIKE %s OR 
                source_handle LIKE %s";
        
        $search_pattern = '%' . $this->wpdb->esc_like($search_term) . '%';
        
        // Add ORDER BY clause
        if (!empty($orderby)) {
            $orderby_clause = $this->build_orderby_clause($orderby);
            $sql .= " ORDER BY $orderby_clause";
        }
        
        // Add LIMIT clause
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
            if ($offset > 0) {
                $sql .= " OFFSET $offset";
            }
        }
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $search_pattern, $search_pattern),
            ARRAY_A
        );
        
        // Decode JSON fields
        foreach ($results as &$row) {
            if (isset($row['engagement_metrics']) && $row['engagement_metrics']) {
                $row['engagement_metrics'] = json_decode($row['engagement_metrics'], true);
            }
            
            if (isset($row['content_variations']) && $row['content_variations']) {
                $row['content_variations'] = json_decode($row['content_variations'], true);
            }
            
            if (isset($row['analysis_data']) && $row['analysis_data']) {
                $row['analysis_data'] = json_decode($row['analysis_data'], true);
            }
        }
        
        $this->log_debug('Reposts search completed', array(
            'search_term' => $search_term,
            'count' => count($results)
        ));
        
        return $results;
    }
    
    /**
     * Get repost analytics data
     *
     * @param string $period Period for analytics (daily, weekly, monthly)
     * @param string $start_date Start date (Y-m-d format)
     * @param string $end_date End date (Y-m-d format)
     * @return array
     */
    public function get_repost_analytics($period = 'daily', $start_date = null, $end_date = null) {
        $table_name = $this->tables['reposts'];
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        // Build date format based on period
        switch ($period) {
            case 'weekly':
                $date_format = '%Y-%u'; // Year-week
                $group_by = 'YEARWEEK(repost_date)';
                break;
            case 'monthly':
                $date_format = '%Y-%m'; // Year-month
                $group_by = 'YEAR(repost_date), MONTH(repost_date)';
                break;
            case 'daily':
            default:
                $date_format = '%Y-%m-%d'; // Year-month-day
                $group_by = 'DATE(repost_date)';
                break;
        }
        
        $sql = "SELECT 
                DATE_FORMAT(repost_date, '$date_format') as period,
                COUNT(*) as total_reposts,
                COUNT(DISTINCT source_handle) as unique_sources,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(JSON_EXTRACT(engagement_metrics, '$.likes')) as avg_likes,
                AVG(JSON_EXTRACT(engagement_metrics, '$.retweets')) as avg_retweets,
                AVG(JSON_EXTRACT(engagement_metrics, '$.replies')) as avg_replies,
                SUM(JSON_EXTRACT(engagement_metrics, '$.likes')) as total_likes,
                SUM(JSON_EXTRACT(engagement_metrics, '$.retweets')) as total_retweets,
                SUM(JSON_EXTRACT(engagement_metrics, '$.replies')) as total_replies
                FROM $table_name 
                WHERE DATE(repost_date) BETWEEN %s AND %s
                GROUP BY $group_by
                ORDER BY period ASC";
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $start_date, $end_date),
            ARRAY_A
        );
        
        $this->log_debug('Repost analytics retrieved', array(
            'period' => $period,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'count' => count($results)
        ));
        
        return $results;
    }
    
    /**
     * Export reposts data
     *
     * @param array $where Where conditions
     * @param array $orderby Order by conditions
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function export_reposts($where = array(), $orderby = array(), $limit = 0, $offset = 0) {
        $reposts = $this->get_reposts($where, $orderby, $limit, $offset);
        
        $export_data = array();
        
        foreach ($reposts as $repost) {
            $export_row = array(
                'id' => $repost['id'],
                'source_handle' => $repost['source_handle'],
                'original_tweet_id' => $repost['original_tweet_id'],
                'original_text' => $repost['original_text'],
                'platform' => $repost['platform'],
                'repost_date' => $repost['repost_date'],
                'post_id' => $repost['post_id'],
                'user_id' => $repost['user_id'],
                'repost_count' => $repost['repost_count'],
                'is_analyzed' => $repost['is_analyzed'],
                'created_at' => $repost['created_at'],
                'updated_at' => $repost['updated_at']
            );
            
            // Add engagement metrics as separate columns
            if (isset($repost['engagement_metrics']) && is_array($repost['engagement_metrics'])) {
                $export_row['likes'] = isset($repost['engagement_metrics']['likes']) ? $repost['engagement_metrics']['likes'] : 0;
                $export_row['retweets'] = isset($repost['engagement_metrics']['retweets']) ? $repost['engagement_metrics']['retweets'] : 0;
                $export_row['replies'] = isset($repost['engagement_metrics']['replies']) ? $repost['engagement_metrics']['replies'] : 0;
            }
            
            $export_data[] = $export_row;
        }
        
        $this->log_debug('Reposts exported', array(
            'count' => count($export_data)
        ));
        
        return $export_data;
    }

    /**
     * Start database transaction
     *
     * @return bool True on success
     */
    public function start_transaction() {
        $this->wpdb->query('START TRANSACTION');
        return true;
    }

    /**
     * Commit database transaction
     *
     * @return bool True on success
     */
    public function commit_transaction() {
        $this->wpdb->query('COMMIT');
        return true;
    }

    /**
     * Rollback database transaction
     *
     * @return bool True on success
     */
    public function rollback_transaction() {
        $this->wpdb->query('ROLLBACK');
        return true;
    }
} 