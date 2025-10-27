
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Show wiki: '.$site->hostname) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Categories for {{ $site->hostname }}</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($categories as $category)
                            <a href="{{ route('graph.show', ['site' => $site->hostname, 'graph' => $category->name]) }}" 
                               class="block bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 overflow-hidden">
                                <!-- Header -->
                                <div class="p-4 pb-2">
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-medium text-gray-900">{{ $category->display_name }}</h4>
                                        <!-- <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">{{ $category->type }}</span> -->
                                    </div>
                                </div>
                                
                                <!-- Chart fills remaining space -->
                                <div class="px-4 pb-4 flex-1">
                                    <div class="bg-white rounded border border-gray-200 overflow-hidden" style="height: 120px;">
                                        <iframe src="{{ route('graph.small', ['site' => $site->hostname, 'graph' => $category->name]) }}" 
                                                style="width: 100%; height: 100%; border: none; overflow: hidden;"
                                                frameborder="0"
                                                scrolling="no">
                                        </iframe>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    
                    @if(count($categories) === 0)
                        <div class="text-center py-8 text-gray-500">
                            <p>No categories found for this site.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

