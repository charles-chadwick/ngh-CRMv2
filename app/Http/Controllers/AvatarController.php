<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\HasMedia;

class AvatarController extends Controller
{
    /**
     * Whitelist of models that may receive an avatar, keyed by the
     * `model_type` value accepted from the client.
     *
     * @var array<string, class-string<Model>>
     */
    protected array $models = [
        'customer' => Customer::class,
        'user' => User::class,
        'company' => Company::class,
    ];

    /**
     * Validation rules identifying the target model.
     *
     * @var array<string, array<int, string>>
     */
    protected const MODEL_RULES = [
        'model_type' => ['required', 'string'],
        'model_id' => ['required'],
    ];

    /**
     * Store (or replace) the avatar for the given model.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...self::MODEL_RULES,
            'image' => ['required', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],
        ]);

        $model = $this->resolveModel($validated['model_type'], $validated['model_id']);

        $model->addMediaFromRequest('image')
            ->toMediaCollection($model->avatarCollection());

        return back();
    }

    /**
     * Remove the avatar from the given model.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate(self::MODEL_RULES);

        $model = $this->resolveModel($validated['model_type'], $validated['model_id']);

        $model->clearMediaCollection($model->avatarCollection());

        return back();
    }

    /**
     * Resolve the target model from the whitelist. The model is authoritative
     * for its own avatar collection name via the WithAvatar trait.
     */
    protected function resolveModel(string $type, int|string $id): HasMedia
    {
        if (! array_key_exists($type, $this->models)) {
            throw ValidationException::withMessages(['model_type' => 'Invalid model type.']);
        }

        return $this->models[$type]::findOrFail($id);
    }
}
