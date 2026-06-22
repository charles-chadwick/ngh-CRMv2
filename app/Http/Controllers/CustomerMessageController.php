<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Topic;
use App\Services\MessageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

class CustomerMessageController extends Controller
{
    /**
     * Post a message to a customer's conversation, creating the underlying
     * topic (and its participants) the first time someone writes to them.
     */
    public function store(FormRequest $request, Customer $customer, MessageService $messages): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $author = $request->user();

        $topic = $this->conversationFor($customer);
        $topic->customers()->syncWithoutDetaching([$customer->getKey()]);

        if ($author !== null) {
            $relation = $author instanceof Customer ? $topic->customers() : $topic->users();
            $relation->syncWithoutDetaching([$author->getKey()]);
        }

        $messages->post($topic, $author, $validated['content']);

        return back();
    }

    /**
     * Find or create the customer's dedicated conversation topic.
     */
    protected function conversationFor(Customer $customer): Topic
    {
        return $customer->conversation()->firstOrCreate([], [
            'title' => $customer->full_name,
        ]);
    }
}
