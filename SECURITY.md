# Security

## Supported versions

| Version | Status         |
|---------|----------------|
| 0.x     | Active support |

## Reporting a vulnerability

Please **do not** open a public GitHub issue for security-sensitive findings.
Email **opensource@simtabi.com** with a description, reproduction steps, and the
affected version(s). We aim to acknowledge within 72 hours.

## Hardening notes

- Wizard routes are protected by the install-once guard, CSRF (the `web` group)
  and rate limiting.
- The web layer never writes the `.env` or the database directly — all work is
  delegated to the headless engine, which writes the `.env` atomically and masks
  secrets in logs.
