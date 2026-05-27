<?php

namespace App\Http\Controllers;

use App\Models\MetabotFaq;
use Illuminate\Http\Request;

class MetabotFaqController extends Controller
{
    public function index()
    {
        $faqs = MetabotFaq::orderBy('topic')->get();

        return view('metabot.faqs.index', compact('faqs'));
    }

    public function new()
    {
        return view('metabot.faqs.new');
    }

    public function store(Request $request)
    {
        MetabotFaq::create($this->validateFaq($request));

        return redirect()->route('metabot.faqs.index')->with('success', 'Pregunta creada.');
    }

    public function edit($id)
    {
        $faq = MetabotFaq::findOrFail($id);

        return view('metabot.faqs.edit', compact('faq'));
    }

    public function update(Request $request, $id)
    {
        $faq = MetabotFaq::findOrFail($id);
        $faq->update($this->validateFaq($request));

        return redirect()->route('metabot.faqs.index')->with('success', 'Pregunta actualizada.');
    }

    private function validateFaq(Request $request)
    {
        return $request->validate([
            'topic'               => ['required', 'string', 'max:64'],
            'trigger_description' => ['nullable', 'string', 'max:500'],
            'answer_text'         => ['required', 'string', 'max:1024'],
            'status'              => ['required', 'in:active,inactive'],
        ]);
    }
}
