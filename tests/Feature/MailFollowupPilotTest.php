<?php

namespace Tests\Feature;

use App\Services\Mail\MailFollowupClassifier;
use App\Services\Mail\MailFollowupPilot;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MailFollowupPilotTest extends TestCase
{
    public function test_openai_receives_only_the_subject_and_excerpt_with_a_strict_schema(): void
    {
        config(['services.mail_followup.openai.enabled' => true, 'services.mail_followup.openai.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['https://api.openai.com/v1/responses' => Http::response([
            'status' => 'completed',
            'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode([
                'clasificacion' => 'solicitud_presupuesto',
                'motivo' => 'Pide precio para perforación.',
                'evidencia' => 'Solicitamos cotización',
            ])]]]],
        ])]);

        $result = app(MailFollowupClassifier::class)->classify('Perforación', 'Solicitamos cotización', ['x-internal' => 'test']);

        $this->assertSame('solicitud_presupuesto', $result['clasificacion']);
        Http::assertSent(fn ($request) => $request['store'] === false
            && $request['text']['format']['strict'] === true
            && json_decode($request['input'], true) === [
                'asunto' => 'Perforación',
                'extracto' => 'Solicitamos cotización',
            ]);
    }

    public function test_unavailable_ai_and_unverified_evidence_go_to_review(): void
    {
        config(['services.mail_followup.openai.enabled' => true, 'services.mail_followup.openai.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['https://api.openai.com/v1/responses' => Http::sequence()
            ->pushStatus(503)
            ->push([
                'status' => 'completed',
                'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode([
                    'clasificacion' => 'no_aplica', 'motivo' => 'Supuesto boletín.', 'evidencia' => 'Boletín inventado',
                ])]]]],
            ])]);

        $classifier = app(MailFollowupClassifier::class);
        $this->assertSame('por_revisar', $classifier->classify('Consulta', 'Hola', [])['clasificacion']);
        $this->assertSame('por_revisar', $classifier->classify('Consulta', 'Hola', [])['clasificacion']);
    }

    public function test_known_messages_skip_classification_and_replaying_does_not_duplicate_events(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));
        if (!Schema::hasTable('mail_followups')) {
            $this->assertSame(0, Artisan::call('migrate', [
                '--path' => 'database/migrations/2026_11_24_000000_create_mail_followup_pilot_tables.php',
            ]));
        }

        DB::beginTransaction();
        try {
            $classifier = $this->partialMock(MailFollowupClassifier::class);
            $classifier->shouldReceive('classify')->once()->andReturn([
                'clasificacion' => MailFollowupClassifier::REQUEST,
                'fuente' => 'openai',
                'motivo' => 'Solicita una cotización.',
                'evidencia' => 'Solicitamos cotización',
            ]);

            $key = (string) str()->uuid();
            $mailbox = "{$key}@example.test";
            $incoming = [
                'id' => 'message-A', 'conversationId' => 'thread-a', 'subject' => 'Obra',
                'bodyPreview' => 'Solicitamos cotización', 'receivedDateTime' => '2026-11-24T15:00:00Z',
            ];
            $reply = [
                'id' => 'message-B', 'conversationId' => 'thread-a', 'subject' => 'Re: Obra',
                'bodyPreview' => 'Adjunto la cotización. Favor de confirmar la fecha.', 'sentDateTime' => '2026-11-24T16:00:00Z',
            ];
            $automatic = [
                'id' => 'message-b', 'conversationId' => 'thread-a', 'subject' => 'Respuesta automática',
                'bodyPreview' => 'Estoy fuera de oficina', 'receivedDateTime' => '2026-11-24T17:00:00Z',
            ];
            $pilot = app(MailFollowupPilot::class);
            $first = $pilot->import($mailbox, [$automatic, $incoming], [$reply]);

            $this->assertSame(1, $first['abiertos']);
            $this->assertSame(2, $first['asociados']);
            $thread = DB::table('mail_followups')->where('mailbox', $mailbox)->first();
            $this->assertSame('espera', $thread->status);
            $this->assertSame(3, $pilot->import($mailbox, [$incoming, $automatic], [$reply])['duplicados']);
            $this->assertSame(3, DB::table('mail_followup_events')->where('mail_followup_id', $thread->id)->count());
        } finally {
            DB::rollBack();
        }
    }
}
