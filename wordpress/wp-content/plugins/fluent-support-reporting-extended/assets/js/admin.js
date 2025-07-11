jQuery(document).ready(function ($) {
    // Initialize date range picker
    const dateRangePicker = flatpickr("#date_range", {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: [new Date(), new Date()],
        maxDate: new Date()
    });

    // Tab navigation
    $('.nav-tab').on('click', function (e) {
        e.preventDefault();
        const target = $(this).attr('href');

        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').hide();

        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        $(target).show();

        // Load agents when switching to reports tab
        if (target === '#reports') {
            loadAgents();
        }
    });

    // Save credentials
    $('#fsre-credentials-form').on('submit', function (e) {
        e.preventDefault();

        const formData = {
            action: 'fsre_save_credentials',
            nonce: fsre_ajax.nonce,
            user_name: $('#user_name').val(),
            user_pass: $('#user_pass').val()
        };

        $.post(fsre_ajax.ajax_url, formData, function (response) {
            if (response.success) {
                showNotice(response.data, 'success');
            } else {
                showNotice(response.data, 'error');
            }
        });
    });

    // Test API Connection
    $('#test_api').on('click', function () {
        const button = $(this);
        button.prop('disabled', true).text('Testing...');

        const testData = {
            action: 'fsre_test_api',
            nonce: fsre_ajax.nonce
        };

        $.post(fsre_ajax.ajax_url, testData, function (response) {
            button.prop('disabled', false).text('Test API Connection');

            if (response.success) {
                const data = response.data;
                let message = `API Test Results:\n`;
                message += `URL: ${data.url}\n`;
                message += `Response Code: ${data.response_code}\n`;
                message += `Is JSON: ${data.is_json}\n`;
                message += `Response: ${data.body.substring(0, 200)}...`;

                console.log('API Test Results:', data);
                showNotice('API test completed. Check console for details.', 'success');
                alert(message);
            } else {
                showNotice(response.data, 'error');
                console.error('API Test Failed:', response.data);
            }
        }).fail(function () {
            button.prop('disabled', false).text('Test API Connection');
            showNotice('Test request failed. Please try again.', 'error');
        });
    });

    // Get reports
    $('#get_reports').on('click', function () {
        const dateRange = $('#date_range').val();
        const agentId = $('#agent_select').val();

        if (!dateRange) {
            showNotice('Please select a date range', 'error');
            return;
        }

        $('#loading').show();
        $('#reports_container').empty();

        const requestData = {
            action: 'fsre_get_reports',
            nonce: fsre_ajax.nonce,
            date_range: dateRange,
            agent_id: agentId
        };

        $.post(fsre_ajax.ajax_url, requestData, function (response) {
            $('#loading').hide();

            if (response.success) {
                displayReports(response.data);
            } else {
                showNotice(response.data, 'error');
            }
        }).fail(function () {
            $('#loading').hide();
            showNotice('Request failed. Please try again.', 'error');
        });
    });

    // Load agents
    function loadAgents() {
        $.post(fsre_ajax.ajax_url, {
            action: 'fsre_get_agents',
            nonce: fsre_ajax.nonce
        }, function (response) {
            if (response.success) {
                const select = $('#agent_select');
                select.find('option:not(:first)').remove();

                response.data.forEach(function (agent) {
                    select.append('<option value="' + agent.id + '">' + agent.name + '</option>');
                });
            }
        });
    }

    // Display reports
    function displayReports(data) {
        const container = $('#reports_container');

        if (!data || data.length === 0) {
            container.html('<p>No reports found for the selected criteria.</p>');
            return;
        }

        // Create stats summary
        const totalResponses = data.length;
        const uniqueAgents = [...new Set(data.map(item => item.full_name))].length;
        const uniqueTickets = [...new Set(data.map(item => item.ticket_id))].length;

        const summaryHtml = `
            <div class="stats-summary">
                <div class="stat-box">
                    <div class="stat-number">${totalResponses}</div>
                    <div class="stat-label">Total Responses</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">${uniqueAgents}</div>
                    <div class="stat-label">Active Agents</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">${uniqueTickets}</div>
                    <div class="stat-label">Tickets Handled</div>
                </div>
            </div>
        `;

        // Create report items
        let reportsHtml = '<h3>Response Details</h3>';
        data.forEach(function (item) {
            const content = stripHtml(item.content);
            const date = new Date(item.created_at).toLocaleString();

            reportsHtml += `
                <div class="report-item">
                    <div class="report-meta">
                        <span class="report-agent">${item.full_name}</span>
                        <span class="report-ticket-id">Ticket #${item.ticket_id}</span>
                        <span class="report-date">${date}</span>
                    </div>
                    <div class="report-content">${content}</div>
                </div>
            `;
        });

        container.html(summaryHtml + reportsHtml);
    }

    // Show notice
    function showNotice(message, type) {
        const noticeClass = type === 'error' ? 'notice error' : 'notice success';
        const notice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');

        $('.wrap h1').after(notice);

        setTimeout(function () {
            notice.fadeOut(function () {
                $(this).remove();
            });
        }, 5000);
    }

    // Strip HTML tags
    function stripHtml(html) {
        const tmp = document.createElement('DIV');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }

    // Initialize
    loadAgents();
});
