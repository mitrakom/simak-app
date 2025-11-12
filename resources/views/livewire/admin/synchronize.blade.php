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
            <button 
                wire:click="syncAll" 
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <x-icon name="refresh" size="5" wire:loading.remove wire:target="syncAll" />
                <x-icon name="spinner" size="5" wire:loading wire:target="syncAll" class="animate-spin" />
                <span wire:loading.remove wire:target="syncAll">Sinkronisasi Semua</span>
                <span wire:loading wire:target="syncAll">Memproses...</span>
            </button>
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
    <x-card class="overflow-hidden" :padding="false">
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
                            
                            // Define color classes for proper Tailwind compilation
                            $iconBgClass = match($job['color']) {
                                'blue' => 'bg-blue-100 dark:bg-blue-900/20',
                                'purple' => 'bg-purple-100 dark:bg-purple-900/20',
                                'green' => 'bg-green-100 dark:bg-green-900/20',
                                'indigo' => 'bg-indigo-100 dark:bg-indigo-900/20',
                                'cyan' => 'bg-cyan-100 dark:bg-cyan-900/20',
                                'yellow' => 'bg-yellow-100 dark:bg-yellow-900/20',
                                'emerald' => 'bg-emerald-100 dark:bg-emerald-900/20',
                                'pink' => 'bg-pink-100 dark:bg-pink-900/20',
                                'orange' => 'bg-orange-100 dark:bg-orange-900/20',
                                default => 'bg-gray-100 dark:bg-gray-900/20',
                            };
                            
                            $iconColorClass = match($job['color']) {
                                'blue' => 'text-blue-600 dark:text-blue-400',
                                'purple' => 'text-purple-600 dark:text-purple-400',
                                'green' => 'text-green-600 dark:text-green-400',
                                'indigo' => 'text-indigo-600 dark:text-indigo-400',
                                'cyan' => 'text-cyan-600 dark:text-cyan-400',
                                'yellow' => 'text-yellow-600 dark:text-yellow-400',
                                'emerald' => 'text-emerald-600 dark:text-emerald-400',
                                'pink' => 'text-pink-600 dark:text-pink-400',
                                'orange' => 'text-orange-600 dark:text-orange-400',
                                default => 'text-gray-600 dark:text-gray-400',
                            };
                        @endphp
                        
                        <!-- Main Row -->
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 {{ $iconBgClass }} rounded-lg">
                                        <x-icon :name="$job['icon']" size="5" class="{{ $iconColorClass }}" />
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
                                    
                                    @if($progress && $progress['status'] === 'processing')
                                        <button 
                                            wire:click="cancelJob('{{ $job['id'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="cancelJob('{{ $job['id'] }}')"
                                            wire:confirm="Apakah Anda yakin ingin membatalkan job ini?"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            title="Batalkan job yang sedang berjalan"
                                        >
                                            <x-icon name="x-circle" size="4" />
                                            <span>Batalkan</span>
                                        </button>
                                    @else
                                        @php
                                            $buttonClass = match($job['color']) {
                                                'blue' => 'bg-blue-600 hover:bg-blue-700',
                                                'purple' => 'bg-purple-600 hover:bg-purple-700',
                                                'green' => 'bg-green-600 hover:bg-green-700',
                                                'indigo' => 'bg-indigo-600 hover:bg-indigo-700',
                                                'cyan' => 'bg-cyan-600 hover:bg-cyan-700',
                                                'yellow' => 'bg-yellow-600 hover:bg-yellow-700',
                                                'emerald' => 'bg-emerald-600 hover:bg-emerald-700',
                                                'pink' => 'bg-pink-600 hover:bg-pink-700',
                                                'orange' => 'bg-orange-600 hover:bg-orange-700',
                                                default => 'bg-gray-600 hover:bg-gray-700',
                                            };
                                        @endphp
                                        <button 
                                            wire:click="syncJob('{{ $job['id'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="syncJob('{{ $job['id'] }}')"
                                            class="inline-flex items-center gap-2 px-4 py-2 {{ $buttonClass }} text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <x-icon name="refresh" size="4" wire:loading.remove wire:target="syncJob('{{ $job['id'] }}')" />
                                            <x-icon name="spinner" size="4" wire:loading wire:target="syncJob('{{ $job['id'] }}')" class="animate-spin" />
                                            <span>Sinkron</span>
                                        </button>
                                    @endif
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
                                                        @if($param['required'] ?? false)
                                                            <span class="text-red-600 dark:text-red-400">*</span>
                                                        @endif
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
                                                            placeholder="{{ $param['placeholder'] ?? $param['label'] }}"
                                                            class="@error('jobParameters.'.$job['id'].'.'.$paramKey) border-red-500 @enderror"
                                                        />
                                                    @endif
                                                    @if(isset($param['helper']))
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $param['helper'] }}
                                                        </p>
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
                                                        label="Total Records" 
                                                        :value="number_format($progress['total'])" 
                                                        color="gray" 
                                                    />
                                                    <x-stat-card 
                                                        label="Processed" 
                                                        :value="number_format($progress['processed'])" 
                                                        color="green" 
                                                    />
                                                    <x-stat-card 
                                                        label="Failed" 
                                                        :value="number_format($progress['failed'])" 
                                                        color="red" 
                                                    />
                                                    <x-stat-card 
                                                        label="Progress" 
                                                        :value="number_format($progress['progress'], 1) . '%'" 
                                                        :color="$job['color']" 
                                                    />
                                                </div>

                                                @if($progress['started_at'])
                                                    <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                                        Dimulai: {{ $progress['started_at']->format('d M Y H:i:s') }}
                                                        @if($progress['completed_at'])
                                                            • Selesai: {{ $progress['completed_at']->format('d M Y H:i:s') }}
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
        </x-card>
    </div>

    <style>
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        
        .animate-shimmer {
            animation: shimmer 2s infinite;
        }
    </style>
</div>
