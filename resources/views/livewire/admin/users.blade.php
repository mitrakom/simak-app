@volt
<?php

use function Livewire\Volt\{state, computed};

// State untuk data users dan filter
state([
    'search' => '',
    'users' => [
        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'Admin', 'status' => 'active'],
        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'User', 'status' => 'active'],
        ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'role' => 'User', 'status' => 'inactive'],
        ['id' => 4, 'name' => 'Alice Brown', 'email' => 'alice@example.com', 'role' => 'Manager', 'status' => 'active'],
        ['id' => 5, 'name' => 'Charlie Davis', 'email' => 'charlie@example.com', 'role' => 'User', 'status' => 'active'],
    ],
]);

// Filter users berdasarkan search
$filteredUsers = computed(function () {
    if (empty($this->search)) {
        return $this->users;
    }

    return array_filter($this->users, function ($user) {
        return stripos($user['name'], $this->search) !== false ||
               stripos($user['email'], $this->search) !== false;
    });
});

?>

<x-layouts.admin>
    <x-slot name="header">Users Management</x-slot>

    <div class="space-y-6">
        <!-- Actions Bar -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1 max-w-md">
                <x-input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search users..."
                />
            </div>
            <x-button variant="primary">
                <x-icon name="plus" size="5" class="mr-2" />
                Add User
            </x-button>
        </div>

        <!-- Users Table Card -->
        <x-card :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Role
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->filteredUsers as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="user-{{ $user['id'] }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="size-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0">
                                            {{ strtoupper(substr($user['name'], 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user['name'] }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user['email'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-badge 
                                        :variant="$user['role'] === 'Admin' ? 'danger' : ($user['role'] === 'Manager' ? 'warning' : 'secondary')"
                                    >
                                        {{ $user['role'] }}
                                    </x-badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-badge 
                                        :variant="$user['status'] === 'active' ? 'success' : 'secondary'"
                                        :dot="true"
                                    >
                                        {{ ucfirst($user['status']) }}
                                    </x-badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-button variant="secondary" size="sm" :outline="true">
                                            <x-icon name="pencil" size="4" />
                                        </x-button>
                                        <x-button variant="danger" size="sm" :outline="true">
                                            <x-icon name="trash" size="4" />
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <x-icon name="inbox" size="12" class="text-gray-400" />
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No users found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Info Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <x-card>
                <div class="flex items-center gap-4">
                    <div class="size-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <x-icon name="user-group" size="6" class="text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($users) }}</p>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center gap-4">
                    <div class="size-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <x-icon name="check-circle" size="6" class="text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Active Users</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count(array_filter($users, fn($u) => $u['status'] === 'active')) }}</p>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center gap-4">
                    <div class="size-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <x-icon name="exclamation" size="6" class="text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Inactive Users</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count(array_filter($users, fn($u) => $u['status'] === 'inactive')) }}</p>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.admin>
@endvolt
