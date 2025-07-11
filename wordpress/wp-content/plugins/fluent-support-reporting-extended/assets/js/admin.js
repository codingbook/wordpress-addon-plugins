jQuery(document).ready(function ($) {
    // Initialize date range pickers
    const dateRangePicker = flatpickr("#date_range", {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: [new Date(), new Date()],
        maxDate: new Date()
    });

    const agentResponsesDateRangePicker = flatpickr("#agent_responses_date_range", {
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

    // Get agent responses
    $('#get_agent_responses').on('click', function () {
        const dateRange = $('#agent_responses_date_range').val();

        if (!dateRange) {
            showNotice('Please select a date range', 'error');
            return;
        }

        $('#agent_responses_loading').show();
        $('#agent_responses_table_container').empty();
        $('#agent_responses_chart_container').empty();
        $('#agent_responses_details_container').empty();

        const requestData = {
            action: 'fsre_get_agent_responses',
            nonce: fsre_ajax.nonce,
            date_range: dateRange
        };

        $.post(fsre_ajax.ajax_url, requestData, function (response) {
            $('#agent_responses_loading').hide();

            if (response.success) {
                displayAgentResponses(response.data);
            } else {
                showNotice(response.data, 'error');
            }
        }).fail(function () {
            $('#agent_responses_loading').hide();
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

    // Display agent responses
    function displayAgentResponses(data) {
        const { agents, time_series } = data;

        // Display agent table
        displayAgentTable(agents);

        // Display chart
        displayAgentChart(agents, time_series);
    }

    // Display agent cards
    function displayAgentTable(agents) {
        const container = $('#agent_responses_table_container');

        // Filter out agents with 0 responses
        const activeAgents = agents.filter(agent => agent.total_responses > 0);

        let cardsHtml = `
            <h3>Agent Response Statistics</h3>
            <div class="agent-cards-grid">
        `;

        activeAgents.forEach(function (agent) {
            const firstResponse = agent.first_response ? new Date(agent.first_response).toLocaleString() : 'N/A';
            const lastResponse = agent.last_response ? new Date(agent.last_response).toLocaleString() : 'N/A';

            cardsHtml += `
                <div class="agent-card">
                    <div class="agent-card-header">
                        <div class="agent-header-left">
                            <h4>${agent.full_name}</h4>
                            <div class="agent-stats">
                                <span class="stat-item">
                                    <strong>${agent.total_responses}</strong> responses
                                </span>
                                <span class="stat-item">
                                    <strong>${agent.total_tickets}</strong> tickets
                                </span>
                            </div>
                        </div>
                        <div class="agent-header-right">
                            <div class="timeline-item">
                                <span class="timeline-label">First:</span>
                                <span class="timeline-value">${firstResponse}</span>
                            </div>
                            <div class="timeline-item">
                                <span class="timeline-label">Last:</span>
                                <span class="timeline-value">${lastResponse}</span>
                            </div>
                        </div>
                    </div>
                    <div class="agent-card-body">
                        <div class="recent-responses">
                            <h5>Recent Responses</h5>
                            <div class="response-list">
            `;

            // Show recent responses (last 5)
            const recentResponses = agent.response_details ? agent.response_details.slice(-5).reverse() : [];
            recentResponses.forEach(function (response) {
                const content = stripHtml(response.content).substring(0, 300) + (response.content.length > 300 ? '...' : '');
                const ticketUrl = `https://support.wpmanageninja.com/#/tickets/${response.ticket_id}/view`;
                cardsHtml += `
                    <div class="response-item">
                        <div class="response-time">${response.formatted_time}</div>
                        <div class="response-content">${content}</div>
                        <div class="response-ticket">
                            <a href="${ticketUrl}" target="_blank">Ticket #${response.ticket_id}</a>
                        </div>
                    </div>
                `;
            });

            cardsHtml += `
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        cardsHtml += `
            </div>
        `;

        container.html(cardsHtml);
    }

    // Display agent chart
    function displayAgentChart(agents, timeSeries) {
        const container = $('#agent_responses_chart_container');

        // Filter out agents with 0 responses
        const activeAgents = agents.filter(agent => agent.total_responses > 0);

        // Create chart container with agent selection
        container.html(`
            <h3>Response Time Series (24-Hour View)</h3>
            <div class="chart-controls">
                <div class="agent-selector">
                    <label>Show/Hide Agents:</label>
                    <div class="agent-checkboxes">
                        <label class="agent-checkbox">
                            <input type="checkbox" id="select-all-agents" checked> Select All
                        </label>
                        ${activeAgents.map(agent => `
                            <label class="agent-checkbox">
                                <input type="checkbox" class="agent-toggle" data-agent-id="${agent.id}" checked> ${agent.full_name}
                            </label>
                        `).join('')}
                    </div>
                </div>
            </div>
            <canvas id="agent_responses_chart"></canvas>
        `);

        // Prepare chart data
        const hours = Object.keys(timeSeries).sort();
        const datasets = [];

        // Create a dataset for each active agent
        activeAgents.forEach(function (agent, index) {
            const data = hours.map(hour => timeSeries[hour][agent.id] || 0);

            // Generate a color for each agent
            const colors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
                '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
                '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384', '#36A2EB'
            ];

            datasets.push({
                label: agent.full_name,
                data: data,
                borderColor: colors[index % colors.length],
                backgroundColor: colors[index % colors.length] + '20',
                borderWidth: 2,
                fill: false,
                tension: 0.1,
                pointRadius: 4,
                pointHoverRadius: 6,
                agentId: agent.id
            });
        });

        // Format hour labels for better display
        const formattedLabels = hours.map(hour => {
            const date = new Date(hour);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        });

        // Create chart
        const ctx = document.getElementById('agent_responses_chart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: formattedLabels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Agent Responses Over Time (24-Hour View) - Click on points to see details'
                    },
                    tooltip: {
                        enabled: false // Disable tooltips
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date & Time (Hourly)'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Number of Responses'
                        },
                        beginAtZero: true
                    }
                },
                onClick: function (event, elements) {
                    if (elements.length > 0) {
                        const element = elements[0];
                        const hour = hours[element.index];
                        showDateDetails(hour, timeSeries, activeAgents);

                        // Scroll to details section
                        $('#agent_responses_details_container')[0].scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                }
            }
        });

        // Add event listeners for agent selection
        $('#select-all-agents').on('change', function () {
            const isChecked = $(this).is(':checked');
            $('.agent-toggle').prop('checked', isChecked);
            updateChartVisibility(chart, isChecked);
        });

        $('.agent-toggle').on('change', function () {
            const agentId = $(this).data('agent-id');
            const isVisible = $(this).is(':checked');

            // Update chart dataset visibility
            const datasetIndex = chart.data.datasets.findIndex(dataset => dataset.agentId == agentId);
            if (datasetIndex !== -1) {
                chart.setDatasetVisibility(datasetIndex, isVisible);
                chart.update();
            }

            // Update select all checkbox
            const allChecked = $('.agent-toggle:checked').length === $('.agent-toggle').length;
            const someChecked = $('.agent-toggle:checked').length > 0;
            $('#select-all-agents').prop('indeterminate', someChecked && !allChecked);
            $('#select-all-agents').prop('checked', allChecked);
        });
    }

    // Update chart visibility based on select all
    function updateChartVisibility(chart, isVisible) {
        chart.data.datasets.forEach((dataset, index) => {
            chart.setDatasetVisibility(index, isVisible);
        });
        chart.update();
    }

    // Show hour details when chart point is clicked
    function showDateDetails(hour, timeSeries, agents) {
        const container = $('#agent_responses_details_container');
        const hourData = timeSeries[hour] || {};

        // Format the hour for display
        const date = new Date(hour);
        const formattedHour = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        let detailsHtml = `
            <h3>Responses for ${formattedHour}</h3>
            <div class="agent-cards-grid">
        `;

        agents.forEach(function (agent) {
            const responses = hourData[agent.id] || 0;
            if (responses > 0) {
                // Get responses for this specific hour
                const hourResponses = agent.response_details ? agent.response_details.filter(response => {
                    const responseHour = new Date(response.created_at);
                    return responseHour.toISOString().substring(0, 13) + ':00:00.000Z' === hour;
                }) : [];

                if (hourResponses.length > 0) {
                    const firstResponse = hourResponses[0].formatted_time;
                    const lastResponse = hourResponses[hourResponses.length - 1].formatted_time;

                    detailsHtml += `
                        <div class="agent-card">
                            <div class="agent-card-header">
                                <div class="agent-header-left">
                                    <h4>${agent.full_name}</h4>
                                    <div class="agent-stats">
                                        <span class="stat-item">
                                            <strong>${responses}</strong> responses
                                        </span>
                                        <span class="stat-item">
                                            <strong>${new Set(hourResponses.map(r => r.ticket_id)).size}</strong> tickets
                                        </span>
                                    </div>
                                </div>
                                <div class="agent-header-right">
                                    <div class="timeline-item">
                                        <span class="timeline-label">First:</span>
                                        <span class="timeline-value">${firstResponse}</span>
                                    </div>
                                    <div class="timeline-item">
                                        <span class="timeline-label">Last:</span>
                                        <span class="timeline-value">${lastResponse}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="agent-card-body">
                                <div class="recent-responses">
                                    <h5>Responses for this hour</h5>
                                    <div class="response-list">
                    `;

                    hourResponses.forEach(function (response) {
                        const content = stripHtml(response.content).substring(0, 300) + (response.content.length > 300 ? '...' : '');
                        const ticketUrl = `https://support.wpmanageninja.com/#/tickets/${response.ticket_id}/view`;
                        const responseTime = new Date(response.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                        detailsHtml += `
                            <div class="response-item">
                                <div class="response-time">${responseTime}</div>
                                <div class="response-content">${content}</div>
                                <div class="response-ticket">
                                    <a href="${ticketUrl}" target="_blank">Ticket #${response.ticket_id}</a>
                                </div>
                            </div>
                        `;
                    });

                    detailsHtml += `
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }
        });

        detailsHtml += `
            </div>
        `;

        container.html(detailsHtml);
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
