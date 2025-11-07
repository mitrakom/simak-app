@volt
<?php

use function Livewire\Volt\{state, computed};

// Data untuk chart - bisa dari database atau API
state([
    'period' => 'week', // week, month, year
]);

// Computed data untuk Sales Chart
$salesData = computed(function () {
    // Simulasi data penjualan - dalam praktik nyata, ambil dari database
    return match($this->period) {
        'week' => [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'data' => [12000, 19000, 15000, 25000, 22000, 30000, 28000],
        ],
        'month' => [
            'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            'data' => [85000, 92000, 78000, 105000],
        ],
        'year' => [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'data' => [320000, 280000, 350000, 400000, 380000, 420000, 450000, 480000, 460000, 500000, 520000, 550000],
        ],
    };
});

// Computed data untuk Category Distribution
$categoryData = computed(fn () => [
    'labels' => ['Electronics', 'Clothing', 'Food & Beverages', 'Books', 'Home & Garden'],
    'data' => [35, 25, 20, 12, 8],
]);

// Computed data untuk Revenue vs Expenses
$revenueExpenseData = computed(fn () => [
    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    'revenue' => [450000, 520000, 480000, 600000, 580000, 650000],
    'expense' => [280000, 310000, 290000, 350000, 340000, 380000],
]);

// Computed data untuk User Growth
$userGrowthData = computed(fn () => [
    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    'data' => [120, 145, 168, 195, 230, 275, 310, 358, 405, 460, 520, 595],
]);

?>

