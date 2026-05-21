<!-- resources/views/records/show.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('View Record') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div>Name: {{ $order->name }}</div>
                    <div>Description: {{ $order->description }}</div>
                    <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded mt-4">Back to List</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
