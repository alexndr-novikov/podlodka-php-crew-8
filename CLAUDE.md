# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository is for the Podlodka PHP Crew 8 — a learning/workshop project focused on Docker best practices for PHP applications. The project is in its early stages with no application code yet.

## Reference Material

`old/SUMMARY.md` contains a Russian-language summary of Docker best practices for PHP covering:
- Dockerfile optimization (layer caching, multi-stage builds, targets, COPY --link)
- Security (non-root containers, UID/GID sync, secret mounts)
- Docker Compose patterns (healthchecks, env substitution, multi-file configs)
- Tooling ecosystem (Dive, Hadolint, Dockle, Trivy, Traefik, MinIO, MailHog)

## Git Commits

Do NOT add `Co-Authored-By: Claude` to commit messages.

## Slides Workflow

When editing slides, always open Playwright to visually verify changes. After finishing a set of edits, ask the user: close Playwright or continue with the next task?

## Language

Project documentation is in Russian. Communicate in Russian when discussing project-specific topics unless asked otherwise.
