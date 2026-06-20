<?php

use App\Http\Requests\ContactRequest;
use Illuminate\Support\Facades\Validator;

it('requires model_type and a valid type on create', function () {
    $rules = (new ContactRequest)->rules();

    $validator = Validator::make([
        'type' => 'NotAType',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('model_type'))->toBeTrue()
        ->and($validator->errors()->has('type'))->toBeTrue();
});

it('passes with valid create data', function () {
    $rules = (new ContactRequest)->rules();

    $validator = Validator::make([
        'model_type' => 'customer',
        'model_id' => 1,
        'type' => 'Office',
        'is_primary' => true,
        'city' => 'Seattle',
    ], $rules);

    expect($validator->passes())->toBeTrue();
});
