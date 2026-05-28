<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Metabot · Registrar plantilla') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($errors->any())
                        <div style="color: red;">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="text-sm text-gray-500 mb-4">
                        Primero crea y aprueba la plantilla en <strong>Meta → WhatsApp Manager → Plantillas de mensajes</strong>.
                        Luego regístrala aquí con el mismo nombre e idioma.
                    </p>

                    <form method="POST" action="{{ route('metabot.templates.store') }}">
                        @csrf

                        <div>
                            <label for="label" class="block text-sm font-medium text-gray-700">Etiqueta (para el chat)</label>
                            <input type="text" name="label" id="label" maxlength="150" value="{{ old('label') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="mt-3">
                            <label for="name" class="block text-sm font-medium text-gray-700">Nombre exacto en Meta</label>
                            <input type="text" name="name" id="name" maxlength="128" value="{{ old('name') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="text-xs text-gray-400 mt-1">Ej: <code>reengage_general</code>. Minúsculas y guiones bajos.</p>
                        </div>

                        <div class="mt-3">
                            <label for="language" class="block text-sm font-medium text-gray-700">Idioma</label>
                            <input type="text" name="language" id="language" maxlength="16" value="{{ old('language', 'es') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="text-xs text-gray-400 mt-1">Código del idioma aprobado, ej: <code>es</code>.</p>
                        </div>

                        <div class="mt-3">
                            <label for="body_preview" class="block text-sm font-medium text-gray-700">Texto del mensaje (solo referencia)</label>
                            <textarea name="body_preview" id="body_preview" rows="3" maxlength="1024" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('body_preview') }}</textarea>
                            <p class="text-xs text-gray-400 mt-1">Se muestra en el chat. El mensaje real que se envía es el aprobado en Meta.</p>
                        </div>

                        <div class="mt-3">
                            <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                            <select id="status" name="status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Activa</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactiva</option>
                            </select>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
