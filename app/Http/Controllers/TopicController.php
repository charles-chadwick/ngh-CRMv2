<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopicRequest;
use App\Models\Customer;
use App\Models\Topic;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TopicController extends Controller
{
    /**
     * Whitelist of models that may participate in a topic, keyed by the
     * `type` value accepted from the client.
     *
     * @var array<string, class-string<Model>>
     */
    protected array $participantModels = [
        'user' => User::class,
        'customer' => Customer::class,
    ];

    /**
     * The current participant's inbox of topics.
     */
    public function index(): Response
    {
        $participant = auth()->user();

        $topics = $participant->paginatedTopics();

        $topics->getCollection()->transform(function (Topic $topic) use ($participant) {
            $topic->setAttribute('unread_count', $topic->unreadCountFor($participant));

            return $topic;
        });

        return Inertia::render('Topics/Index', [
            'topics' => $topics,
        ]);
    }

    /**
     * The form for starting a new topic, optionally pre-selecting a participant.
     */
    public function create(Request $request): Response
    {
        $preselected = null;

        if ($request->filled('customer')) {
            $customer = Customer::withAvatar()->find($request->integer('customer'));

            if ($customer !== null) {
                $preselected = $this->participantPayload($customer, 'customer');
            }
        }

        return Inertia::render('Topics/Form', [
            'preselected' => $preselected,
        ]);
    }

    /**
     * Search users and customers that may be added to a topic.
     */
    public function participants(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();

        $users = User::query()
            ->whereKeyNot($request->user()?->id)
            ->when($search !== '', fn ($query) => $query->where(fn ($grouped) => $grouped->withSearch($search)))
            ->withAvatar()
            ->limit(8)
            ->get()
            ->map(fn (User $user) => $this->participantPayload($user, 'user'));

        $customers = Customer::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($grouped) => $grouped->withSearch($search)))
            ->withAvatar()
            ->limit(8)
            ->get()
            ->map(fn (Customer $customer) => $this->participantPayload($customer, 'customer'));

        return response()->json($users->concat($customers)->values());
    }

    /**
     * A single thread with its messages; marks the thread read for the viewer.
     */
    public function show(Topic $topic): Response
    {
        $participant = auth()->user();

        abort_unless($topic->hasParticipant($participant), HttpResponse::HTTP_FORBIDDEN);

        $topic->load([
            'users' => fn ($query) => $query->withAvatar(),
            'customers' => fn ($query) => $query->withAvatar(),
        ]);

        $messages = $topic->messages()
            ->with('author')
            ->reorder('created_at')
            ->paginate(20, ['*'], 'messages_page')
            ->withQueryString();

        $topic->markReadFor($participant);

        return Inertia::render('Topics/Show', [
            'topic' => $topic,
            'messages' => $messages,
        ]);
    }

    /**
     * Start a new topic with its participants and opening message.
     */
    public function store(TopicRequest $request, MessageService $messages): RedirectResponse
    {
        $validated = $request->validated();
        $author = $request->user();

        $topic = DB::transaction(function () use ($validated, $author, $messages) {
            $topic = Topic::create(['title' => $validated['title']]);

            $this->attachParticipants($topic, $validated['participants'], $author);

            $messages->post($topic, $author, $validated['content']);

            return $topic;
        });

        return to_route('topics.show', $topic);
    }

    /**
     * Shape a user or customer for the participant picker.
     *
     * @return array{type: string, id: int, full_name: string, avatar_url: ?string}
     */
    protected function participantPayload(Model $participant, string $type): array
    {
        return [
            'type' => $type,
            'id' => $participant->getKey(),
            'full_name' => $participant->full_name,
            'avatar_url' => $participant->avatar_url,
        ];
    }

    /**
     * Attach the selected participants plus the author to the topic.
     *
     * @param  array<int, array{type: string, id: int}>  $participants
     */
    protected function attachParticipants(Topic $topic, array $participants, Model $author): void
    {
        $topic->users()->syncWithoutDetaching([$author->getKey()]);

        foreach ($participants as $participant) {
            $model = $this->participantModels[$participant['type']]::findOrFail($participant['id']);

            $relation = $model instanceof User ? $topic->users() : $topic->customers();
            $relation->syncWithoutDetaching([$model->getKey()]);
        }
    }
}
