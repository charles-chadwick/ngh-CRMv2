<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Note;
use App\Models\User;
use App\Notifications\UserMentionedInNote;
use App\Services\NoteContentSanitizer;
use App\Services\NoteMentionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

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
        'lead' => Lead::class,
    ];

    /**
     * Store a note against the given notable model.
     */
    public function store(NoteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $model = $this->resolveModel($validated['model_type'], $validated['model_id']);

        $note = $model->notes()->create($this->noteAttributes($request));

        $this->notifyMentions($note);

        return back();
    }

    /**
     * Update an existing note.
     */
    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        $note->update($this->noteAttributes($request));

        $this->notifyMentions($note);

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
     * Pull the writable note attributes from the request with sanitized HTML content.
     *
     * @return array<string, mixed>
     */
    protected function noteAttributes(NoteRequest $request): array
    {
        $attributes = $request->safe()->only(['type', 'title', 'content']);
        $attributes['content'] = app(NoteContentSanitizer::class)->clean($attributes['content']);

        return $attributes;
    }

    /**
     * Sync mentions for the note and notify any newly mentioned users.
     */
    protected function notifyMentions(Note $note): void
    {
        $author = auth()->user();
        $authoring_user = $author instanceof User ? $author : null;

        $newly_mentioned = app(NoteMentionService::class)
            ->syncFromContent($note, $note->content, $authoring_user?->id);

        if ($authoring_user !== null) {
            Notification::send($newly_mentioned, new UserMentionedInNote($note, $authoring_user));
        }
    }

    /**
     * Resolve the target notable model from the whitelist.
     */
    protected function resolveModel(string $type, int|string $id): Model
    {
        return $this->models[$type]::findOrFail($id);
    }
}
