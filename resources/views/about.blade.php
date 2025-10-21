<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('About Maintenalyzer') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="prose max-w-none">
                        <h1 class="text-3xl font-bold text-gray-900 mb-6">About WikiStats</h1>
                        
                        <div class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-4">What is WikiStats?</h2>
                            <p class="text-lg text-gray-700 leading-relaxed mb-4">
                            Maintenalyzer is a comprehensive analytics platform designed to track and visualize category growth across Wikipedia and other Wikimedia Foundation wikis. The platform provides insights into how different categories expand over time, helping researchers, editors, and enthusiasts understand the evolution of knowledge organization on Wikimedia projects.
                            </p>
                        </div>

                        <div class="mb-8">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-4">How Data is Gathered</h2>

                            <h3 class="text-xl font-medium text-gray-800 mb-3">1. Gathering information from WikiData</h3>
                            <p class="text-gray-700 mb-4">
                                From WikiData, we gather sitelinks to Wikipedia and other Wikimedia wikis, about which categories to track. This list is manually curated, and if you want us to track more, you should contact <a href="https://meta.wikimedia.org/wiki/User_talk:Laaknor" class="text-blue-600 hover:text-blue-800 underline">User talk:Laaknor</a> on metawiki with the item ID (Q-number) of the category your wiki is using to categorize a templates usage.
                            </p>

                            <h3 class="text-xl font-medium text-gray-800 mb-3">2. Gathering weekly data from the wikis</h3>
                            <p class="text-gray-700 mb-4">
                                Every wiki category found from WikiData is checked weekly for the count of pages in the category (or if there are many subcategories, the count of pages in the subcategories). This is checked against the MediaWiki API.
                            </p>
                            
                            <h3 class="text-xl font-medium text-gray-800 mb-3">3. Archive.org Integration for historical data</h3>
                            <p class="text-gray-700 mb-4">
                                Maintenalyzer leverages the Internet Archive's comprehensive collection of Wikimedia database dumps. We systematically search and download historical database exports from <a href="https://archive.org/details/wikimediadownloads" class="text-blue-600 hover:text-blue-800 underline">archive.org/details/wikimediadownloads</a>, and other sources for historical data, which contains complete snapshots of Wikipedia and other Wikimedia wikis at different points in time.
                            </p>

                           
                            

                        </div>

                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
