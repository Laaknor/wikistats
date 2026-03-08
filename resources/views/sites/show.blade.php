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
                    <h3 class="text-lg font-semibold mb-4">Categories & charts for {{ $site->hostname }}</h3>

                    @if(count($tabs) === 0)
                        <div class="text-center py-8 text-gray-500">
                            <p>No categories or charts found for this site.</p>
                        </div>
                    @else
                        <div id="site-tabs" class="mt-4">
                            <div class="flex border-b border-gray-200 gap-1" role="tablist">
                                @foreach($tabs as $tabKey)
                                    @php
                                        $isActive = $tabKey === $activeTab;
                                        $tabUrl = request()->url() . '?tab=' . urlencode($tabKey);
                                    @endphp
                                    <a href="{{ $tabUrl }}"
                                       class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors {{ $isActive ? 'bg-gray-100 border-b-2 border-gray-900' : 'hover:bg-gray-50' }}"
                                       role="tab"
                                       aria-selected="{{ $isActive ? 'true' : 'false' }}">
                                        {{ $groupOrder[$tabKey] }}
                                    </a>
                                @endforeach
                            </div>

                            @foreach($tabs as $tabKey)
                                @php
                                    $groupCategories = $categoriesByGroup->get($tabKey, collect());
                                    $groupCharts = $chartsByGroup->get($tabKey, collect());
                                    $panelActive = $tabKey === $activeTab;
                                @endphp
                                <div id="panel-{{ $tabKey }}" class="tab-panel py-6 {{ $panelActive ? '' : 'hidden' }}" role="tabpanel" aria-hidden="{{ $panelActive ? 'false' : 'true' }}">
                                    {{-- Combined charts (multiple series in one graph) --}}
                                    @if($groupCharts->isNotEmpty())
                                        <h4 class="text-md font-medium text-gray-700 mb-3">Combined charts</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                            @foreach($groupCharts as $chart)
                                                <a href="{{ route('chart.show', ['site' => $site->hostname, 'chartSlug' => $chart->slug]) }}"
                                                   class="block bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 overflow-hidden">
                                                    <div class="p-4 pb-2">
                                                        <h4 class="font-medium text-gray-900">{{ $chart->name }}</h4>
                                                    </div>
                                                    <div class="px-4 pb-4 flex-1">
                                                        <div class="bg-white rounded border border-gray-200 overflow-hidden" style="height: 120px;">
                                                            <iframe src="{{ route('chart.small', ['site' => $site->hostname, 'chartSlug' => $chart->slug]) }}"
                                                                    style="width: 100%; height: 100%; border: none; overflow: hidden;"
                                                                    frameborder="0"
                                                                    scrolling="no">
                                                            </iframe>
                                                        </div>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Single-category cards --}}
                                    @if($groupCategories->isNotEmpty())
                                        <h4 class="text-md font-medium text-gray-700 mb-3">{{ $groupCharts->isNotEmpty() ? 'Single category counts' : 'Category counts' }}</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                            @foreach($groupCategories as $category)
                                                <a href="{{ route('graph.show', ['site' => $site->hostname, 'graph' => urlencode($category->name)]) }}"
                                                   class="block bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 overflow-hidden">
                                                    <div class="p-4 pb-2">
                                                        <h4 class="font-medium text-gray-900">{{ $category->display_name ?? $category->name }}</h4>
                                                    </div>
                                                    <div class="px-4 pb-4 flex-1">
                                                        <div class="bg-white rounded border border-gray-200 overflow-hidden" style="height: 120px;">
                                                            <iframe src="{{ route('graph.small', ['site' => $site->hostname, 'graph' => urlencode($category->name)]) }}"
                                                                    style="width: 100%; height: 100%; border: none; overflow: hidden;"
                                                                    frameborder="0"
                                                                    scrolling="no">
                                                            </iframe>
                                                        </div>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
