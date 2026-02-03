<?php
/**
 * Plugin Name: EcoPantry
 * Description: Manage your pantry, reduce waste, get AI recipe suggestions, and track expiry.
 * Version: 1.0
 * Author: Sneha
 */

// Include DB functions
require_once(plugin_dir_path(__FILE__) . 'includes/db.php');

// Enqueue CSS & JS
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('ecopantry-style', plugin_dir_url(__FILE__) . 'public/styles.css');
    wp_enqueue_script('ecopantry-script', plugin_dir_url(__FILE__) . 'public/scripts.js', ['jquery'], false, true);
});

// ------------------ USER SHORTCODE -------------------
add_shortcode('eco_pantry', function () {

    if (!is_user_logged_in()) {
        return "<p>Please log in to access your pantry.</p>";
    }

    $user_id = get_current_user_id();

    $master_items = [
        'potato'=>'Vegetables','tomato'=>'Vegetables','onion'=>'Vegetables','carrot'=>'Vegetables',
        'spinach'=>'Vegetables','cabbage'=>'Vegetables','cauliflower'=>'Vegetables','brinjal'=>'Vegetables',
        'ladyfinger'=>'Vegetables','peas'=>'Vegetables','beans'=>'Vegetables','capsicum'=>'Vegetables',
        'cucumber'=>'Vegetables','pumpkin'=>'Vegetables','bottle gourd'=>'Vegetables','ridge gourd'=>'Vegetables',
        'bitter gourd'=>'Vegetables','sweet corn'=>'Vegetables','beetroot'=>'Vegetables','radish'=>'Vegetables',
        'apple'=>'Fruits','banana'=>'Fruits','orange'=>'Fruits','mango'=>'Fruits','grapes'=>'Fruits',
        'papaya'=>'Fruits','pineapple'=>'Fruits','watermelon'=>'Fruits','muskmelon'=>'Fruits',
        'pomegranate'=>'Fruits','guava'=>'Fruits','pear'=>'Fruits','strawberry'=>'Fruits',
        'kiwi'=>'Fruits','cherry'=>'Fruits','milk'=>'Dairy','curd'=>'Dairy','yogurt'=>'Dairy',
        'butter'=>'Dairy','cheese'=>'Dairy','paneer'=>'Dairy','buttermilk'=>'Dairy','cream'=>'Dairy',
        'condensed milk'=>'Dairy','ghee'=>'Dairy','rice'=>'Grains','basmati rice'=>'Grains',
        'brown rice'=>'Grains','wheat'=>'Grains','flour'=>'Grains','maida'=>'Grains','oats'=>'Grains',
        'barley'=>'Grains','cornflakes'=>'Grains','rava'=>'Grains','poha'=>'Grains','toor dal'=>'Pulses',
        'moong dal'=>'Pulses','masoor dal'=>'Pulses','chana dal'=>'Pulses','urad dal'=>'Pulses',
        'rajma'=>'Pulses','chickpeas'=>'Pulses','black beans'=>'Pulses','soybeans'=>'Pulses',
        'lentils'=>'Pulses','egg'=>'Protein','chicken'=>'Protein','fish'=>'Protein','mutton'=>'Protein',
        'prawns'=>'Protein','tofu'=>'Protein','soy chunks'=>'Protein','turmeric'=>'Spices',
        'red chilli powder'=>'Spices','coriander powder'=>'Spices','cumin seeds'=>'Spices',
        'mustard seeds'=>'Spices','garam masala'=>'Spices','black pepper'=>'Spices','cardamom'=>'Spices',
        'cloves'=>'Spices','cinnamon'=>'Spices','bay leaf'=>'Spices','bread'=>'Packaged',
        'biscuits'=>'Packaged','noodles'=>'Packaged','pasta'=>'Packaged','ketchup'=>'Packaged',
        'mayonnaise'=>'Packaged','jam'=>'Packaged','peanut butter'=>'Packaged','corn chips'=>'Packaged',
        'chocolates'=>'Packaged','instant soup'=>'Packaged','sugar'=>'Essentials','salt'=>'Essentials',
        'jaggery'=>'Essentials','tea leaves'=>'Essentials','coffee powder'=>'Essentials',
        'cooking oil'=>'Essentials','sunflower oil'=>'Essentials','mustard oil'=>'Essentials',
        'olive oil'=>'Essentials','honey'=>'Essentials','avocado'=>'Fruits','broccoli'=>'Vegetables',
        'chikoo'=>'Fruits'
    ];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zwp_item_name'])) {
        $item_name = sanitize_text_field($_POST['zwp_item_name']);
        $quantity = intval($_POST['zwp_quantity']);
        $expiry_date = sanitize_text_field($_POST['zwp_expiry']);
        $selected_category = sanitize_text_field($_POST['zwp_category']);

        if ($quantity <= 0) {
            echo "<script>alert('Quantity must be greater than 0');</script>";
        } else {
            $item_key = strtolower($item_name);

            if (isset($master_items[$item_key])) {
                // Master item → force master category
                $category_to_save = $master_items[$item_key];
            } else {
                // Unknown item → save as Other for admin approval
                $category_to_save = 'Other';
            }

            add_pantry_item($user_id, $item_name, $category_to_save, $quantity, $expiry_date);
        }
    }

    // Display pantry items
    $items = get_pantry_items($user_id);

    ob_start(); ?>
    <div class="zwp-container">
        <h2>My Pantry</h2>
        <form method="post">
            <input type="text" name="zwp_item_name" placeholder="Item name" required>
            <input type="number" name="zwp_quantity" placeholder="Qty" required>
            <input type="date" name="zwp_expiry" required>
            <select name="zwp_category" required>
                <option value="">Select Category</option>
                <?php
                $categories = array_unique(array_values($master_items));
                foreach ($categories as $cat) {
                    echo "<option value='" . esc_attr($cat) . "'>$cat</option>";
                }
                ?>
            </select>
            <button type="submit">Add Item</button>
        </form>

        <h3>Pantry Items</h3>
        <table>
            <tr><th>Item</th><th>Category</th><th>Qty</th><th>Expiry</th></tr>
            <?php foreach ($items as $i): ?>
            <tr>
                <td><?= esc_html($i[1]) ?></td>
                <td><?= esc_html($i[2]) ?></td>
                <td><?= esc_html($i[3]) ?></td>
                <td><?= esc_html($i[4]) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php
    return ob_get_clean();
});

