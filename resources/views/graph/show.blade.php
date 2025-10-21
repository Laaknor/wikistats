
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(isset($error))
                        <div class="alert alert-danger">
                            {{ $error }}
                        </div>
                    @elseif($graph)
                        <div style="width: 75%;">
                            <x-chartjs-component :chart="$graph" />
                        </div>
                    @else
                        <div class="alert alert-warning">
                            No chart data available
                        </div>
                    @endif
                    
                    <!-- Footer -->
                    <div class="mt-4 text-center text-sm text-gray-500">
                        https://maintenalyzer.laaknor.no
                    </div>
                </div>
            </div>
        </div>
    </div>


</x-app-layout>




