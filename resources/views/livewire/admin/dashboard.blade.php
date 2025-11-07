@volt
<?php

use function Livewire\Volt\{state, computed};

// State untuk statistik dashboard
state([
    'totalUsers' => 1250,
    'totalOrders' => 3456,
    'totalRevenue' => 125430,
    'totalProducts' => 892,
]);

// Computed property untuk pertumbuhan
$userGrowth = computed(fn() => '+12.5% from last month');
$orderGrowth = computed(fn() => '+8.3% from last month');
$revenueGrowth = computed(fn() => '+15.2% from last month');
$productGrowth = computed(fn() => '+5.1% from last month');

?>

<x-layouts.admin>
    <x-slot name="header">Dashboard</x-slot>

    <div class="space-y-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card
                title="Total Users"
                :value="number_format($totalUsers)"
                :change="$this->userGrowth"
                change-type="positive"
                color="blue"
            >
                <x-slot name="icon">
                    <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card
                title="Total Orders"
                :value="number_format($totalOrders)"
                :change="$this->orderGrowth"
                change-type="positive"
                color="green"
            >
                <x-slot name="icon">
                    <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card
                title="Total Revenue"
                :value="'$' . number_format($totalRevenue)"
                :change="$this->revenueGrowth"
                change-type="positive"
                color="purple"
            >
                <x-slot name="icon">
                    <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card
                title="Total Products"
                :value="number_format($totalProducts)"
                :change="$this->productGrowth"
                change-type="positive"
                color="pink"
            >
                <x-slot name="icon">
                    <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </x-slot>
            </x-stat-card>
        </div>

        <!-- Charts & Recent Activity -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Orders -->
            <x-card title="Recent Orders">
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="size-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="size-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Order #1234</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">John Doe</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">$459.00</p>
                            <x-badge variant="success" size="sm">Completed</x-badge>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="size-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="size-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Order #1233</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Jane Smith</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">$239.00</p>
                            <x-badge variant="warning" size="sm">Processing</x-badge>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="size-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="size-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Order #1232</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Bob Johnson</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">$899.00</p>
                            <x-badge variant="primary" size="sm">Shipped</x-badge>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="size-10 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center">
                                <svg class="size-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Order #1231</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Alice Brown</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">$329.00</p>
                            <x-badge variant="success" size="sm">Completed</x-badge>
                        </div>
                    </div>
                </div>

                <x-slot name="footer">
                    <x-button variant="secondary" size="sm" href="#" class="w-full">
                        View All Orders
                    </x-button>
                </x-slot>
            </x-card>

            <!-- Top Products -->
            <x-card title="Top Products">
                <div class="space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="size-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Product Name 1</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">245 sales</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">$12,450</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="size-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Product Name 2</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">198 sales</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">$9,890</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="size-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Product Name 3</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">156 sales</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">$7,340</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="size-12 bg-gradient-to-br from-pink-500 to-pink-600 rounded-lg flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Product Name 4</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">132 sales</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">$6,120</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="size-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Product Name 5</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">109 sales</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">$5,450</p>
                        </div>
                    </div>
                </div>

                <x-slot name="footer">
                    <x-button variant="secondary" size="sm" href="#" class="w-full">
                        View All Products
                    </x-button>
                </x-slot>
            </x-card>
        </div>

        <!-- Alert Example -->
        <x-alert variant="info" :dismissible="true">
            <strong class="font-semibold">Welcome to your dashboard!</strong>
            <p class="mt-1">Here you can view all your important metrics and recent activities.</p>
        </x-alert>
    </div>
</x-layouts.admin>
@endvolt
