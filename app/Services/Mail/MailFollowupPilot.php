<?php

namespace App\Services\Mail;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\DB;

class MailFollowupPilot
{
    public function __construct(private MailFollowupClassifier $classifier) {}

    public function import(string $mailbox, array $inbox, array $sent): array
    {
        $counts = ['abiertos' => 0, 'revision' => 0, 'asociados' => 0, 'omitidos' => 0, 'duplicados' => 0, 'sin_hilo' => 0, 'invalidos' => 0];

        foreach (['received' => $inbox, 'sent' => $sent] as $direction => $messages) {
            $normalized = collect($messages)->map(fn ($message) => $this->normalize($mailbox, $direction, $message));
            $counts['invalidos'] += $normalized->whereNull()->count();

            foreach ($normalized->filter()->sortBy('occurred_at') as $message) {
                $counts[$this->record($mailbox, $message)]++;
            }
        }

        return $counts;
    }

    private function record(string $mailbox, array $message): string
    {
        if (DB::table('mail_followup_events')->where('message_key', $message['message_key'])->exists()) {
            return 'duplicados';
        }

        $followups = DB::table('mail_followups')->where('thread_key', $message['thread_key']);
        $known = $followups->exists();

        if (!$known && $message['direction'] === 'sent') {
            return 'sin_hilo';
        }

        $decision = $known ? null : $this->classifier->classify($message['subject'], $message['excerpt'], $message['headers']);

        return DB::transaction(function () use ($followups, $decision, $mailbox, $message): string {
            $thread = (clone $followups)->lockForUpdate()->first();
            $event = array_intersect_key($message, array_flip([
                'message_key', 'direction', 'occurred_at', 'author', 'subject', 'excerpt', 'web_link', 'automatic',
            ]));

            if ($thread === null && $decision['clasificacion'] === MailFollowupClassifier::IGNORE) {
                DB::table('mail_followup_events')->insert(array_intersect_key($event, array_flip(['message_key', 'direction', 'occurred_at'])));

                return 'omitidos';
            }

            if ($thread === null) {
                $review = $decision['clasificacion'] === MailFollowupClassifier::REVIEW;
                $threadId = DB::table('mail_followups')->insertGetId([
                    'thread_key' => $message['thread_key'],
                    'mailbox' => $mailbox,
                    'client_name' => $message['author'] ?? 'Remitente desconocido',
                    'subject' => $message['subject'],
                    'status' => $review ? 'revision' : 'pendiente',
                    'classification_source' => $decision['fuente'],
                    'classification_reason' => $decision['motivo'],
                    'classification_evidence' => $decision['evidencia'],
                    'last_activity_at' => $message['occurred_at'],
                    'status_at' => $message['occurred_at'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $result = $review ? 'revision' : 'abiertos';
            } else {
                $threadId = $thread->id;
                $this->updateStatus($thread, $message);
                $result = 'asociados';
            }

            DB::table('mail_followup_events')->insert($event + ['mail_followup_id' => $threadId]);

            return $result;
        }, 3);
    }

    private function updateStatus(object $thread, array $message): void
    {
        $date = $message['occurred_at'];
        $changes = [];

        if ($date > $thread->last_activity_at) {
            $changes['last_activity_at'] = $date;
        }

        $nextStatus = null;
        if (!$message['automatic'] && $date > $thread->status_at && $thread->status !== 'cerrado') {
            if ($message['direction'] === 'received') {
                $nextStatus = $thread->status === 'revision' ? 'revision' : 'pendiente';
            } elseif ($message['direction'] === 'sent') {
                $excerpt = $message['excerpt'];
                if (preg_match('/\b(?:favor de confirmar|podr[ií]an confirmar|agradezco confirmar|quedamos atentos a su confirmaci[oó]n)\b/iu', $excerpt)) {
                    $nextStatus = 'espera';
                } elseif (preg_match('/\b(?:adjunto|anexo|comparto|enviamos|env[ií]o)\s+(?:(?:la|el|los|las|una|un)\s+)?(?:propuesta|cotizaci[oó]n|presupuesto|informaci[oó]n|ficha|planos)\b/iu', $excerpt)) {
                    $nextStatus = 'respondido';
                }
            }
        }

        if ($nextStatus !== null) {
            $changes['status'] = $nextStatus;
            $changes['status_at'] = $date;
        }

        if ($changes !== []) {
            DB::table('mail_followups')->where('id', $thread->id)->update($changes + ['updated_at' => now()]);
        }
    }

    private function normalize(string $mailbox, string $direction, mixed $message): ?array
    {
        if (!is_array($message) || !is_string($message['id'] ?? null) || $message['id'] === ''
            || !is_string($message['conversationId'] ?? null) || $message['conversationId'] === '') {
            return null;
        }

        $date = $message[$direction === 'sent' ? 'sentDateTime' : 'receivedDateTime'] ?? null;
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}T/', $date)) {
            return null;
        }

        try {
            $occurredAt = CarbonImmutable::parse($date)->utc()->format('Y-m-d H:i:s');
        } catch (InvalidFormatException) {
            return null;
        }

        $subject = mb_substr(trim((string) ($message['subject'] ?? '')), 0, 255) ?: '(Sin asunto)';
        $excerpt = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($message['bodyPreview'] ?? '')) ?? ''), 0, 500);
        $headers = collect($message['internetMessageHeaders'] ?? [])
            ->filter(fn ($header) => is_string($header['name'] ?? null) && is_string($header['value'] ?? null))
            ->mapWithKeys(fn ($header) => [strtolower($header['name']) => $header['value']])->all();
        $sender = $message['sender']['emailAddress'] ?? [];
        $from = $message['from']['emailAddress'] ?? [];
        $person = $direction === 'sent' && !empty($sender['address']) ? $sender : $from;
        $address = trim((string) ($person['address'] ?? ''));
        $author = $direction === 'sent' && ($address === '' || strcasecmp($mailbox, $address) === 0)
            ? null
            : mb_substr(trim((string) ($person['name'] ?? '')) ?: $address, 0, 255);

        $link = $message['webLink'] ?? null;
        $origin = is_string($link) ? parse_url($link) : [];
        if (($origin['scheme'] ?? null) !== 'https' || !in_array(strtolower($origin['host'] ?? ''), ['outlook.office.com', 'outlook.office365.com', 'outlook.microsoft.com'], true)) {
            $link = null;
        }

        return [
            'thread_key' => hash('sha256', strtolower($mailbox) . "\0" . $message['conversationId']),
            'message_key' => hash('sha256', strtolower($mailbox) . "\0" . $message['id']),
            'direction' => $direction,
            'occurred_at' => $occurredAt,
            'subject' => $subject,
            'excerpt' => $excerpt,
            'author' => $author ?: null,
            'web_link' => $link,
            'automatic' => $this->classifier->isAutomatic($subject, $headers),
            'headers' => $headers,
        ];
    }
}
