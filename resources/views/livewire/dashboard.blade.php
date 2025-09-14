<div class="max-w-6xl mx-auto">
    <!-- Loading Overlay -->
    <div wire:loading.flex class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 items-center justify-center">
        <div class="bg-white rounded-lg p-6 shadow-lg">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <span class="text-gray-700">Processing...</span>
            </div>
        </div>
    </div>

    <!-- Message Alert -->
    @if ($message)
        <div
            class="mb-6 p-4 rounded-md {{ $messageType === 'error' ? 'bg-red-100 border border-red-400 text-red-700' : ($messageType === 'info' ? 'bg-blue-100 border border-blue-400 text-blue-700' : 'bg-green-100 border border-green-400 text-green-700') }}">
            <div class="flex justify-between items-center">
                <span>{{ $message }}</span>
                <button wire:click="clearMessage" class="text-sm underline">Dismiss</button>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">Laravel Valet Manager</h1>
            <div class="flex space-x-3">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $status['running'] ?? false ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $status['running'] ?? false ? 'Running' : 'Stopped' }}
                </span>
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    PHP: {{ $phpVersion ?: 'Unknown' }}
                </span>
            </div>
        </div>

        <div class="mt-4 flex space-x-3">
            <button wire:click="restartValet" wire:loading.attr="disabled"
                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md transition-colors">
                <span wire:loading.remove wire:target="restartValet">Restart Valet</span>
                <span wire:loading wire:target="restartValet">Restarting...</span>
            </button>
            <button wire:click="showLinkSiteModal"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition-colors">
                Link New Site
            </button>
            <button wire:click="refreshData" wire:loading.attr="disabled"
                class="bg-gray-500 hover:bg-gray-600 disabled:bg-gray-300 text-white px-4 py-2 rounded-md transition-colors">
                <span wire:loading.remove wire:target="refreshData">Refresh</span>
                <span wire:loading wire:target="refreshData">Refreshing...</span>
            </button>
        </div>
    </div>

    <!-- Sites List -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Linked Sites</h2>
        </div>

        <div>
            @if (count($sites) > 0)
                <div class="divide-y divide-gray-200">
                    @foreach ($sites as $site)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">{{ $site['name'] }}</h3>
                                    <p class="text-sm text-gray-500">{{ $site['path'] }}</p>
                                    <a href="{{ $site['url'] }}" target="_blank"
                                        class="text-blue-600 hover:text-blue-800 transition-colors">
                                        {{ $site['url'] }}
                                    </a>
                                </div>
                                <div class="flex space-x-2">
                                    <button wire:click="secureSite('{{ $site['name'] }}')" wire:loading.attr="disabled"
                                        class="bg-yellow-500 hover:bg-yellow-600 disabled:bg-yellow-300 text-white px-3 py-1 rounded text-sm transition-colors">
                                        <span wire:loading.remove
                                            wire:target="secureSite('{{ $site['name'] }}')">Secure</span>
                                        <span wire:loading
                                            wire:target="secureSite('{{ $site['name'] }}')">Securing...</span>
                                    </button>
                                    <button wire:click="unlinkSite('{{ $site['name'] }}')" wire:loading.attr="disabled"
                                        wire:confirm="Are you sure you want to unlink {{ $site['name'] }}?"
                                        class="bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white px-3 py-1 rounded text-sm transition-colors">
                                        <span wire:loading.remove
                                            wire:target="unlinkSite('{{ $site['name'] }}')">Unlink</span>
                                        <span wire:loading
                                            wire:target="unlinkSite('{{ $site['name'] }}')">Unlinking...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-8 text-center">
                    <p class="text-gray-500">No sites linked yet. Click "Link New Site" to get started.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Link Site Modal -->
    @if ($showLinkModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 z-40">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Link New Site</h3>
                    <form wire:submit="linkSite">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                            <input type="text" wire:model="siteName"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('siteName') border-red-500 @enderror"
                                placeholder="my-awesome-site">
                            @error('siteName')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Project Path</label>
                            <input type="text" wire:model="sitePath"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('sitePath') border-red-500 @enderror"
                                placeholder="/path/to/your/project">
                            @error('sitePath')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" wire:click="hideLinkSiteModal"
                                class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded-md transition-colors">
                                Cancel
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md transition-colors">
                                <span wire:loading.remove wire:target="linkSite">Link Site</span>
                                <span wire:loading wire:target="linkSite">Linking...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // Auto-hide messages after 5 seconds
    document.addEventListener('livewire:init', () => {
        Livewire.on('hide-message', () => {
            setTimeout(() => {
                Livewire.dispatch('clearMessage');
            }, 5000);
        });
    });
</script>
