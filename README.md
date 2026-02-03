# EcoPantry – Waste Management

**EcoPantry** is a WordPress plugin designed to help users manage their pantry, reduce food waste, and get insights on household food consumption. The plugin includes automatic categorization, admin approval for new items, recipe suggestions, expiry alerts, and dashboards for waste management.

---

## Features

### User Side
- Add and manage pantry items with quantities and expiry dates.
- Automatic categorization of common food items.
- Dropdown for users to select category; incorrect or new items are marked as `Other`.

### Admin Side
- Approve new or unrecognized items submitted by users.
- Update categories for items stored as `Other`.
- Manage user pantry data and analytics.
- **Coming Soon:** Waste dashboard for all users and analytics.

### Coming Soon
- Expiry alerts to notify users before items go bad.
- Track pantry usage and food items.
- Waste dashboard showing usage trends and potential food waste.

---

## Installation
1. Clone or download the repository into your WordPress `wp-content/plugins` folder:
  
```bash
git clone https://github.com/<your-username>/EcoPantry.git
```
2. Activate the plugin from WordPress Admin → Plugins.
3. The database tables (wp_pantry_items and wp_food_master) will be created automatically on activation.
