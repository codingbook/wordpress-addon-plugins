# Installation Instructions

## Quick Start

1. **Upload the Plugin**
   - Upload the entire `fluent-support-reporting-extended` folder to your WordPress site's `/wp-content/plugins/` directory
   - Or zip the folder and install through WordPress Admin → Plugins → Add New → Upload Plugin

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "FluentSupport Reporting Extended" and click "Activate"

3. **Configure Credentials**
   - Go to WordPress Admin → FS Reporting
   - Click on the "Credentials" tab
   - Enter your FluentSupport API credentials:
     - Username: Your FluentSupport username (e.g., "kevin")
     - Password: Your FluentSupport application password (e.g., "0YN5 d7ml T5XU nw3X tKqf L6Pe")
   - Click "Save Credentials"

4. **Generate Reports**
   - Click on the "Reports" tab
   - Select a date range using the date picker
   - Optionally select a specific agent from the dropdown
   - Click "Get Reports" to fetch and display the data

## Features Available

- **Date Range Selection**: Use the intuitive date picker to select start and end dates
- **Agent Filtering**: Filter reports by specific agents or view all agents
- **Real-time API Calls**: Fetch fresh data from FluentSupport API
- **Statistics Summary**: View total responses, active agents, and tickets handled
- **Detailed Response View**: See individual ticket responses with metadata

## Troubleshooting

- **No data showing**: Check that your API credentials are correct
- **API errors**: Verify that your FluentSupport account has API access
- **Permission errors**: Ensure you're logged in as an administrator

## API URL Format

The plugin constructs API URLs in this format:

```url
https://support.wpmanageninja.com/wp-json/fluent-support/v2/reports/ticket-response-stats?date_range[]=2025-07-11&date_range[]=2025-07-11&person_type=agent
```
