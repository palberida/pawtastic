<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Metabot · Editar pregunta') }}
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

                    <form method="POST" action="{{ route('metabot.faqs.update', ['id' => $faq->id]) }}">
                        @csrf

                        <div>
                            <label for="topic" class="block text-sm font-medium text-gray-700">Tema</label>
                            <input list="faq_topics" type="text" name="topic" id="topic" maxlength="64" value="{{ old('topic', $faq->topic) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <datalist id="faq_topics">
                                <option value="shipping"></option>
                                <option value="payment"></option>
                                <option value="delivery_time"></option>
                                <option value="returns"></option>
                            </datalist>
                        </div>

                        <div class="mt-3">
                            <label for="trigger_description" class="block text-sm font-medium text-gray-700">¿Qué preguntas cubre?</label>
                            <textarea name="trigger_description" id="trigger_description" rows="2" maxlength="500" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('trigger_description', $faq->trigger_description) }}</textarea>
                            <p class="text-xs text-gray-400 mt-1">Ayuda al bot a identificar cuándo aplica esta respuesta.</p>
                        </div>

                        <div class="mt-3">
                            <label for="answer_text" class="block text-sm font-medium text-gray-700">Respuesta (se envía tal cual)</label>
                            <textarea name="answer_text" id="answer_text" rows="4" maxlength="1024" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('answer_text', $faq->answer_text) }}</textarea>
                        </div>

                        <div class="mt-3">
                            <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                            <select id="status" name="status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="active" {{ old('status', $faq->status) === 'active' ? 'selected' : '' }}>Activa</option>
                                <option value="inactive" {{ old('status', $faq->status) === 'inactive' ? 'selected' : '' }}>Inactiva</option>
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
