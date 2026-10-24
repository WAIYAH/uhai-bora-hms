# Security policy

Uhai Bora handles appointment details that can reveal information about
people’s health. Please report security problems privately and responsibly.

## Supported versions

Only the latest version on the `main` branch receives security fixes.

| Component | Supported |
| --- | --- |
| PHP | 8.3 (Docker image); the code also runs on PHP 7.3+, but PHP versions that no longer receive upstream security updates should not be used in production |
| MariaDB / MySQL | MariaDB 10.11 (Docker image) or another currently supported release |

## Reporting a vulnerability

- Do **not** open a public issue for security problems.
- Contact the operator of the deployment through the privacy or contact details published on its Contact page, or the repository owner privately.
- Include the affected page or file, steps to reproduce and the impact you expect. Do not include real patient data, and do not access, change or delete information that is not yours.

We aim to acknowledge reports promptly, keep you updated while we investigate,
and credit you when a fix is released if you would like that.

## Operator responsibilities

Before using a deployment with real patient information:

- Delete the demo accounts or change their passwords (the administrator dashboard warns while they exist).
- Serve the site only over HTTPS and use strong, unique database passwords.
- Keep PHP, the database server and the operating system patched.
- Back up the database securely and test restores.
- Monitor the server error log; each error has a reference shown to the user.
- Follow your incident-response plan for personal data breaches, including notifying the Office of the Data Protection Commissioner within 72 hours where the law requires it.
