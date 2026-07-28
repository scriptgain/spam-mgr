<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MailDomain;
use App\Models\MailLogEntry;
use App\Models\Node;
use App\Models\QuarantineMessage;
use Illuminate\Http\Request;

/**
 * The node agent API. Nodes dial out to this; the panel never connects inward, so
 * a node needs only outbound 443 and can sit behind any firewall.
 *
 * Enrolment is one-time-token based, everything after uses the node's own key.
 */
class AgentController extends Controller
{
    /** Trade a one-time enrolment token for a permanent node key. */
    public function enroll(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'hostname' => ['nullable', 'string', 'max:191'],
            'ip' => ['nullable', 'ip'],
            'agent_version' => ['nullable', 'string', 'max:40'],
        ]);

        $node = Node::findByEnrollmentToken($data['token']);
        if (! $node) {
            return response()->json(['message' => 'Invalid or used enrolment token.'], 401);
        }

        $plainKey = $node->completeEnrollment();

        $extra = array_filter([
            'ip' => $data['ip'] ?? null,
            'agent_version' => $data['agent_version'] ?? null,
        ]);
        if ($extra) {
            $node->forceFill($extra)->save();
        }

        return response()->json([
            'node_id' => $node->uuid,
            'api_key' => $plainKey,
        ]);
    }

    /**
     * Everything the node needs to configure Postfix and score mail: which domains
     * to accept, where to relay them, and the thresholds to apply.
     *
     * The node caches this and keeps enforcing the last good copy, so a panel
     * outage never stops mail flowing.
     */
    public function config(Request $request)
    {
        $node = $request->attributes->get('agent_node');
        $this->touch($node);

        $domains = MailDomain::with(['policy', 'rules'])
            ->where('active', true)
            ->whereNotNull('verified_at')
            ->get()
            ->map(function (MailDomain $domain) {
                $policy = $domain->effectivePolicy();

                return [
                    'name' => $domain->name,
                    'destination_host' => $domain->destination_host,
                    'destination_port' => $domain->destination_port,
                    'tls_policy' => $domain->tls_policy,
                    'recipient_mode' => $domain->recipient_mode,
                    'recipients' => $domain->recipient_mode === 'list'
                        ? $domain->recipients()->where('active', true)->pluck('address')
                        : [],
                    'no_filter_recipients' => $domain->recipients()
                        ->where('active', true)->where('filtering_enabled', false)->pluck('address'),
                    'tag_level' => (float) ($policy?->tag_level ?? 5.0),
                    'tag2_level' => (float) ($policy?->tag2_level ?? 8.0),
                    'kill_level' => (float) ($policy?->kill_level ?? 12.0),
                    'rules' => $domain->rules->map->only(['type', 'value', 'list']),
                ];
            });

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'domains' => $domains,
        ]);
    }

    /**
     * Health, plus counts we record but never meter. Unlimited nodes is the pitch;
     * the numbers exist so a future volume tier is a pricing decision rather than
     * a guess.
     */
    public function heartbeat(Request $request)
    {
        $node = $request->attributes->get('agent_node');

        $data = $request->validate([
            'postfix_ok' => ['nullable', 'boolean'],
            'rspamd_ok' => ['nullable', 'boolean'],
            'clamav_ok' => ['nullable', 'boolean'],
            'queue_depth' => ['nullable', 'integer', 'min:0'],
            'disk_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'load' => ['nullable', 'numeric', 'min:0'],
            'cert_expires_at' => ['nullable', 'date'],
            'agent_version' => ['nullable', 'string', 'max:40'],
            'domain_count' => ['nullable', 'integer', 'min:0'],
            'mailbox_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $node->forceFill($data + ['last_seen_at' => now(), 'status' => 'online'])->save();

        return response()->json(['ok' => true]);
    }

    /** A message the node held. The body stays on the node. */
    public function quarantine(Request $request)
    {
        $node = $request->attributes->get('agent_node');
        $this->touch($node);

        $data = $request->validate([
            'domain' => ['required', 'string', 'max:191'],
            'sender' => ['nullable', 'string', 'max:191'],
            'recipient' => ['nullable', 'string', 'max:191'],
            'subject' => ['nullable', 'string', 'max:500'],
            'spam_score' => ['nullable', 'numeric'],
            'reason' => ['nullable', 'string', 'max:191'],
            'body_path' => ['nullable', 'string', 'max:500'],
        ]);

        $domain = MailDomain::where('name', strtolower($data['domain']))->first();
        if (! $domain) {
            return response()->json(['message' => 'Unknown domain.'], 404);
        }

        $message = QuarantineMessage::create([
            'mail_domain_id' => $domain->id,
            'node_id' => $node->id,
            'sender' => $data['sender'] ?? null,
            'recipient' => $data['recipient'] ?? null,
            'subject' => $data['subject'] ?? null,
            'spam_score' => $data['spam_score'] ?? null,
            'reason' => $data['reason'] ?? null,
            'body_path' => $data['body_path'] ?? null,
        ]);

        return response()->json(['uuid' => $message->uuid], 201);
    }

    /** Every message handled, clean or not. */
    public function log(Request $request)
    {
        $node = $request->attributes->get('agent_node');
        $this->touch($node);

        $data = $request->validate([
            'domain' => ['required', 'string', 'max:191'],
            'message_id' => ['nullable', 'string', 'max:191'],
            'sender' => ['nullable', 'string', 'max:191'],
            'recipient' => ['nullable', 'string', 'max:191'],
            'subject' => ['nullable', 'string', 'max:500'],
            'verdict' => ['required', 'in:clean,tagged,quarantined,rejected'],
            'score' => ['nullable', 'numeric'],
            'reason' => ['nullable', 'string', 'max:191'],
            'delivery_status' => ['nullable', 'in:pending,delivered,deferred,failed'],
            'delivery_detail' => ['nullable', 'string', 'max:512'],
        ]);

        $domain = MailDomain::where('name', strtolower($data['domain']))->first();
        if (! $domain) {
            return response()->json(['message' => 'Unknown domain.'], 404);
        }

        unset($data['domain']);

        MailLogEntry::create($data + [
            'mail_domain_id' => $domain->id,
            'node_id' => $node->id,
            'logged_at' => now(),
        ]);

        return response()->json(['ok' => true], 201);
    }

    /**
     * The release work queue. Only messages this node is holding are handed out,
     * because the body never left it.
     */
    public function releases(Request $request)
    {
        $node = $request->attributes->get('agent_node');
        $this->touch($node);

        $messages = QuarantineMessage::awaitingRelease()
            ->where('node_id', $node->id)
            ->with('domain:id,name,destination_host,destination_port')
            ->limit(100)
            ->get()
            ->map(fn (QuarantineMessage $m) => [
                'uuid' => $m->uuid,
                'body_path' => $m->body_path,
                'recipient' => $m->recipient,
                'destination_host' => $m->domain?->destination_host,
                'destination_port' => $m->domain?->destination_port,
            ]);

        return response()->json(['releases' => $messages]);
    }

    /** The node reporting whether a release actually delivered. */
    public function releaseResult(Request $request, string $uuid)
    {
        $node = $request->attributes->get('agent_node');
        $this->touch($node);

        $data = $request->validate([
            'ok' => ['required', 'boolean'],
            'error' => ['nullable', 'string', 'max:1000'],
        ]);

        $message = QuarantineMessage::where('uuid', $uuid)->where('node_id', $node->id)->first();
        if (! $message) {
            return response()->json(['message' => 'Unknown message.'], 404);
        }

        $message->forceFill([
            'release_attempts' => $message->release_attempts + 1,
            'release_completed_at' => $data['ok'] ? now() : null,
            'release_error' => $data['ok'] ? null : ($data['error'] ?? 'Release failed.'),
        ])->save();

        return response()->json(['ok' => true]);
    }

    /** Liveness without a full heartbeat payload. */
    private function touch(Node $node): void
    {
        $node->forceFill(['last_seen_at' => now(), 'status' => 'online'])->saveQuietly();
    }
}
