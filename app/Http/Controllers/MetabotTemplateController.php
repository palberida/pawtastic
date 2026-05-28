<?php

namespace App\Http\Controllers;

use App\Models\MetabotTemplate;
use Illuminate\Http\Request;

class MetabotTemplateController extends Controller
{
    public function index()
    {
        $templates = MetabotTemplate::orderBy('label')->orderBy('name')->get();

        return view('metabot.templates.index', compact('templates'));
    }

    public function new()
    {
        return view('metabot.templates.new');
    }

    public function store(Request $request)
    {
        MetabotTemplate::create($this->validateTemplate($request));

        return redirect()->route('metabot.templates.index')->with('success', 'Plantilla registrada.');
    }

    public function edit($id)
    {
        $template = MetabotTemplate::findOrFail($id);

        return view('metabot.templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = MetabotTemplate::findOrFail($id);
        $template->update($this->validateTemplate($request));

        return redirect()->route('metabot.templates.index')->with('success', 'Plantilla actualizada.');
    }

    private function validateTemplate(Request $request)
    {
        return $request->validate([
            'name'         => ['required', 'string', 'max:128'],
            'language'     => ['required', 'string', 'max:16'],
            'label'        => ['nullable', 'string', 'max:150'],
            'body_preview' => ['nullable', 'string', 'max:1024'],
            'status'       => ['required', 'in:active,inactive'],
        ]);
    }
}
