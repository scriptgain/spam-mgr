# SpamMGR

**Self-hosted email security gateway. Spam and virus filtering in front of your mail
servers, with a customer portal.**

You install one panel on your own server, then stand up as many MX nodes as you want.
Node count is not metered: every node is a machine you already pay for.

## How it works

```
Internet sender -SMTP:25-> MX node (Postfix -> Rspamd -> ClamAV)
                              |
                              +-- spam/virus -> held on the node
                              |                  + reported to the panel
                              +-- clean ------> relayed to the real mail server
                              ^
                 domains, destinations, thresholds and rules
                 pulled from the panel; nodes cache the last
                 good copy so a panel outage never stops mail
```

Nodes dial out to the panel and nothing dials in, so a node needs only outbound 443.
Each node enrols once with a one-time token and gets its own key, so a leaked key on
one node is revoked on its own without touching the others.

Quarantined message bodies stay on the node that caught them. The panel holds the
metadata and the release decision; releasing queues work the node collects on its next
poll.

## Who sees what

| Role | Sees |
| --- | --- |
| `admin` | Everything: customers, nodes, policies, all mail |
| `user` | Operator staff. All mail, no operator administration |
| `customer` | One customer's domains, mailboxes, quarantine, mail log and rules |

## Surfaces

| Surface | Routes |
| --- | --- |
| Panel and customer portal | `web.php` |
| Manager REST API (bearer token) | `api.php` under `/api/v1` |
| Node agent API (per-node key) | `api.php` under `/api/agent/v1` |

## Requirements

PHP 8.3, MySQL or MariaDB, and a working scheduler. The scheduler matters: retention
runs there, and unbounded mail logs will fill the disk.

```bash
php artisan migrate
php artisan spam:bootstrap    # seeds filtering policies + first API token
```

## Retention

`spam:housekeeping` prunes mail logs, settled quarantine, blocklist checks and audit
rows nightly. Windows are configurable and `--dry-run` reports without deleting.
Quarantine still sitting at `quarantined` is never pruned, because nobody has looked at
it yet.

## Licensing

Self-hosted installs validate against scriptgain.com. Enforcement is deliberately
lenient and never locks the panel: locking the operator out would block them releasing
legitimate mail, and a licensing problem must never become a mail problem. The nodes
never talk to licensing at all.

## Status

Early. The panel, the customer portal and the node agent API are built and working. The
Go node agent and the installer are not written yet.
