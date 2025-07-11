# FluentSupport Reporting Extended

A WordPress plugin that extends FluentSupport reporting capabilities by integrating with the FluentSupport API to provide detailed ticket response statistics.

## Features

- **API Integration**: Connects to FluentSupport API to fetch ticket response data
- **Credential Management**: Secure storage of API credentials
- **Date Range Filtering**: Use Flatpickr for intuitive date range selection
- **Agent Filtering**: Filter reports by specific agents
- **Interactive Dashboard**: Clean admin interface with tabbed navigation
- **Real-time Reports**: Fetch and display reports with AJAX
- **Statistics Summary**: Shows total responses, active agents, and tickets handled

## Installation

1. Upload the plugin files to `/wp-content/plugins/fluent-support-reporting-extended/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to 'FS Reporting' in the WordPress admin menu
4. Configure your API credentials in the Credentials tab

## Configuration

### API Credentials

1. Go to **FS Reporting** → **Credentials** tab
2. Enter your FluentSupport API credentials:
   - **Username**: Your FluentSupport username
   - **Password**: Your FluentSupport API password (application password)
3. Click **Save Credentials**

### Getting Reports

1. Switch to the **Reports** tab
2. Select a date range using the date picker
3. Optionally select a specific agent from the dropdown
4. Click **Get Reports** to fetch and display the data

## API Endpoint

The plugin calls the following FluentSupport API endpoint:

```url
https://support.wpmanageninja.com/wp-json/fluent-support/v2/reports/ticket-response-stats
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Valid FluentSupport API credentials

## Security

- All API credentials are stored securely using WordPress options
- AJAX requests are protected with nonces
- User capability checks ensure only administrators can access the functionality

## Files Structure

```text
fluent-support-reporting-extended/
├── fluent-support-reporting-extended.php  # Main plugin file
├── assets/
│   ├── css/
│   │   └── admin.css                      # Admin styles
│   └── js/
│       └── admin.js                       # Admin JavaScript
└── README.md                              # This file
```

## Changelog

### 1.0.0

- Initial release
- API integration with FluentSupport
- Credentials management
- Date range filtering with Flatpickr
- Agent filtering
- Interactive admin dashboard
- Real-time AJAX reporting

## Support

For support and questions, please create an issue in the plugin repository.
