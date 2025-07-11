# FluentCRM Custom Field Conditional Content

This solution extends FluentCRM to support dynamic content in email templates based on custom field values. It allows you to show different content (text, images, links) based on the values stored in your contacts' custom fields.

## Features

- **Conditional Content**: Show/hide content based on custom field values
- **Multiple Operators**: Support for equals, contains, greater than, less than, etc.
- **Shortcode Support**: Easy-to-use shortcodes for email templates
- **Gutenberg Block**: Visual editor support for conditional content
- **FluentCRM Integration**: Seamless integration with existing FluentCRM functionality

## Installation

1. Upload the `fluent-crm-custom-field-conditional.php` file to your WordPress plugins directory (`/wp-content/plugins/`)
2. Activate the plugin from the WordPress admin panel
3. The functionality will be automatically available in your FluentCRM email templates

## Usage

### Method 1: Shortcode in Email Templates

Use the shortcode directly in your email templates:

```html
[fc_custom_field_conditional field="your_field_slug" operator="equals" value="2" show_if_true="<img src='https://example.com/image1.jpg' alt='Special Image' />" show_if_false="<img src='https://example.com/default.jpg' alt='Default Image' />"]
```

### Method 2: Inline Shortcode Format

For more complex content, you can use the inline format:

```html
[fc_custom_field_conditional field="membership_level" operator="equals" value="premium" show_if_true="<h2>Premium Content</h2><p>Welcome to our premium section!</p>" show_if_false="<h2>Standard Content</h2><p>Upgrade to premium for more features.</p>"]
```

### Method 3: PHP Code Integration

You can also use the helper functions in your custom code:

```php
// Get a custom field value
$fieldValue = fluentcrm_get_custom_field_value('membership_level', $subscriber);

// Check a condition
$isPremium = fluentcrm_check_custom_field_condition('membership_level', 'equals', 'premium', $subscriber);

// Use in conditional logic
if ($isPremium) {
    echo '<img src="premium-image.jpg" alt="Premium" />';
} else {
    echo '<img src="standard-image.jpg" alt="Standard" />';
}
```

## Available Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `equals` or `=` | Exact match | `value="2"` |
| `not_equals` or `!=` | Not equal | `value="2"` |
| `contains` | Contains text | `value="premium"` |
| `not_contains` | Does not contain | `value="premium"` |
| `starts_with` | Starts with text | `value="premium"` |
| `ends_with` | Ends with text | `value="user"` |
| `greater_than` or `>` | Greater than (numeric) | `value="5"` |
| `less_than` or `<` | Less than (numeric) | `value="10"` |
| `greater_than_or_equal` or `>=` | Greater than or equal | `value="5"` |
| `less_than_or_equal` or `<=` | Less than or equal | `value="10"` |
| `empty` | Field is empty | `value=""` |
| `not_empty` | Field is not empty | `value=""` |

## Examples

### Example 1: Show Different Images Based on Membership Level

```html
[fc_custom_field_conditional field="membership_level" operator="equals" value="premium" show_if_true="<img src='https://yoursite.com/premium-banner.jpg' alt='Premium Member' style='width: 100%; max-width: 600px;' />" show_if_false="<img src='https://yoursite.com/standard-banner.jpg' alt='Standard Member' style='width: 100%; max-width: 600px;' />"]
```

### Example 2: Conditional Text Based on Purchase Count

```html
[fc_custom_field_conditional field="purchase_count" operator="greater_than" value="5" show_if_true="<h2>VIP Customer!</h2><p>Thank you for being a loyal customer with over 5 purchases!</p>" show_if_false="<h2>Welcome!</h2><p>Make your first purchase to unlock special benefits.</p>"]
```

### Example 3: Show Content Based on Location

```html
[fc_custom_field_conditional field="country" operator="contains" value="US" show_if_true="<p>Free shipping available for US customers!</p>" show_if_false="<p>International shipping available.</p>"]
```

### Example 4: Conditional Buttons

```html
[fc_custom_field_conditional field="subscription_status" operator="equals" value="active" show_if_true="<a href='https://yoursite.com/account' style='background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;'>Manage Account</a>" show_if_false="<a href='https://yoursite.com/subscribe' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;'>Subscribe Now</a>"]
```

## Custom Field Setup

Before using this functionality, make sure you have custom fields set up in FluentCRM:

1. Go to **FluentCRM > Settings > Custom Fields**
2. Create your custom fields (e.g., `membership_level`, `purchase_count`, `country`)
3. Note the field slug (this is what you'll use in the shortcode)

## Advanced Usage

### Multiple Conditions

For complex scenarios, you can combine multiple shortcodes:

```html
[fc_custom_field_conditional field="membership_level" operator="equals" value="premium" show_if_true="<h2>Premium Content</h2>" show_if_false=""]
[fc_custom_field_conditional field="membership_level" operator="equals" value="standard" show_if_true="<h2>Standard Content</h2>" show_if_false=""]
[fc_custom_field_conditional field="membership_level" operator="equals" value="basic" show_if_true="<h2>Basic Content</h2>" show_if_false=""]
```

### Using FluentCRM Shortcodes Inside Conditionals

You can use FluentCRM's built-in shortcodes inside the conditional content:

```html
[fc_custom_field_conditional field="membership_level" operator="equals" value="premium" show_if_true="<h2>Hello {{contact.first_name}}!</h2><p>Your premium membership expires on {{contact.custom.expiry_date}}</p>" show_if_false="<h2>Hello {{contact.first_name}}!</h2><p>Upgrade to premium for exclusive benefits.</p>"]
```

## Troubleshooting

### Common Issues

1. **Shortcode not working**: Make sure the plugin is activated and the custom field slug is correct
2. **Content not showing**: Check that the custom field has a value for the contact
3. **Operator not working**: Verify the operator syntax and data types (numeric vs text)

### Debug Mode

To debug issues, you can temporarily add this to your theme's functions.php:

```php
add_action('wp_footer', function() {
    if (function_exists('fluentcrm_get_current_contact')) {
        $contact = fluentcrm_get_current_contact();
        if ($contact) {
            echo '<script>console.log("Contact custom fields:", ' . json_encode($contact->custom_fields()) . ');</script>';
        }
    }
});
```

## Integration with WP Fusion Pro

If you're using WP Fusion Pro, this solution works seamlessly with it:

1. WP Fusion syncs data to FluentCRM custom fields
2. This plugin reads those custom field values
3. Conditional content is displayed based on the synced data

### Example with WP Fusion

```html
[fc_custom_field_conditional field="course_progress" operator="greater_than" value="50" show_if_true="<h2>Great Progress!</h2><p>You're more than halfway through your course.</p>" show_if_false="<h2>Keep Going!</h2><p>Complete more lessons to unlock advanced content.</p>"]
```

## Performance Considerations

- The plugin is lightweight and doesn't impact email sending performance
- Custom field values are cached during email processing
- Shortcodes are parsed only when needed

## Support

For issues or questions:
1. Check that your custom fields are properly configured
2. Verify the shortcode syntax
3. Test with a simple condition first
4. Check the browser console for any JavaScript errors

## Changelog

### Version 1.0.0
- Initial release
- Shortcode support for custom field conditionals
- Multiple operator support
- FluentCRM integration
- Helper functions for developers