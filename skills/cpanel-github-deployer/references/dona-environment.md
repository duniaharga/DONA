# Deployment environment setup

This public distribution deliberately contains no private connection details. Read the local project instructions and request the destination information using the ordered setup in SKILL.md.

- Confirm the local project path and source URL.
- Obtain SSH host, port, username and local key-file paths securely. A cPanel HTTPS login port is not the SSH port.
- Obtain the target HTTPS URL, any subdirectory and verified document root.
- Obtain the database name, user and host; keep the password in protected destination configuration.
- WordPress Studio may use SQLite while cPanel uses MySQL/MariaDB. Verify export compatibility and exclude Studio-only database loaders.
- Keep unique file/database rollback backups outside the public document root.
- Discover GitHub repository, branch and requested deployment scope rather than assuming them.
- Preserve existing live orders, users, uploads, configuration and unrelated content during routine updates.

Do not commit private environment records, keys, passwords, database dumps or deployment logs to the public repository.
