<!DOCTYPE html>
<html>
<head>
    <style>
        body { margin: 0; padding: 0; overflow: hidden; font-family: system-ui, -apple-system, sans-serif; }
        .chart-container { width: 100%; height: 100vh; overflow: hidden; display: flex; flex-direction: column; }
        .chart-content { flex: 1; overflow: hidden; }
        .chart-footer { flex-shrink: 0; text-align: center; font-size: 8px; color: #6b7280; padding: 2px; }
    </style>
</head>
<body>
    <div class="chart-container">
        <div class="chart-content">
            @if(isset($error))
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background-color: #f9fafb; color: #6b7280; font-size: 12px;">
                    {{ $error }}
                </div>
            @elseif($chart)
                <div style="width: 100%; height: 100%; overflow: hidden;">
                    <x-chartjs-component :chart="$chart" />
                </div>
            @else
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background-color: #f9fafb; color: #6b7280; font-size: 12px;">
                    No chart data available
                </div>
            @endif
        </div>
        <div class="chart-footer">
            https://wikistats.laaknor.no
        </div>
    </div>
</body>
</html>
