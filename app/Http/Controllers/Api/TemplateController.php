<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemplateRequest;
use App\Models\Template;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TemplateController extends Controller
{
    #[OA\Get(
        path: "/templates",
        summary: "List all templates",
        tags: ["Templates"],
        responses: [
            new OA\Response(response: 200, description: "List of templates")
        ]
    )]
    public function index()
    {
        return Template::latest()->paginate();
    }

    #[OA\Post(
        path: "/templates",
        summary: "Create a new template",
        tags: ["Templates"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "channel", "content"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "welcome"),
                    new OA\Property(property: "channel", type: "string", enum: ["sms", "email", "push"]),
                    new OA\Property(property: "content", type: "string", example: "Hello {{name}}!"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Template created"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(StoreTemplateRequest $request)
    {
        $template = Template::create($request->validated());
        return response()->json($template, 201);
    }

    #[OA\Get(
        path: "/templates/{id}",
        summary: "Get template details",
        tags: ["Templates"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Template details"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function show(Template $template)
    {
        return response()->json($template);
    }

    #[OA\Put(
        path: "/templates/{id}",
        summary: "Update an existing template",
        tags: ["Templates"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "channel", type: "string", enum: ["sms", "email", "push"]),
                    new OA\Property(property: "content", type: "string"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Template updated"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
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
