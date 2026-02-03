<?php
global $wpdb;

// ------------------ CREATE TABLES -------------------
function zwp_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $pantry_table = $wpdb->prefix . 'pantry_items';
    $food_table   = $wpdb->prefix . 'food_master';

    $sql = "
    CREATE TABLE IF NOT EXISTS $pantry_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT,
        item_name VARCHAR(100),
        category VARCHAR(50),
        quantity INT,
        expiry_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS $food_table (
        item_name VARCHAR(100) PRIMARY KEY,
        category VARCHAR(50),
        approved TINYINT DEFAULT 0
    ) $charset_collate;
    ";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'zwp_create_tables');

// ------------------ PANTRY FUNCTIONS -------------------
function add_pantry_item($user_id, $item_name, $category, $quantity, $expiry_date) {
    global $wpdb;
    $table = $wpdb->prefix . 'pantry_items';

    $wpdb->insert(
        $table,
        [
            'user_id'     => $user_id,
            'item_name'   => $item_name,
            'category'    => $category,
            'quantity'    => $quantity,
            'expiry_date' => $expiry_date
        ],
        ['%d', '%s', '%s', '%d', '%s']
    );
}

function get_pantry_items($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'pantry_items';

    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC", $user_id),
        ARRAY_A
    );

    $formatted = [];
    foreach ($results as $r) {
        $formatted[] = [
            $r['id'],
            $r['item_name'],
            $r['category'],
            $r['quantity'],
            $r['expiry_date']
        ];
    }
    return $formatted;
}

// ------------------ FOOD MASTER / ADMIN APPROVAL -------------------
function get_all_other_items() {
    global $wpdb;
    $food_table = $wpdb->prefix . 'food_master';
    $pantry_table = $wpdb->prefix . 'pantry_items';

    // Fetch distinct items in pantry where category = 'Other' and not yet approved
    $results = $wpdb->get_col("
        SELECT DISTINCT p.item_name
        FROM $pantry_table p
        LEFT JOIN $food_table f ON LOWER(p.item_name) = LOWER(f.item_name)
        WHERE p.category = 'Other' AND (f.approved IS NULL OR f.approved = 0)
    ");

    return $results ?: [];
}

function approve_food($item_name, $category) {
    global $wpdb;
    $food_table = $wpdb->prefix . 'food_master';

    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $food_table WHERE LOWER(item_name) = LOWER(%s)", $item_name));
    if ($exists) {
        $wpdb->update(
            $food_table,
            ['category' => $category, 'approved' => 1],
            ['item_name' => $item_name],
            ['%s', '%d'],
            ['%s']
        );
    } else {
        $wpdb->insert(
            $food_table,
            ['item_name' => $item_name, 'category' => $category, 'approved' => 1],
            ['%s', '%s', '%d']
        );
    }
}

function update_pantry_category($item_name, $category) {
    global $wpdb;
    $pantry_table = $wpdb->prefix . 'pantry_items';

    $wpdb->update(
        $pantry_table,
        ['category' => $category],
        ['item_name' => $item_name, 'category' => 'Other'],
        ['%s'],
        ['%s']
    );
}