<?php

use App\Http\Requests\CompanyRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\NoteRequest;
use App\Models\Note;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;

function validateNote(array $payload): Illuminate\Validation\Validator
{
    return Validator::make($payload, (new NoteRequest)->rules());
}

function updateRules(): array
{
    $request = NoteRequest::create('/', 'PUT');
    $route = new Route('PUT', 'notes/{note}', fn () => null);
    $route->bind($request);
    $route->setParameter('note', new Note);
    $request->setRouteResolver(fn () => $route);

    return $request->rules();
}

it('requires the core note fields', function () {
    $validator = validateNote([
        'model_type' => 'customer',
        'model_id' => 1,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain('type', 'title', 'content');
});

it('rejects an unknown model type', function () {
    $validator = validateNote([
        'model_type' => 'invoice',
        'model_id' => 1,
        'type' => 'General',
        'title' => 'Hi',
        'content' => 'There',
    ]);

    expect($validator->errors()->keys())->toContain('model_type');
});

it('passes with a complete note payload', function () {
    $validator = validateNote([
        'model_type' => 'company',
        'model_id' => 1,
        'type' => 'Call',
        'title' => 'Called client',
        'content' => 'Discussed renewal.',
    ]);

    expect($validator->passes())->toBeTrue();
});

it('does not require a model when updating an existing note', function () {
    $validator = Validator::make([
        'type' => 'General',
        'title' => 'Updated title',
        'content' => 'Updated content.',
    ], updateRules());

    expect($validator->passes())->toBeTrue();
});

it('no longer accepts notes through the edit requests', function () {
    expect((new CustomerRequest)->rules())->not->toHaveKey('notes')
        ->and((new CompanyRequest)->rules())->not->toHaveKey('notes');
});
