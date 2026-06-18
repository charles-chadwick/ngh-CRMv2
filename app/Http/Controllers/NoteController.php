<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Note;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class NoteController extends Controller
{
    /**
     * Whitelist of models that may receive notes, keyed by the
     * `model_type` value accepted from the client.
     *
     * @var array<string, class-string<Model>>
     */
    protected array $models = [
        'customer' => Customer::class,
        'company' => Company::class,
    ];

    /**
     * Store a note against the given notable model.
     */
    public function store(NoteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $model = $this->resolveModel($validated['model_type'], $validated['model_id']);

        $model->notes()->create($request->safe()->only(['type', 'title', 'content']));

        return back();
    }

    /**
     * Update an existing note.
     */
    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        $note->update($request->safe()->only(['type', 'title', 'content']));

        return back();
    }

    /**
     * Remove a note.
     */
    public function destroy(Note $note): RedirectResponse
    {
        $note->delete();

        return back();
    }

    /**
     * Resolve the target notable model from the whitelist.
     */
    protected function resolveModel(string $type, int|string $id): Model
    {
        return $this->models[$type]::findOrFail($id);
    }
}
