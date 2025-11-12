<div class="space-y-6" wire:poll.5s="refreshAllProgress">
    <!-- Header -->
    <x-card>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Data Synchronization</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Sinkronisasi data dari Feeder PDDIKTI untuk {{ $institusi->nama }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <svg class="size-3 animate-pulse text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <circle cx="10" cy="10" r="8"/>
                        </svg>
                        Auto-refresh setiap 5 detik
                    </span>
                </p>
            </div>
            
            <x-button 
                wire:click="syncAll" 
                wire:loading.attr="disabled"
                variant="primary"
                size="lg"
                class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-lg hover:shadow-xl"
            >
                <x-icon name="refresh" size="5" wire:loading.remove wire:target="syncAll" />
                <x-icon name="spinner" size="5" wire:loading wire:target="syncAll" class="animate-spin" />
                <span wire:loading.remove wire:target="syncAll">Sinkronisasi Semua</span>
                <span wire:loading wire:target="syncAll">Memproses...</span>
            </x-button>
        </div>

        <!-- Messages -->
        @if($successMessage)
            <x-alert type="success" class="mt-4">
                {{ $successMessage }}
            </x-alert>
        @endif

        @if($errorMessage)
            <x-alert type="error" class="mt-4">
                {{ $errorMessage }}
            </x-alert>
        @endif
    </x-card>

    <!-- Statistics Cards -->
    @php
        $stats = $this->getOverallStats();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-sync.stat-card 
            label="Total Jobs" 
            :value="$stats['total_jobs']" 
            icon="clipboard" 
            color="gray" 
        />
        
        <x-sync.stat-card 
            label="Sedang Berjalan" 
            :value="$stats['running']" 
            icon="refresh" 
            color="blue" 
            :animate="$stats['running'] > 0" 
        />
        
        <x-sync.stat-card 
            label="Selesai" 
            :value="$stats['completed']" 
            icon="check-circle" 
            color="green" 
        />
        
        <x-sync.stat-card 
            label="Pending" 
            :value="$stats['pending']" 
            icon="clock" 
            color="yellow" 
        />
    </div>

    <!-- Jobs Table -->
    <x-card class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Job Name
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Category
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Progress
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($jobs as $job)
                        @php
                            $progress = $this->getJobProgress($job['id']);
                            $isExpanded = in_array($job['id'], $expandedRows);
                        @endphp
                        
                        <!-- Main Row -->
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-{{ $job['color'] }}-100 dark:bg-{{ $job['color'] }}-900/20 rounded-lg">
                                        <x-icon :name="$job['icon']" size="5" class="text-{{ $job['color'] }}-600 dark:text-{{ $job['color'] }}-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $job['name'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $job['description'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge color="gray">{{ ucfirst($job['category']) }}</x-badge>
                            </td>
                            <td class="px-6 py-4">
                                <x-sync.progress-bar :progress="$progress" :color="$job['color']" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($progress)
                                    <x-sync.status-badge :status="$progress['status']" />
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($job['has_parameters'])
                                        <button 
                                            wire:click="toggleRow('{{ $job['id'] }}')"
                                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                            title="Toggle parameters"
                                        >
                                            <x-icon name="chevron-down" size="5" :class="$isExpanded ? 'rotate-180' : ''" class="transition-transform" />
                                        </button>
                                    @endif
                                    
                                    <x-button 
                                        wire:click="syncJob('{{ $job['id'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="syncJob('{{ $job['id'] }}')"
                                        variant="{{ $job['color'] === 'blue' ? 'primary' : 'secondary' }}"
                                        size="sm"
                                    >
                                        <x-icon name="refresh" size="4" wire:loading.remove wire:target="syncJob('{{ $job['id'] }}')" />
                                        <x-icon name="spinner" size="4" wire:loading wire:target="syncJob('{{ $job['id'] }}')" class="animate-spin" />
                                        Sinkron
                                    </x-button>
                                </div>
                            </td>
                        </tr>

                        <!-- Expanded Row - Parameters & Details -->
                        @if($isExpanded && $job['has_parameters'])
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <td colspan="5" class="px-6 py-4">
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            <x-icon name="cog" size="5" />
                                            Parameter Konfigurasi
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            @foreach($job['parameters'] as $paramKey => $param)
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                        {{ $param['label'] }}
                                                    </label>
                                                    @if($param['type'] === 'boolean')
                                                        <label class="inline-flex items-center cursor-pointer">
                                                            <input 
                                                                type="checkbox" 
                                                                wire:model="jobParameters.{{ $job['id'] }}.{{ $paramKey }}"
                                                                class="sr-only peer"
                                                            >
                                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-{{ $job['color'] }}-300 dark:peer-focus:ring-{{ $job['color'] }}-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-{{ $job['color'] }}-600"></div>
                                                        </label>
                                                    @else
                                                        <x-input 
                                                            type="text"
                                                            wire:model="jobParameters.{{ $job['id'] }}.{{ $paramKey }}"
                                                            :placeholder="$param['label']"
                                                        />
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($progress)
                                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                                                    <x-icon name="chart" size="5" />
                                                    Detail Progress Terakhir
                                                </div>

                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                                    <x-stat-card 
                                                        title="Total Records" 
                                                        :value="number_format($progress['total'])" 
                                                        color="gray" 
                                                    />
                                                    <x-stat-card 
                                                        title="Processed" 
                                                        :value="number_format($progress['processed'])" 
                                                        color="green" 
                                                    />
                                                    <x-stat-card 
                                                        title="Failed" 
                                                        :value="number_format($progress['failed'])" 
                                                        color="red" 
                                                    />
                                                    <x-stat-card 
                                                        title="Progress" 
                                                        :value="number_format($progress['progress'], 1) . '%'" 
                                                        :color="$job['color']" 
                                                    />
                                                </div>

                                                @if($progress['started_at'])
                                                    <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                                        <span class="inline-flex items-center gap-1">
                                                            <x-icon name="clock" size="3" />
                                                            Dimulai: {{ $progress['started_at']->format('d M Y H:i:s') }}
                                                        </span>
                                                        @if($progress['completed_at'])
                                                            • <span class="inline-flex items-center gap-1">
                                                                <x-icon name="check" size="3" />
                                                                Selesai: {{ $progress['completed_at']->format('d M Y H:i:s') }}
                                                            </span>
                                                            • Durasi: {{ $progress['started_at']->diffForHumans($progress['completed_at'], true) }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Shimmer Animation -->
    <style>
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .animate-shimmer { animation: shimmer 2s infinite; }
    </style>
</div>
