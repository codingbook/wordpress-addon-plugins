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

        // Load teams when switching to agent responses tab
        if (target === '#agent-responses') {
            loadTeams();
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
        let selectedAgents = $('#agent_filter').val() || [];
        let selectedTeams = $('#team_filter').val() || [];

        // Handle "All" selections - if empty string is selected, treat as "select all"
        if (selectedAgents.includes('')) {
            selectedAgents = [];
        }
        if (selectedTeams.includes('')) {
            selectedTeams = [];
        }

        if (!dateRange) {
            showNotice('Please select a date range', 'error');
            return;
        }

        $('#agent_responses_loading').show();
        $('#agent_responses_summary_container').empty();
        $('#agent_responses_table_container').empty();
        $('#agent_responses_chart_container').empty();
        $('#agent_responses_details_container').empty();

        const requestData = {
            action: 'fsre_get_agent_responses',
            nonce: fsre_ajax.nonce,
            date_range: dateRange,
            selected_agents: selectedAgents,
            selected_teams: selectedTeams
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

    // Load agents and teams
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

    // Load teams and populate dropdowns
    function loadTeams() {
        $.post(fsre_ajax.ajax_url, {
            action: 'fsre_get_teams',
            nonce: fsre_ajax.nonce
        }, function (response) {
            if (response.success) {
                const teamsData = response.data;

                // Populate agent filter dropdown
                const agentFilter = $('#agent_filter');
                agentFilter.empty();
                agentFilter.append('<option value="">All Agents</option>');

                teamsData.all_agents.forEach(function (agent) {
                    agentFilter.append('<option value="' + agent.id + '">' + agent.full_name + '</option>');
                });

                // Populate team filter dropdown
                const teamFilter = $('#team_filter');
                teamFilter.empty();
                teamFilter.append('<option value="">All Teams</option>');
                teamFilter.append('<option value="a">Team A</option>');
                teamFilter.append('<option value="b">Team B</option>');
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

        console.log('Display agent responses - raw data:', data);
        console.log('Agents:', agents);
        console.log('Time series:', time_series);

        // Filter out agents with no responses
        const activeAgents = agents.filter(agent => agent.total_responses > 0);

        console.log('Active agents:', activeAgents);

        if (activeAgents.length === 0) {
            $('#agent_responses_summary_container').html('<p>No agent responses found for the selected criteria.</p>');
            return;
        }

        // Display summary table
        displayAgentSummary(activeAgents);

        // Display agent table with toggle button
        displayAgentTable(activeAgents);

        // Display chart
        displayAgentChart(activeAgents, time_series);
    }

    // Display agent summary table
    function displayAgentSummary(agents) {
        const container = $('#agent_responses_summary_container');

        let summaryHtml = `
            <div class="agent-summary-section">
                <h3>Agent Response Summary</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Agent Name</th>
                            <th>Total Responses</th>
                            <th>Interactions (Tickets)</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Time Difference</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        let totalResponses = 0;
        let totalInteractions = 0;

        agents.forEach(function (agent) {
            const startTime = agent.first_response ? new Date(agent.first_response) : null;
            const endTime = agent.last_response ? new Date(agent.last_response) : null;

            let timeDifference = '';
            if (startTime && endTime) {
                const diffMs = endTime - startTime;
                const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
                timeDifference = `${diffHours}h ${diffMinutes}m`;
            }

            const startTimeFormatted = startTime ? startTime.toLocaleString() : 'N/A';
            const endTimeFormatted = endTime ? endTime.toLocaleString() : 'N/A';

            summaryHtml += `
                <tr>
                    <td><strong>${agent.full_name}</strong></td>
                    <td>${agent.total_responses}</td>
                    <td>${agent.total_tickets}</td>
                    <td>${startTimeFormatted}</td>
                    <td>${endTimeFormatted}</td>
                    <td>${timeDifference}</td>
                </tr>
            `;

            totalResponses += agent.total_responses;
            totalInteractions += agent.total_tickets;
        });

        // Add totals row
        summaryHtml += `
                    <tr class="summary-totals">
                        <td><strong>TOTALS</strong></td>
                        <td><strong>${totalResponses}</strong></td>
                        <td><strong>${totalInteractions}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        `;

        container.html(summaryHtml);
    }

    // Display agent cards
    function displayAgentTable(agents) {
        const container = $('#agent_responses_table_container');

        let cardsHtml = `
            <div class="agent-cards-header">
                <h3>Agent Response Statistics</h3>
                <button class="button-secondary" id="toggle-agent-cards">Show All Agent Cards</button>
            </div>
            <div class="agent-cards-grid" style="display: none;">
        `;

        agents.forEach(function (agent) {
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

        // Add event listener for toggle button
        $('#toggle-agent-cards').on('click', function () {
            const button = $(this);
            const cardsGrid = $('.agent-cards-grid');

            if (cardsGrid.is(':visible')) {
                cardsGrid.hide();
                button.text('Show All Agent Cards');
            } else {
                cardsGrid.show();
                button.text('Hide All Agent Cards');
            }
        });
    }

    // Display agent chart
    function displayAgentChart(agents, timeSeries) {
        const container = $('#agent_responses_chart_container');

        // Create chart container with show/hide all controls
        container.html(`
            <div class="chart-controls">
                <h3>Response Time Series (24-Hour View) - Click on legends to show/hide agents</h3>
                <div class="chart-buttons">
                    <button class="button-secondary" id="show-all-agents">Show All Agents</button>
                    <button class="button-secondary" id="hide-all-agents">Hide All Agents</button>
                </div>
            </div>
            <canvas id="agent_responses_chart"></canvas>
        `);

        // Prepare chart data
        const hours = Object.keys(timeSeries).sort();
        const datasets = [];

        // Create a dataset for each active agent
        agents.forEach(function (agent, index) {
            const data = hours.map(hour => timeSeries[hour][agent.id] || 0);

            // Calculate total responses for this agent
            const totalResponses = data.reduce((sum, count) => sum + count, 0);

            // Generate a color for each agent
            const colors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
                '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
                '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384', '#36A2EB'
            ];

            datasets.push({
                label: `${agent.full_name} (${totalResponses})`,
                data: data,
                borderColor: colors[index % colors.length],
                backgroundColor: colors[index % colors.length] + '20',
                borderWidth: 2,
                fill: false,
                tension: 0.1,
                pointRadius: 4,
                pointHoverRadius: 6,
                agentId: agent.id,
                agentName: agent.full_name,
                totalResponses: totalResponses
            });
        });

        // Format hour labels for better display (using UTC)
        const formattedLabels = hours.map(hour => {
            const date = new Date(hour + 'Z'); // Add Z to treat as UTC
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' UTC';
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
                        const datasetIndex = element.datasetIndex;

                        console.log('Chart clicked - element:', element);
                        console.log('Hour index:', element.index);
                        console.log('Hour value:', hour);
                        console.log('Dataset index:', datasetIndex);
                        console.log('Time series keys:', Object.keys(timeSeries));

                        // Get the specific agent for this dataset
                        const clickedAgent = agents.find(agent => agent.id == datasets[datasetIndex].agentId);
                        console.log('Clicked agent:', clickedAgent ? clickedAgent.full_name : 'Unknown');

                        // Only show the specific agent that was clicked, not all agents with data at this point
                        const agentsToShow = clickedAgent ? [clickedAgent] : [];

                        showDateDetails(hour, timeSeries, agents, agentsToShow);

                        // Scroll to details section
                        $('#agent_responses_details_container')[0].scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                }
            }
        });

        // Add event handlers for show/hide all buttons
        $('#show-all-agents').on('click', function () {
            chart.data.datasets.forEach((dataset, index) => {
                chart.setDatasetVisibility(index, true);
            });
            chart.update();
        });

        $('#hide-all-agents').on('click', function () {
            chart.data.datasets.forEach((dataset, index) => {
                chart.setDatasetVisibility(index, false);
            });
            chart.update();
        });

        // Chart legend click handling is built into Chart.js
        // Users can click on legend items to show/hide datasets
    }

    // Update chart visibility based on select all
    function updateChartVisibility(chart, isVisible) {
        chart.data.datasets.forEach((dataset, index) => {
            chart.setDatasetVisibility(index, isVisible);
        });
        chart.update();
    }

    // Show hour details when chart point is clicked
    function showDateDetails(hour, timeSeries, agents, agentsWithData = null) {
        const container = $('#agent_responses_details_container');
        const hourData = timeSeries[hour] || {};

        // Debug logging
        console.log('Hour clicked:', hour);
        console.log('Hour data:', hourData);
        console.log('All agents:', agents);
        console.log('Agents with data at this point:', agentsWithData);

        // Use agentsWithData if provided, otherwise fall back to visible agents
        let agentsToShow = agentsWithData;
        if (!agentsToShow || agentsToShow.length === 0) {
            // Get currently visible agents from the chart
            const chart = Chart.getChart('agent_responses_chart');
            agentsToShow = agents.filter(agent => {
                const datasetIndex = chart.data.datasets.findIndex(dataset => dataset.agentId == agent.id);
                return datasetIndex !== -1 && chart.isDatasetVisible(datasetIndex);
            });
        }

        console.log('Agents to show:', agentsToShow);

        // Format the hour for display (using UTC)
        const date = new Date(hour + 'Z'); // Add Z to treat as UTC
        const formattedHour = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' UTC';

        let detailsHtml = `
            <div class="details-header">
                <h3>Responses for ${formattedHour}</h3>
                <button class="button-secondary" onclick="$('#agent_responses_details_container').empty(); $('#agent_responses_table_container').css('display', 'block');">Back to Overview</button>
            </div>
            <div class="agent-cards-grid">
        `;

        let hasResponses = false;

        agentsToShow.forEach(function (agent) {
            const responses = hourData[agent.id] || 0;
            if (responses > 0) {
                // Get responses for this specific hour
                console.log(`Filtering responses for agent ${agent.full_name} (ID: ${agent.id})`);
                console.log(`Agent has ${agent.response_details ? agent.response_details.length : 0} total responses`);

                const hourResponses = agent.response_details ? agent.response_details.filter(response => {
                    const responseHour = new Date(response.created_at);
                    // Use UTC methods to match PHP's gmdate output
                    const responseHourString = responseHour.getUTCFullYear() + '-' +
                        String(responseHour.getUTCMonth() + 1).padStart(2, '0') + '-' +
                        String(responseHour.getUTCDate()).padStart(2, '0') + ' ' +
                        String(responseHour.getUTCHours()).padStart(2, '0') + ':00';

                    // Debug logging for first few responses
                    if (agent.response_details.indexOf(response) < 3) {
                        console.log('Response hour string:', responseHourString);
                        console.log('Target hour:', hour);
                        console.log('Match:', responseHourString === hour);
                    }

                    return responseHourString === hour;
                }) : [];

                console.log(`Found ${hourResponses.length} responses for hour ${hour} for agent ${agent.full_name}`);

                if (hourResponses.length > 0) {
                    hasResponses = true;
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

        if (!hasResponses) {
            detailsHtml += `
                <div class="no-responses-message">
                    <p>No detailed responses found for this time period. The chart shows aggregated data, but individual response details may not be available for this specific hour.</p>
                </div>
            `;
        }

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
