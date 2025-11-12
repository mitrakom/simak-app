# Settings Page Guide

## Overview
The Settings page provides a centralized location for managing institution-wide configurations including general settings, theme customization, and security options.

## Accessing Settings

### Via Navbar User Menu
1. Click on your profile avatar in the top-right corner
2. Select **Settings** from the dropdown menu

### Via Sidebar
1. Navigate to the **Settings** menu item in the main navigation sidebar
2. The menu is located after Dashboard, Users, and Analytics

## Available Tabs

### 1. General Settings
Configure basic institution information and feeder integration.

**Fields:**
- **Institution Name**: The display name for your institution
- **Slug**: URL-safe identifier (read-only after creation)
- **Feeder URL**: PDDikti Feeder API endpoint
- **Feeder Username**: Authentication username for Feeder API
- **Feeder Password**: Authentication password (leave blank to keep current)

**Example:**
```
Institution Name: Universitas Indonesia Timur
Slug: uit (cannot be changed)
Feeder URL: https://feeder.example.com
Feeder Username: admin_uit
Feeder Password: ******** (optional update)
```

### 2. Theme Settings
Customize your institution's brand colors and appearance.

**Features:**
- **Color Selection**: Choose from 22 Tailwind colors for primary, secondary, and accent colors
- **Live Preview**: See color changes in real-time before saving
- **Theme Mode**: Select Light, Dark, or Auto (follows system preference)
- **Custom CSS**: Advanced customization with custom CSS rules

**Available Colors:**
- Slate, Gray, Zinc, Neutral, Stone
- Red, Orange, Amber, Yellow
- Lime, Green, Emerald, Teal
- Cyan, Sky, Blue, Indigo
- Violet, Purple, Fuchsia, Pink, Rose

**Color Picker:**
- Click on any color swatch to select it
- Selected color will show a checkmark
- Preview updates immediately at the top

**Theme Mode Options:**
- **Light**: Always use light theme
- **Dark**: Always use dark theme
- **Auto**: Follow system dark mode preference

**Custom CSS Example:**
```css
/* Add custom brand fonts */
h1, h2, h3 {
    font-family: 'Your Custom Font', sans-serif;
}

/* Custom button styling */
.btn-primary {
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
```

**Important Notes:**
- After saving theme settings, the page will automatically reload to apply changes
- Theme changes affect all pages across your institution's dashboard
- Custom CSS should be used with caution to avoid breaking existing styles

### 3. Security Settings
*(Coming in future updates)*

Manage security configurations and access controls.

## Route Information

**Route Name:** `admin.settings`  
**URL Pattern:** `{institusi}/admin/settings`  
**Example URL:** `https://yourdomain.com/uit/admin/settings`

**Middleware:**
- `auth` - Requires authentication
- `belongs.to.institusi` - Ensures user belongs to the institution
- `validate.institusi.exists` - Validates institution slug

## Permissions

Currently, any authenticated user belonging to the institution can access Settings. In future updates, this will be restricted to admin and superadmin roles only.

## Technical Details

### Database Fields Modified

**General Settings (Table: `institusis`):**
- `nama` - Institution name
- `feeder_url` - Feeder API URL
- `feeder_username` - Feeder username
- `feeder_password` - Feeder password (encrypted)

**Theme Settings (Table: `institusis`):**
- `theme_primary_color` - Primary brand color (default: blue)
- `theme_secondary_color` - Secondary brand color (default: purple)
- `theme_accent_color` - Accent color (default: indigo)
- `theme_mode` - Theme mode: light, dark, or auto (default: auto)
- `custom_css` - Custom CSS rules (nullable)

### Livewire Component

**Component Path:** `resources/views/livewire/admin/settings/index.blade.php`  
**Type:** Livewire Volt (Functional API)

**State Variables:**
```php
'institusi' => null,
'activeTab' => 'general',
'nama' => '',
'slug' => '',
'feeder_url' => '',
'feeder_username' => '',
'feeder_password' => '',
'theme_primary_color' => 'blue',
'theme_secondary_color' => 'purple',
'theme_accent_color' => 'indigo',
'theme_mode' => 'auto',
'custom_css' => '',
'successMessage' => '',
'errorMessage' => '',
```

**Available Methods:**
- `setActiveTab(string $tab)` - Switch between tabs
- `updateGeneral()` - Save general settings
- `updateTheme()` - Save theme settings and reload page

**Events:**
- `theme-updated` - Dispatched after theme save, triggers page reload

## Usage Examples

### Changing Institution Theme

1. Navigate to **Settings** from the navbar or sidebar
2. Click on the **Theme** tab
3. Select your desired colors:
   - Click **Blue** for primary color
   - Click **Indigo** for secondary color
   - Click **Sky** for accent color
4. Choose theme mode: **Auto**
5. Click **Save Theme Settings**
6. Page will reload with new colors applied

### Updating Feeder Configuration

1. Navigate to **Settings** > **General** tab
2. Fill in Feeder configuration:
   ```
   Feeder URL: https://feeder.ristekdikti.go.id/ws/live2.php
   Feeder Username: ws_uit_2024
   Feeder Password: your_secure_password
   ```
3. Click **Save General Settings**
4. Success message will appear

### Adding Custom CSS

1. Navigate to **Settings** > **Theme** tab
2. Scroll to **Custom CSS** textarea
3. Add your custom styles:
   ```css
   /* Customize card shadows */
   .card {
       box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
   }
   
   /* Custom primary button hover */
   .btn-primary:hover {
       transform: translateY(-2px);
       transition: all 0.3s ease;
   }
   ```
4. Click **Save Theme Settings**
5. Changes will be applied after page reload

## Troubleshooting

### Theme Changes Not Appearing
- Ensure you clicked **Save Theme Settings**
- Wait for the page to reload automatically
- Clear browser cache if needed
- Check browser console for JavaScript errors

### Cannot Save Settings
- Verify you are authenticated
- Ensure you belong to the institution
- Check for validation errors in the form
- Review error message displayed at the top

### Slug Cannot Be Changed
- This is by design for security and URL stability
- Slug is set during institution creation
- Contact system administrator if slug must be changed

## Best Practices

1. **Test Theme Changes**: Preview colors before saving to ensure brand consistency
2. **Backup Custom CSS**: Keep a copy of custom CSS in version control
3. **Use Theme Mode Auto**: Respects user's system preferences
4. **Minimal Custom CSS**: Only add custom CSS when absolutely necessary
5. **Feeder Security**: Never share Feeder credentials publicly

## Future Enhancements

Planned features for upcoming releases:
- [ ] Logo upload functionality
- [ ] Favicon upload functionality
- [ ] Advanced role-based access control
- [ ] Two-factor authentication settings
- [ ] Session management
- [ ] Audit log for settings changes
- [ ] Theme preview mode (test before applying)
- [ ] Export/Import theme configurations
- [ ] Preset color schemes (Material, Nord, Dracula, etc.)
- [ ] Real-time theme preview without page reload

## Related Documentation

- [THEME_GUIDE.md](./THEME_GUIDE.md) - Comprehensive theme system documentation
- [COMPONENT_GUIDE.md](./COMPONENT_GUIDE.md) - UI component reference
- [README_DASHBOARD.md](./README_DASHBOARD.md) - Dashboard overview

## Support

For issues or questions about Settings functionality:
1. Check this guide first
2. Review error messages carefully
3. Check browser console for technical details
4. Contact system administrator
