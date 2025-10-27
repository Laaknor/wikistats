
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Select Wiki') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @php
                        $groupedSites = $sites->groupBy('family');
                    @endphp
                    
                    @foreach($groupedSites as $family => $sitesInFamily)
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                                {{ ucfirst($family) }} Wikis
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($sitesInFamily as $site)
                                    <a href="{{ route("site.show", ['site' => $site->hostname]) }}" 
                                       class="block p-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors duration-200">
                                        <div class="font-medium text-gray-900">{{ $site->hostname }}</div>
                                        @if($site->language)
                                            <div class="text-sm text-gray-600">{{ $site->language }}</div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


