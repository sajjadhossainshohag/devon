@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">Laravel Valet Manager</h1>
                <div class="flex space-x-3">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $status['running'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $status['running'] ? 'Running' : 'Stopped' }}
                    </span>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        PHP: {{ $phpVersion }}
                    </span>
                </div>
            </div>

            <div class="mt-4 flex space-x-3">
                <button onclick="restartValet()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                    Restart Valet
                </button>
                <button onclick="showLinkModal()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">
                    Link New Site
                </button>
            </div>
        </div>

        <!-- Sites List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Linked Sites</h2>
            </div>

            <div id="sites-container">
                @if (count($sites) > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach ($sites as $site)
                            <div class="px-6 py-4 site-row">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">{{ $site['name'] }}</h3>
                                        <p class="text-sm text-gray-500">{{ $site['path'] }}</p>
                                        <a href="{{ $site['url'] }}" target="_blank"
                                            class="text-blue-600 hover:text-blue-800">
                                            {{ $site['url'] }}
                                        </a>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="secureSite('{{ $site['name'] }}')"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                                            Secure
                                        </button>
                                        <button onclick="unlinkSite('{{ $site['name'] }}')"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                            Unlink
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
    </div>

    <!-- Link Site Modal -->
    <div id="linkModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Link New Site</h3>
                <form id="linkSiteForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Site Name</label>
                        <input type="text" id="siteName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Project Path</label>
                        <input type="text" id="sitePath" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideLinkModal()"
                            class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded-md">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                            Link Site
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // JavaScript functions for handling UI interactions
        function showLinkModal() {
            document.getElementById('linkModal').classList.remove('hidden');
        }

        function hideLinkModal() {
            document.getElementById('linkModal').classList.add('hidden');
        }

        async function linkSite(name, path) {
            try {
                const response = await fetch('/api/sites/link', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        name,
                        path
                    })
                });

                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert('Failed to link site: ' + result.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        async function unlinkSite(name) {
            if (!confirm(`Are you sure you want to unlink ${name}?`)) return;

            try {
                const response = await fetch('/api/sites/unlink', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        name
                    })
                });

                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert('Failed to unlink site: ' + result.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        async function secureSite(name) {
            try {
                const response = await fetch('/api/sites/secure', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        name
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert(`${name} is now secured with SSL`);
                } else {
                    alert('Failed to secure site: ' + result.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        async function restartValet() {
            try {
                const response = await fetch('/api/valet/restart', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (result.success) {
                    alert('Valet restarted successfully');
                    location.reload();
                } else {
                    alert('Failed to restart Valet: ' + result.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        // Form submission handler
        document.getElementById('linkSiteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('siteName').value;
            const path = document.getElementById('sitePath').value;

            if (name && path) {
                linkSite(name, path);
                hideLinkModal();
            }
        });
    </script>
@endsection
