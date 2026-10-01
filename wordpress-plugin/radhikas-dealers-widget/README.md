# Radhikas Dealers Directory Elementor Widget

A modern, responsive, and customizable WordPress Elementor Widget to showcase all **Authorized Dealers** and **Special Dealers** from the **Radhikas ERP System API** with live search, district filters, and smooth pagination.

---

## Features
- **Live Search**: Instant debounced search by company name, contact person, district, or address.
- **District Dropdown Filter**: Dynamic dropdown auto-populated with all active districts.
- **Type Filter Tabs**: Quick filter between "All Dealers", "Special Dealers" (gold starred), and "Authorized Dealers" with real-time counts.
- **Premium Cards**:
  - Highlights **Special Dealers** with an amber/gold accent banner and badge.
  - Highlights **Authorized Dealers** with an emerald verified badge.
  - Shop/Company Name & Contact Person.
  - District & Full Address with map pin icon.
  - Click-to-Call button (`tel:...`) and Email button (`mailto:...`).
- **AJAX Pagination**: Fast page transitions with smooth scroll to top.
- **Elementor Controls**:
  - Configurable API endpoint URL (supports local dev, staging, or production).
  - Responsive column controls (1-4 columns for desktop, tablet, mobile).
  - Customizable typography, colors, borders, shadows, and padding.
  - Toggle visibility of any card element (phone, badge, address, email, etc.).
- **Fallback Shortcode**: Works anywhere via `[radhikas_dealers]` shortcode even without Elementor.

---

## Installation Guide for WordPress (`radhikastradeintl.com`)

### Method 1: Install via WordPress Admin (Recommended)
1. Download the `radhikas-dealers-widget.zip` file.
2. In your WordPress Admin Dashboard, navigate to **Plugins** &rarr; **Add New** &rarr; **Upload Plugin**.
3. Choose `radhikas-dealers-widget.zip` and click **Install Now**.
4. Click **Activate Plugin**.

### Method 2: Manual FTP / cPanel Upload
1. Extract the `radhikas-dealers-widget` folder.
2. Upload it to your WordPress directory: `wp-content/plugins/radhikas-dealers-widget/`.
3. In WordPress Admin, go to **Plugins** and activate **Radhikas Dealers Directory for Elementor**.

---

## How to Use in Elementor
1. Open any page or create a new page (e.g., `/dealers/` or `/our-dealers/`) and click **Edit with Elementor**.
2. In the Elementor widget search bar on the left panel, search for **Dealers Directory Grid** (found under the *Radhikas ERP Widgets* category).
3. Drag and drop the widget onto your page.
4. In the **Content** tab:
   - **API Endpoint URL**: Set to your ERP API URL (e.g. `https://erp.radhikastradeintl.com/api/dealers`).
   - Choose your desired **Dealers Per Page** (e.g., 12).
   - Turn on/off Search, District dropdown, or Filter tabs as desired.
5. In the **Style** tab:
   - Customize grid columns (e.g. 3 columns on desktop, 2 on tablet, 1 on mobile).
   - Customize card colors, fonts, badge styles, and button colors.
6. Click **Publish** or **Update**.

---

## Fallback Shortcode
You can also embed the dealers directory on any standard page, blog post, or Gutenberg block:
```html
[radhikas_dealers api_url="https://erp.radhikastradeintl.com/api/dealers" per_page="12" columns="3"]
```
