<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemplateRequest;
use App\Models\Template;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        return Template::latest()->paginate();
    }

    public function store(StoreTemplateRequest $request)
    {
        $template = Template::create($request->validated());
        return response()->json($template, 201);
    }

    public function show(Template $template)
    {
        return response()->json($template);
    }

    public function update(Request $request, Template $template)
    {
        $data = $request->validate([
            'name' => 'string|unique:templates,name,' . $template->id,
            'channel' => 'string|in:sms,email,push',
            'content' => 'string',
        ]);

        $template->update($data);
        return response()->json($template);
    }

    public function destroy(Template $template)
    {
        $template->delete();
        return response()->json(null, 204);
    }
}
