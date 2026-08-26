# Changelog

## 0.5.1

- GitHub README redesigned in Turkish and English
- README and documentation references standardized to the final ScratchByPHP brand filenames

- Brand assets added to documentation
- scratchattach / TimMcCool attribution added in THIRD_PARTY_NOTICES.md

Security hardening release:

- Authenticated HTTP requests are restricted to HTTPS `scratch.mit.edu` hosts/subdomains
- Automatic redirects are disabled for requests carrying Scratch credentials
- Logger redacts tokens, session IDs, cookies, authorization data and sensitive query values
- Scratch session IDs now have length/control-character validation
- Compressed session payload decoding is capped at 64 KiB
- Download helper rejects NUL paths, requires a writable target directory and uses `LOCK_EX`
- Plaintext account JSON remains intentional and unchanged


## 0.5.0

- Composer / Packagist metadata refreshed
- GitHub-ready repository structure
- Turkish-first and English documentation
- GitHub Pages documentation website
- Copyable code snippets
- CI workflow for PHP 8.1–8.4
- Contribution, security and issue templates
- Project player helper methods documented

## 0.4.x

- Registration assistant and JSON account profiles
- Cloud remote read/write verification
- Project, studio, user and authenticated write operations
- CloudRequests, CloudDatabase and project analysis
