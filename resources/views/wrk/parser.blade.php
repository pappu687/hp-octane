@extends('layouts.app')

@section('title', 'Remote Data')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold text-gray-800 mb-4">WRK Performance Parser</h1>
            <p class="text-xl text-gray-600">Analyze your load testing results with beautiful visualizations</p>
        </div>

        <!-- Input Section -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="glass-effect rounded-2xl p-8 card-hover">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Paste Your WRK Output</h2>
                <form id="wrkForm">
                    <textarea id="wrkOutput" name="wrk_output" rows="12"
                        class="w-full p-4 bg-white/10 border-4 border-gray-200 rounded-xl text-gray-800 placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:border-transparent font-mono text-sm resize-none"
                        placeholder="Running 10s test @ http://localhost:8000
  12 threads and 400 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     1.30s   445.11ms   2.00s    60.00%
    Req/Sec     2.18      2.38    10.00     87.10%
  93 requests in 10.08s, 29.70MB read
  Socket errors: connect 157, read 520, write 1, timeout 78
  Non-2xx or 3xx responses: 93
Requests/sec:      9.23
Transfer/sec:      2.95MB"></textarea>
                    <div class="flex justify-end mt-6">
                        <button type="submit"
                            class="px-8 py-3 bg-white text-purple-600 font-semibold rounded-xl hover:bg-white/90 transform hover:scale-105 transition-all duration-200 shadow-lg">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                                Parse & Analyze
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" class="hidden text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gray-600"></div>
            <p class="text-gray-600 mt-4">Analyzing performance data...</p>
        </div>

        <!-- Results Section -->
        <div id="results" class="hidden">
            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="metric-card success rounded-2xl p-6 text-white card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Success Rate</h3>
                            <p id="successRate" class="text-3xl font-bold">--%</p>
                        </div>
                        <div class="text-4xl opacity-80">✓</div>
                    </div>
                </div>
                <div class="metric-card rounded-2xl p-6 text-white card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Avg Latency</h3>
                            <p id="avgLatency" class="text-3xl font-bold">-- ms</p>
                        </div>
                        <div class="text-4xl opacity-80">⚡</div>
                    </div>
                </div>
                <div class="metric-card warning rounded-2xl p-6 text-white card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Requests/sec</h3>
                            <p id="reqPerSec" class="text-3xl font-bold">--</p>
                        </div>
                        <div class="text-4xl opacity-80">🚀</div>
                    </div>
                </div>
                <div class="metric-card error rounded-2xl p-6 text-white card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Total Errors</h3>
                            <p id="totalErrors" class="text-3xl font-bold">--</p>
                        </div>
                        <div class="text-4xl opacity-80">❌</div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Latency Chart -->
                <div class="glass-effect rounded-2xl p-8 card-hover">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Latency Distribution</h3>
                    <div id="latencyChart" style="height: 400px;"></div>
                </div>

                <!-- Error Distribution -->
                <div class="glass-effect rounded-2xl p-8 card-hover">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Error Breakdown</h3>
                    <div id="errorChart" style="height: 400px;"></div>
                </div>
            </div>

            <!-- Performance Trend Chart -->
            <div class="glass-effect rounded-2xl p-8 card-hover mb-8">
                <h3 class="text-2xl font-semibold text-gray-800 mb-6">Performance Overview</h3>
                <div id="performanceChart" style="height: 350px;"></div>
            </div>

            <!-- Detailed Stats -->
            <div class="glass-effect rounded-2xl p-8 card-hover">
                <h3 class="text-2xl font-semibold text-gray-800 mb-6">Detailed Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Test Configuration</h4>
                        <div id="testConfig" class="space-y-2 text-gray-600"></div>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Performance Summary</h4>
                        <div id="perfSummary" class="space-y-2 text-gray-600"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="hidden max-w-4xl mx-auto">
            <div class="bg-red-500/20 border border-red-500/30 rounded-2xl p-6 text-gray-800">
                <h3 class="text-xl font-semibold mb-2">Parsing Error</h3>
                <p id="errorText">Please check your WRK output format and try again.</p>
            </div>
        </div>
    </div>

    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .metric-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .metric-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .metric-card.warning {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .metric-card.error {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
    </style>

    <script>
        let latencyChart = null;
        let errorChart = null;
        let performanceChart = null;

        document.getElementById('wrkForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const wrkOutput = document.getElementById('wrkOutput').value.trim();
            if (!wrkOutput) {
                showError('Please paste your WRK output');
                return;
            }

            showLoading();

            try {
                const response = await fetch('/wrk/parse', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        wrk_output: wrkOutput
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    displayResults(data);
                } else {
                    showError(data.message || 'Failed to parse WRK output');
                }
            } catch (error) {
                showError('Network error occurred');
            }
        });

        function showLoading() {
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('results').classList.add('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
        }

        function showError(message) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('results').classList.add('hidden');
            document.getElementById('errorMessage').classList.remove('hidden');
            document.getElementById('errorText').textContent = message;
        }

        function displayResults(data) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
            document.getElementById('results').classList.remove('hidden');

            // Update key metrics
            document.getElementById('successRate').textContent = (data.calculated?.success_rate || 0) + '%';
            document.getElementById('avgLatency').textContent = Math.round(data.thread_stats?.latency?.avg || 0) + ' ms';
            document.getElementById('reqPerSec').textContent = Math.round(data.rates?.requests_per_sec || 0);
            document.getElementById('totalErrors').textContent = data.errors?.total || 0;

            // Create charts
            createLatencyChart(data);
            createErrorChart(data);
            createPerformanceChart(data);

            // Update detailed stats
            updateTestConfig(data);
            updatePerfSummary(data);
        }

        function createLatencyChart(data) {
            const latency = data.thread_stats?.latency;

            if (latency) {
                // Destroy existing chart if it exists
                if (latencyChart) {
                    latencyChart.destroy();
                }

                const options = {
                    series: [{
                        name: 'Latency (ms)',
                        data: [
                            Math.round(latency.avg || 0),
                            Math.round(latency.max || 0),
                            Math.round(latency.stdev || 0)
                        ]
                    }],
                    chart: {
                        type: 'bar',
                        height: 350,
                        background: 'transparent',
                        toolbar: {
                            show: true,
                            tools: {
                                download: true,
                                selection: false,
                                zoom: false,
                                zoomin: false,
                                zoomout: false,
                                pan: false,
                                reset: false
                            }
                        }
                    },
                    colors: ['#4facfe', '#fa709a', '#43e97b'],
                    plotOptions: {
                        bar: {
                            borderRadius: 8,
                            horizontal: false,
                            columnWidth: '60%',
                            distributed: true,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return val + ' ms';
                        },
                        style: {
                            fontSize: '14px',
                            fontWeight: 'bold'
                        }
                    },
                    xaxis: {
                        categories: ['Average', 'Maximum', 'Std Dev'],
                        labels: {
                            style: {
                                fontSize: '12px',
                                fontWeight: 500
                            }
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'Latency (ms)',
                            style: {
                                fontSize: '14px',
                                fontWeight: 500
                            }
                        },
                        labels: {
                            formatter: function(val) {
                                return val + ' ms';
                            }
                        }
                    },
                    grid: {
                        show: true,
                        borderColor: '#e0e0e0',
                        strokeDashArray: 3
                    },
                    legend: {
                        show: false
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + ' ms';
                            }
                        }
                    }
                };

                latencyChart = new ApexCharts(document.querySelector("#latencyChart"), options);
                latencyChart.render();
            }
        }

        function createErrorChart(data) {
            const errors = data.errors;

            if (errors && errors.total > 0) {
                // Destroy existing chart if it exists
                if (errorChart) {
                    errorChart.destroy();
                }

                const options = {
                    series: [errors.connect || 0, errors.read || 0, errors.write || 0, errors.timeout || 0],
                    chart: {
                        type: 'donut',
                        height: 350,
                        background: 'transparent'
                    },
                    labels: ['Connect Errors', 'Read Errors', 'Write Errors', 'Timeout Errors'],
                    colors: ['#ff6384', '#36a2eb', '#ffcd56', '#4bc0c0'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '60%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true,
                                        fontSize: '16px',
                                        fontWeight: 600
                                    },
                                    value: {
                                        show: true,
                                        fontSize: '20px',
                                        fontWeight: 'bold'
                                    },
                                    total: {
                                        show: true,
                                        label: 'Total Errors',
                                        fontSize: '16px',
                                        fontWeight: 600,
                                        formatter: function(w) {
                                            return errors.total;
                                        }
                                    }
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '14px',
                        fontWeight: 500
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opts) {
                            return Math.round(val) + '%';
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + ' errors';
                            }
                        }
                    }
                };

                errorChart = new ApexCharts(document.querySelector("#errorChart"), options);
                errorChart.render();
            } else {
                document.getElementById('errorChart').innerHTML =
                    '<div class="flex items-center justify-center h-full text-gray-600">' +
                    '<div class="text-center"><div class="text-6xl mb-4">🎉</div><p class="text-xl font-semibold">No errors detected!</p></div>' +
                    '</div>';
            }
        }

        function createPerformanceChart(data) {
            // Destroy existing chart if it exists
            if (performanceChart) {
                performanceChart.destroy();
            }

            const latency = data.thread_stats?.latency;
            const reqStats = data.thread_stats?.req_per_sec;

            if (latency && reqStats) {
                const options = {
                    series: [{
                        name: 'Latency (ms)',
                        type: 'column',
                        data: [
                            Math.round(latency.avg || 0),
                            Math.round(latency.stdev || 0),
                            Math.round(latency.max || 0)
                        ]
                    }, {
                        name: 'Requests/sec',
                        type: 'line',
                        data: [
                            Math.round(reqStats.avg || 0),
                            Math.round(reqStats.stdev || 0),
                            Math.round(reqStats.max || 0)
                        ]
                    }],
                    chart: {
                        height: 300,
                        type: 'line',
                        background: 'transparent',
                        toolbar: {
                            show: true
                        }
                    },
                    colors: ['#4facfe', '#43e97b'],
                    stroke: {
                        width: [0, 4]
                    },
                    dataLabels: {
                        enabled: true,
                        enabledOnSeries: [1]
                    },
                    xaxis: {
                        categories: ['Average', 'Std Dev', 'Maximum'],
                        labels: {
                            style: {
                                fontSize: '12px',
                                fontWeight: 500
                            }
                        }
                    },
                    yaxis: [{
                        title: {
                            text: 'Latency (ms)',
                            style: {
                                color: '#4facfe',
                                fontSize: '14px',
                                fontWeight: 500
                            }
                        },
                        labels: {
                            formatter: function(val) {
                                return val + ' ms';
                            }
                        }
                    }, {
                        opposite: true,
                        title: {
                            text: 'Requests/sec',
                            style: {
                                color: '#43e97b',
                                fontSize: '14px',
                                fontWeight: 500
                            }
                        }
                    }],
                    grid: {
                        show: true,
                        borderColor: '#e0e0e0',
                        strokeDashArray: 3
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'center'
                    }
                };

                performanceChart = new ApexCharts(document.querySelector("#performanceChart"), options);
                performanceChart.render();
            }
        }

        function updateTestConfig(data) {
            const config = data.test_info;
            const html = `
                <div class="flex justify-between py-3 border-b border-gray-200">
                    <span class="font-medium">URL:</span>
                    <span class="font-mono text-sm">${config?.url || 'N/A'}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-gray-200">
                    <span class="font-medium">Duration:</span>
                    <span>${config?.duration || 'N/A'}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-gray-200">
                    <span class="font-medium">Threads:</span>
                    <span>${config?.threads || 'N/A'}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="font-medium">Connections:</span>
                    <span>${config?.connections || 'N/A'}</span>
                </div>
            `;
            document.getElementById('testConfig').innerHTML = html;
        }

        function updatePerfSummary(data) {
            const summary = data.summary;
            const rates = data.rates;
            const html = `
                <div class="flex justify-between py-3 border-b border-gray-200">
                    <span class="font-medium">Total Requests:</span>
                    <span>${summary?.total_requests?.toLocaleString() || 'N/A'}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-gray-200">
                    <span class="font-medium">Data Read:</span>
                    <span>${summary?.data_read?.amount || 'N/A'} ${summary?.data_read?.unit || ''}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-gray-200">
                    <span class="font-medium">Transfer Rate:</span>
                    <span>${rates?.transfer_per_sec?.amount || 'N/A'} ${rates?.transfer_per_sec?.unit || ''}/sec</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="font-medium">Test Duration:</span>
                    <span>${summary?.duration || 'N/A'}s</span>
                </div>
            `;
            document.getElementById('perfSummary').innerHTML = html;
        }
    </script>

@endsection
