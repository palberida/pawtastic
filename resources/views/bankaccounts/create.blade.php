<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bancos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div style="color: red;">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                   
                    <div class="mb-4 w-full">
                        <form action="{{ route('bank-accounts.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <label for="file">Upload CSV:</label>
                            <input type="file" name="file" id="file" required>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Upload</button>
                        </form>
                    </div>
                    <div class="mb-4 w-full">
                    @if(!empty($missingIds))
                        <p>Error creando transacciones</p>
                    @else
                        <p>Transacciones guardadas exitosamente</p>
                    @endif
                    </div>
                    
                   
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


<script>

function sendProducts(id) {
    // Prevent the form from submitting normally
    event.preventDefault();

    // Get the form element
    const form = document.getElementById(`form_${id}`);
    
    // Create a FormData object
    const formData = new FormData(form);

    // Send an AJAX request
    fetch("{{ route('ads.store') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            "Accept": "application/json",
        },
        body: formData
    })
    .then(response => {
        if (response.ok) {
            form.remove();
            return response.json();
        }
        throw new Error("Network response was not ok");
    })
    .then(data => {

    })
    .catch(error => {
        console.error("There was a problem with the fetch operation:", error);
        alert("An error occurred while saving the data.");
    });
}


    
</script>