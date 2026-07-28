<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * IP-gated one-click sign-in for a test account.
 *
 * SECURITY, read before changing anything here.
 *
 * This host's nginx carries `set_real_ip_from 0.0.0.0/0` with no `real_ip_header`
 * directive, so it defaults to X-Real-IP and nginx will rewrite $remote_addr to
 * whatever a caller puts in that header. Verified by experiment: a request sent with
 * `X-Real-IP: 203.0.113.99` was logged by this application as coming from
 * 203.0.113.99. Laravel's $request->ip(), X-Forwarded-For and X-Real-IP are therefore
 * ALL attacker-controlled here, and a gate built on any of them is an open admin
 * bypass, not a restriction.
 *
 * So the gate reads X-True-Peer, which the front nginx block sets from
 * $realip_remote_addr: the original peer address nginx keeps from before realip
 * rewriting. `proxy_set_header` overwrites, so a value supplied by the caller is
 * discarded rather than forwarded. The backend that would see an unfiltered header is
 * on :8080, which ufw does not expose.
 *
 * Fails closed everywhere: no allowlist configured, no header, or no matching user and
 * the feature simply does not exist.
 */
class DevLoginGate
{
    /**
     * Header the front nginx block fills from $realip_remote_addr. Never trust a
     * header a caller can set instead of this one.
     */
    public const PEER_HEADER = 'X-True-Peer';

    /** Setting holding allowed IPs or CIDRs, comma separated. Empty disables the feature. */
    public const ALLOWLIST_SETTING = 'dev_login_ips';

    /** Setting naming the account the button signs in as. */
    public const ACCOUNT_SETTING = 'dev_login_email';

    public function enabled(): bool
    {
        return trim((string) Setting::get(self::ALLOWLIST_SETTING, '')) !== '';
    }

    /** The genuine peer address, or null when the trusted header is absent. */
    public function peerIp(Request $request): ?string
    {
        $ip = trim((string) $request->header(self::PEER_HEADER, ''));

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    public function allows(Request $request): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $peer = $this->peerIp($request);
        if ($peer === null) {
            return false;
        }

        foreach ($this->allowlist() as $entry) {
            if ($this->matches($peer, $entry)) {
                return true;
            }
        }

        return false;
    }

    /** The account the button signs in as, or null when it does not exist. */
    public function account(): ?User
    {
        $email = trim((string) Setting::get(self::ACCOUNT_SETTING, ''));

        return $email === '' ? null : User::where('email', $email)->first();
    }

    /** @return string[] */
    public function allowlist(): array
    {
        $raw = (string) Setting::get(self::ALLOWLIST_SETTING, '');

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /** Exact IP or CIDR membership. IPv4 and IPv6. */
    private function matches(string $ip, string $entry): bool
    {
        if (! str_contains($entry, '/')) {
            return hash_equals($entry, $ip);
        }

        [$subnet, $bits] = explode('/', $entry, 2);
        $bits = (int) $bits;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        // Different families never match, and a malformed entry must not match either.
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }
        if ($bits < 0 || $bits > strlen($ipBin) * 8) {
            return false;
        }

        $whole = intdiv($bits, 8);
        $rest = $bits % 8;

        if ($whole > 0 && ! hash_equals(substr($subnetBin, 0, $whole), substr($ipBin, 0, $whole))) {
            return false;
        }
        if ($rest === 0) {
            return true;
        }

        $mask = chr(0xFF << (8 - $rest) & 0xFF);

        return (($ipBin[$whole] & $mask) === ($subnetBin[$whole] & $mask));
    }
}
