<div>
    
    @if(isset($error))
        <div class="alert alert-danger">
            {{ $error }}
        </div>
    @elseif($graph)
        
        <x-chartjs-component :chart="$graph" :category="$category" />
    @else
        <div class="alert alert-warning">
            No chart data available
        </div>
    @endif
</div>
