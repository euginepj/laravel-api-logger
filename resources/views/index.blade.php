@extends('admin.layouts.app')

@section('title', 'API Request Logs')

@section('content')
<div class="p-4 sm:p-8 space-y-6 max-w-7xl mx-auto" x-data="apiLogViewer()">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-2xl bg-[var(--primary)]/10 text-[var(--primary)] border border-[var(--primary)]/20 shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">API Request Logs</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Inspect real-time API traffic, parameters, payloads, and response latency.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Auto-Refresh Toggle -->
            <button type="button" @click="toggleAutoRefresh()" 
                class="px-3 py-2 rounded-xl border text-xs font-semibold transition-all flex items-center gap-2"
                :class="autoRefresh ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'border-zinc-200 dark:border-white/10 hover:bg-zinc-100 dark:hover:bg-white/5 text-slate-700 dark:text-slate-300'">
                <span class="relative flex h-2 w-2">
                    <span x-show="autoRefresh" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2" :class="autoRefresh ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                </span>
                <span x-text="autoRefresh ? 'Live (Auto-refresh 5s)' : 'Auto-refresh Off'"></span>
            </button>

            <a href="{{ route('admin.api-logs.index') }}" class="px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-white/10 hover:bg-zinc-100 dark:hover:bg-white/5 text-slate-700 dark:text-slate-300 font-semibold text-xs transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </a>
            <form action="{{ route('admin.api-logs.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete all recorded API logs?');">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 border border-red-500/20 font-semibold text-xs transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Clear All
                </button>
            </form>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#151c24] border border-zinc-200 dark:border-white/5 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Recorded</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($metrics['total_requests']) }}</div>
        </div>
        <div class="bg-white dark:bg-[#151c24] border border-zinc-200 dark:border-white/5 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Today's Traffic</span>
            <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ number_format($metrics['today_requests']) }}</div>
        </div>
        <div class="bg-white dark:bg-[#151c24] border border-zinc-200 dark:border-white/5 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Error Rate</span>
            <div class="text-2xl font-black {{ $metrics['error_rate'] > 5 ? 'text-red-500' : 'text-emerald-500' }} mt-1">{{ $metrics['error_rate'] }}%</div>
        </div>
        <div class="bg-white dark:bg-[#151c24] border border-zinc-200 dark:border-white/5 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Avg Latency</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $metrics['avg_duration_ms'] }} <span class="text-xs font-normal text-slate-400">ms</span></div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white dark:bg-[#151c24] border border-zinc-200 dark:border-white/5 rounded-2xl p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.api-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="md:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search path, IP, user ID, or error..." class="w-full bg-slate-50 dark:bg-black/20 border border-zinc-200 dark:border-white/10 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-[var(--primary)] focus:outline-hidden text-slate-800 dark:text-slate-200">
            </div>

            <!-- Method Filter -->
            <div>
                <select name="method" class="w-full bg-slate-50 dark:bg-[#0b0f13] border border-zinc-200 dark:border-white/10 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-[var(--primary)] focus:outline-hidden text-slate-800 dark:text-slate-200">
                    <option value="">All Methods</option>
                    @foreach(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $m)
                        <option value="{{ $m }}" {{ request('method') === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" class="w-full bg-slate-50 dark:bg-[#0b0f13] border border-zinc-200 dark:border-white/10 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-[var(--primary)] focus:outline-hidden text-slate-800 dark:text-slate-200">
                    <option value="">All Statuses</option>
                    <option value="2xx" {{ request('status') === '2xx' ? 'selected' : '' }}>2xx Success</option>
                    <option value="3xx" {{ request('status') === '3xx' ? 'selected' : '' }}>3xx Redirect</option>
                    <option value="4xx" {{ request('status') === '4xx' ? 'selected' : '' }}>4xx Client Error</option>
                    <option value="5xx" {{ request('status') === '5xx' ? 'selected' : '' }}>5xx Server Error</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-[var(--primary)] text-white text-xs font-bold rounded-xl hover:opacity-90 transition-opacity">Filter</button>
                @if(request()->hasAny(['search', 'method', 'status', 'date']))
                    <a href="{{ route('admin.api-logs.index') }}" class="p-2 border border-zinc-200 dark:border-white/10 rounded-xl text-slate-500 hover:text-slate-800 dark:hover:text-white" title="Reset Filters">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-[#151c24] border border-zinc-200 dark:border-white/5 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-white/5 bg-slate-50 dark:bg-white/[0.02] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3">Path</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Device</th>
                        <th class="px-4 py-3">IP Address</th>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-white/5 text-slate-700 dark:text-slate-300">
                    @forelse($logs as $log)
                        @php
                            $statusClass = match(true) {
                                $log->status_code >= 500 => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
                                $log->status_code >= 400 => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                                $log->status_code >= 300 => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
                                default => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                            };

                            $methodClass = match($log->method) {
                                'GET' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
                                'POST' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                'PUT', 'PATCH' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                                'DELETE' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                                default => 'bg-slate-500/10 text-slate-600 border-slate-500/20',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.01] transition-colors cursor-pointer" @click="openModal({{ $log->id }})">
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-md font-mono font-bold text-[11px] border {{ $statusClass }}">
                                    {{ $log->status_code }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-md font-mono font-bold text-[10px] border {{ $methodClass }}">
                                    {{ $log->method }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono font-medium text-slate-900 dark:text-white max-w-xs truncate" title="{{ $log->path }}">
                                {{ $log->path }}
                                @if($log->error_message)
                                    <div class="text-[10px] text-red-500 truncate mt-0.5 font-sans">{{ $log->error_message }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px] {{ $log->duration_ms > 1000 ? 'text-amber-500 font-bold' : '' }}">
                                {{ $log->duration_ms }} ms
                            </td>
                            <td class="px-4 py-3">
                                @if($log->user_id)
                                    <span class="inline-flex items-center gap-1 font-mono font-medium text-slate-800 dark:text-slate-200">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        #{{ $log->user_id }}
                                    </span>
                                @else
                                    <span class="text-slate-400">Guest</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($log->device_name)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-white/5 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-white/5" title="{{ $log->device_name }}">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                        <span class="max-w-[100px] truncate">{{ $log->device_name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[11px]">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-500 dark:text-slate-400">
                                {{ $log->ip_address }}
                            </td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $log->created_at?->format('M d, H:i:s') }}
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                <button type="button" @click="openModal({{ $log->id }})" class="p-1.5 text-slate-400 hover:text-[var(--primary)] transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div class="font-bold text-slate-700 dark:text-slate-300">No API logs recorded yet</div>
                                    <p class="text-xs text-slate-500">Incoming API calls matching /api/* will automatically appear here.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-white/5">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Details Modal -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="modalOpen" x-transition.opacity class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="closeModal()"></div>

            <!-- Modal Card -->
            <div x-show="modalOpen" x-transition class="relative bg-white dark:bg-[#121820] rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-4xl sm:w-full border border-zinc-200 dark:border-white/10 flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-white/5 flex items-center justify-between bg-slate-50 dark:bg-white/[0.02]">
                    <div class="flex items-center gap-3">
                        <span x-text="selectedLog?.status_code" class="px-2.5 py-1 rounded-md font-mono font-bold text-xs" :class="getStatusBadgeClass(selectedLog?.status_code)"></span>
                        <span x-text="selectedLog?.method" class="px-2 py-0.5 rounded-md font-mono font-bold text-xs bg-slate-200 dark:bg-white/10 text-slate-800 dark:text-white"></span>
                        <span x-text="selectedLog?.path" class="font-mono text-xs font-bold text-slate-900 dark:text-white truncate max-w-md"></span>
                    </div>
                    <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Modal Subheader info bar -->
                <div class="px-6 py-3 bg-slate-100/70 dark:bg-white/[0.01] border-b border-zinc-200 dark:border-white/5 grid grid-cols-2 sm:grid-cols-4 gap-3 text-[11px]">
                    <div>
                        <span class="text-slate-400">Duration:</span>
                        <span class="font-mono font-bold ml-1 text-slate-800 dark:text-slate-200" x-text="(selectedLog?.duration_ms || 0) + ' ms'"></span>
                    </div>
                    <div>
                        <span class="text-slate-400">IP:</span>
                        <span class="font-mono ml-1 text-slate-800 dark:text-slate-200" x-text="selectedLog?.ip_address"></span>
                    </div>
                    <div>
                        <span class="text-slate-400">User:</span>
                        <span class="font-mono ml-1 text-slate-800 dark:text-slate-200" x-text="selectedLog?.user_id ? '#' + selectedLog.user_id : 'Guest'"></span>
                    </div>
                    <div>
                        <span class="text-slate-400">Date:</span>
                        <span class="font-mono ml-1 text-slate-800 dark:text-slate-200" x-text="selectedLog?.created_at"></span>
                    </div>
                </div>

                <!-- Tab Selectors -->
                <div class="flex items-center gap-4 px-6 border-b border-zinc-200 dark:border-white/5 text-xs font-bold">
                    <button type="button" @click="activeTab = 'payload'" class="py-3 border-b-2 transition-colors" :class="activeTab === 'payload' ? 'border-[var(--primary)] text-[var(--primary)]' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-white'">
                        Request Payload & Parameters
                    </button>
                    <button type="button" @click="activeTab = 'response'" class="py-3 border-b-2 transition-colors" :class="activeTab === 'response' ? 'border-[var(--primary)] text-[var(--primary)]' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-white'">
                        Response Body
                    </button>
                    <button type="button" @click="activeTab = 'headers'" class="py-3 border-b-2 transition-colors" :class="activeTab === 'headers' ? 'border-[var(--primary)] text-[var(--primary)]' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-white'">
                        Headers
                    </button>
                </div>

                <!-- Tab Contents -->
                <div class="p-6 overflow-y-auto flex-1 font-mono text-xs">
                    <!-- Tab: Payload -->
                    <div x-show="activeTab === 'payload'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Parameters / Body</span>
                            <button type="button" @click="copyToClipboard(selectedLog?.payload)" class="px-2.5 py-1 rounded-md bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 text-[10px] font-bold">
                                Copy Payload
                            </button>
                        </div>
                        <pre class="bg-slate-900 text-emerald-400 p-4 rounded-xl overflow-x-auto max-h-96" x-text="formatJson(selectedLog?.payload)"></pre>

                        <div class="flex items-center justify-between pt-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Query Parameters</span>
                        </div>
                        <pre class="bg-slate-900 text-slate-300 p-4 rounded-xl overflow-x-auto max-h-48" x-text="formatJson(selectedLog?.query_params)"></pre>
                    </div>

                    <!-- Tab: Response -->
                    <div x-show="activeTab === 'response'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Response Payload</span>
                            <button type="button" @click="copyToClipboard(selectedLog?.response_body)" class="px-2.5 py-1 rounded-md bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-600 dark:text-slate-300 text-[10px] font-bold">
                                Copy Response
                            </button>
                        </div>
                        <pre class="bg-slate-900 text-blue-300 p-4 rounded-xl overflow-x-auto max-h-96" x-text="formatJson(selectedLog?.response_body)"></pre>
                    </div>

                    <!-- Tab: Headers -->
                    <div x-show="activeTab === 'headers'" class="space-y-4">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Request Headers</div>
                        <pre class="bg-slate-900 text-slate-300 p-4 rounded-xl overflow-x-auto max-h-64" x-text="formatJson(selectedLog?.headers)"></pre>
                        
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 pt-2">Response Headers</div>
                        <pre class="bg-slate-900 text-slate-300 p-4 rounded-xl overflow-x-auto max-h-64" x-text="formatJson(selectedLog?.response_headers)"></pre>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 border-t border-zinc-200 dark:border-white/5 flex items-center justify-end bg-slate-50 dark:bg-white/[0.02]">
                    <button type="button" @click="closeModal()" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-white/10 text-slate-800 dark:text-white font-bold text-xs hover:opacity-90">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function apiLogViewer() {
    return {
        modalOpen: false,
        activeTab: 'payload',
        selectedLog: null,
        autoRefresh: localStorage.getItem('api-logger-auto-refresh') === 'true',
        refreshTimer: null,

        init() {
            if (this.autoRefresh) {
                this.startTimer();
            }
        },

        toggleAutoRefresh() {
            this.autoRefresh = !this.autoRefresh;
            localStorage.setItem('api-logger-auto-refresh', this.autoRefresh);
            if (this.autoRefresh) {
                this.startTimer();
            } else {
                this.stopTimer();
            }
        },

        startTimer() {
            this.stopTimer();
            this.refreshTimer = setInterval(() => {
                // Only reload if modal is not open to avoid interrupting the admin
                if (!this.modalOpen) {
                    window.location.reload();
                }
            }, 5000);
        },

        stopTimer() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }
        },

        async openModal(id) {
            try {
                const res = await fetch(`{{ route('admin.api-logs.index') }}/${id}`);
                const json = await res.json();
                if (json.success) {
                    this.selectedLog = json.data;
                    this.modalOpen = true;
                }
            } catch (err) {
                console.error('Failed to fetch API log details', err);
            }
        },

        closeModal() {
            this.modalOpen = false;
            this.selectedLog = null;
            this.activeTab = 'payload';
        },

        getStatusBadgeClass(code) {
            if (!code) return '';
            if (code >= 500) return 'bg-red-500/20 text-red-500 border border-red-500/30';
            if (code >= 400) return 'bg-amber-500/20 text-amber-500 border border-amber-500/30';
            if (code >= 300) return 'bg-blue-500/20 text-blue-500 border border-blue-500/30';
            return 'bg-emerald-500/20 text-emerald-500 border border-emerald-500/30';
        },

        formatJson(val) {
            if (!val) return 'null';
            if (typeof val === 'string') {
                try {
                    const parsed = JSON.parse(val);
                    return JSON.stringify(parsed, null, 2);
                } catch (e) {
                    return val;
                }
            }
            return JSON.stringify(val, null, 2);
        },

        copyToClipboard(val) {
            const str = typeof val === 'object' ? JSON.stringify(val, null, 2) : val;
            navigator.clipboard.writeText(str || '');
            alert('Copied to clipboard!');
        }
    }
}
</script>
@endsection