<x-layouts.admin>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Analytics Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Visual data insights with Chart.js integration</p>
            </div>
            
            <!-- Period Filter -->
            <div class="flex gap-2">
                <button 
                    wire:click="$set('period', 'week')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $period === 'week' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    Week
                </button>
                <button 
                    wire:click="$set('period', 'month')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $period === 'month' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    Month
                </button>
                <button 
                    wire:click="$set('period', 'year')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $period === 'year' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    Year
                </button>
            </div>
        </div>

        <!-- Sales Trend Chart - Line Chart -->
        <x-card title="Sales Trend" padding="p-6">
            <div class="relative h-80">
                <canvas 
                    id="salesChart"
                    x-data="{
                        chart: null,
                        darkMode: false,
                        
                        initChart() {
                            this.darkMode = document.documentElement.classList.contains('dark');
                            
                            const ctx = this.$el;
                            this.chart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: {{ Js::from($this->salesData['labels']) }},
                                    datasets: [{
                                        label: 'Sales (IDR)',
                                        data: {{ Js::from($this->salesData['data']) }},
                                        borderColor: 'rgb(59, 130, 246)',
                                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.4,
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            labels: {
                                                color: this.darkMode ? '#e5e7eb' : '#374151'
                                            }
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return 'Sales: IDR ' + context.parsed.y.toLocaleString('id-ID');
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                color: this.darkMode ? '#9ca3af' : '#6b7280',
                                                callback: function(value) {
                                                    return 'IDR ' + (value / 1000) + 'K';
                                                }
                                            },
                                            grid: {
                                                color: this.darkMode ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)'
                                            }
                                        },
                                        x: {
                                            ticks: {
                                                color: this.darkMode ? '#9ca3af' : '#6b7280'
                                            },
                                            grid: {
                                                color: this.darkMode ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)'
                                            }
                                        }
                                    }
                                }
                            });
                        },
                        
                        updateChart() {
                            if (this.chart) {
                                this.chart.data.labels = {{ Js::from($this->salesData['labels']) }};
                                this.chart.data.datasets[0].data = {{ Js::from($this->salesData['data']) }};
                                this.chart.update();
                            }
                        }
                    }"
                    x-init="
                        import('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js').then(() => {
                            initChart();
                        });
                    "
                    wire:key="sales-chart-{{ $period }}"
                    @data-updated.window="updateChart()"
                ></canvas>
            </div>
        </x-card>

        <!-- Two Column Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue vs Expenses - Bar Chart -->
            <x-card title="Revenue vs Expenses" padding="p-6">
                <div class="relative h-80">
                    <canvas 
                        id="revenueExpenseChart"
                        x-data="{
                            chart: null,
                            darkMode: false,
                            
                            initChart() {
                                this.darkMode = document.documentElement.classList.contains('dark');
                                
                                const ctx = this.$el;
                                this.chart = new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: {{ Js::from($this->revenueExpenseData['labels']) }},
                                        datasets: [
                                            {
                                                label: 'Revenue',
                                                data: {{ Js::from($this->revenueExpenseData['revenue']) }},
                                                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                                                borderColor: 'rgb(34, 197, 94)',
                                                borderWidth: 1
                                            },
                                            {
                                                label: 'Expenses',
                                                data: {{ Js::from($this->revenueExpenseData['expense']) }},
                                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                                borderColor: 'rgb(239, 68, 68)',
                                                borderWidth: 1
                                            }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                labels: {
                                                    color: this.darkMode ? '#e5e7eb' : '#374151'
                                                }
                                            },
                                            tooltip: {
                                                callbacks: {
                                                    label: function(context) {
                                                        return context.dataset.label + ': IDR ' + context.parsed.y.toLocaleString('id-ID');
                                                    }
                                                }
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                ticks: {
                                                    color: this.darkMode ? '#9ca3af' : '#6b7280',
                                                    callback: function(value) {
                                                        return 'IDR ' + (value / 1000) + 'K';
                                                    }
                                                },
                                                grid: {
                                                    color: this.darkMode ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)'
                                                }
                                            },
                                            x: {
                                                ticks: {
                                                    color: this.darkMode ? '#9ca3af' : '#6b7280'
                                                },
                                                grid: {
                                                    display: false
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }"
                        x-init="
                            import('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js').then(() => {
                                initChart();
                            });
                        "
                    ></canvas>
                </div>
            </x-card>

            <!-- Category Distribution - Doughnut Chart -->
            <x-card title="Sales by Category" padding="p-6">
                <div class="relative h-80">
                    <canvas 
                        id="categoryChart"
                        x-data="{
                            chart: null,
                            darkMode: false,
                            
                            initChart() {
                                this.darkMode = document.documentElement.classList.contains('dark');
                                
                                const ctx = this.$el;
                                this.chart = new Chart(ctx, {
                                    type: 'doughnut',
                                    data: {
                                        labels: {{ Js::from($this->categoryData['labels']) }},
                                        datasets: [{
                                            data: {{ Js::from($this->categoryData['data']) }},
                                            backgroundColor: [
                                                'rgba(59, 130, 246, 0.8)',
                                                'rgba(16, 185, 129, 0.8)',
                                                'rgba(245, 158, 11, 0.8)',
                                                'rgba(139, 92, 246, 0.8)',
                                                'rgba(236, 72, 153, 0.8)',
                                            ],
                                            borderColor: [
                                                'rgb(59, 130, 246)',
                                                'rgb(16, 185, 129)',
                                                'rgb(245, 158, 11)',
                                                'rgb(139, 92, 246)',
                                                'rgb(236, 72, 153)',
                                            ],
                                            borderWidth: 2
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                position: 'right',
                                                labels: {
                                                    color: this.darkMode ? '#e5e7eb' : '#374151',
                                                    padding: 15,
                                                    font: {
                                                        size: 12
                                                    }
                                                }
                                            },
                                            tooltip: {
                                                callbacks: {
                                                    label: function(context) {
                                                        return context.label + ': ' + context.parsed + '%';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }"
                        x-init="
                            import('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js').then(() => {
                                initChart();
                            });
                        "
                    ></canvas>
                </div>
            </x-card>
        </div>

        <!-- User Growth - Area Chart -->
        <x-card title="User Growth" padding="p-6">
            <div class="relative h-80">
                <canvas 
                    id="userGrowthChart"
                    x-data="{
                        chart: null,
                        darkMode: false,
                        
                        initChart() {
                            this.darkMode = document.documentElement.classList.contains('dark');
                            
                            const ctx = this.$el;
                            this.chart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: {{ Js::from($this->userGrowthData['labels']) }},
                                    datasets: [{
                                        label: 'Total Users',
                                        data: {{ Js::from($this->userGrowthData['data']) }},
                                        borderColor: 'rgb(139, 92, 246)',
                                        backgroundColor: 'rgba(139, 92, 246, 0.2)',
                                        borderWidth: 3,
                                        fill: true,
                                        tension: 0.4,
                                        pointRadius: 5,
                                        pointHoverRadius: 7,
                                        pointBackgroundColor: 'rgb(139, 92, 246)',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            labels: {
                                                color: this.darkMode ? '#e5e7eb' : '#374151'
                                            }
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return 'Users: ' + context.parsed.y.toLocaleString('id-ID');
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                color: this.darkMode ? '#9ca3af' : '#6b7280'
                                            },
                                            grid: {
                                                color: this.darkMode ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)'
                                            }
                                        },
                                        x: {
                                            ticks: {
                                                color: this.darkMode ? '#9ca3af' : '#6b7280'
                                            },
                                            grid: {
                                                color: this.darkMode ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)'
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    }"
                    x-init="
                        import('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js').then(() => {
                            initChart();
                        });
                    "
                ></canvas>
            </div>
        </x-card>

        <!-- Info Box -->
        <x-alert variant="info">
            <div class="flex items-start gap-3">
                <svg class="size-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1">
                    <h4 class="font-semibold mb-1">Chart.js Integration with Livewire</h4>
                    <p class="text-sm">Charts menggunakan Alpine.js untuk inisialisasi dan Chart.js CDN untuk rendering. Data di-inject dari Livewire menggunakan <code class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/50 rounded text-xs">Js::from()</code> untuk konversi PHP → JavaScript yang aman. Period filter akan memuat ulang chart dengan data yang berbeda.</p>
                </div>
            </div>
        </x-alert>
    </div>
</x-layouts.admin>
@endvolt
