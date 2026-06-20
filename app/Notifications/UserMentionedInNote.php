<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Note;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class UserMentionedInNote extends Notification
{
    use Queueable;

    public function __construct(
        public Note $note,
        public User $mentioner,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $notable = $this->note->notable;

        return [
            'note_id' => $this->note->id,
            'mentioner_name' => $this->mentioner->full_name,
            'parent_label' => $notable ? $this->parentLabel($notable) : 'a record',
            'url' => $notable ? $this->parentUrl($notable) : route('home'),
            'snippet' => Str::limit(strip_tags($this->note->content), 100),
        ];
    }

    protected function parentLabel(object $notable): string
    {
        return $notable->full_name ?? $notable->title ?? 'a record';
    }

    protected function parentUrl(object $notable): string
    {
        return match (true) {
            $notable instanceof Customer => route('customers.show', $notable),
            $notable instanceof Company => route('companies.show', $notable),
            $notable instanceof Lead => route('leads.show', $notable),
            default => route('home'),
        };
    }
}