// ------------------ ADMIN MENU & APPROVAL -------------------
add_action('admin_menu', function () {
    add_menu_page(
        'Pantry Approval',
        'Pantry Approval',
        'manage_options',
        'zwp-approval',
        'zwp_admin_approval_page',
        'dashicons-yes',
        30
    );
});

function zwp_admin_approval_page() {
    if (isset($_POST['zwp_approve_item'])) {
        $item_name = sanitize_text_field($_POST['item_name']);
        $category = sanitize_text_field($_POST['category']);

        approve_food($item_name, $category);
        update_pantry_category($item_name, $category);

        echo "<div class='updated'><p>Item '$item_name' approved as '$category'.</p></div>";
    }

    $pending_items = get_all_other_items();
    ?>
    <div class="wrap">
        <h1>Pantry Approval</h1>
        <?php if (empty($pending_items)): ?>
            <p>No items pending approval.</p>
        <?php else: ?>
            <table class="widefat">
                <tr><th>Item</th><th>Assign Category</th><th>Action</th></tr>
                <?php foreach ($pending_items as $item): ?>
                <tr>
                    <form method="post">
                        <td><?= esc_html($item) ?></td>
                        <td>
                            <select name="category" required>
                                <option value="">Select Category</option>
                                <?php
                                $all_categories = [
                                    'Vegetables','Fruits','Dairy','Grains','Pulses','Protein','Spices','Packaged','Essentials'
                                ];
                                foreach ($all_categories as $cat) {
                                    echo "<option value='" . esc_attr($cat) . "'>$cat</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <input type="hidden" name="item_name" value="<?= esc_attr($item) ?>">
                            <button type="submit" name="zwp_approve_item" class="button button-primary">Approve</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
