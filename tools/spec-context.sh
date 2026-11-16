#!/usr/bin/env bash
# Selecciona una historia existente sin crear ramas ni modificar su documentación.
set -euo pipefail

project_root="$(CDPATH='' cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
feature="${1:-$(git -C "$project_root" branch --show-current)}"
if [[ $# -gt 1 ]] || [[ ! "$feature" =~ ^spec-00[1-8]$ ]]; then
    echo 'Uso: bash tools/spec-context.sh [spec-001 … spec-008]. La rama actual debe identificar una historia.' >&2
    exit 1
fi
if [[ ! -f "$project_root/specs/$feature/spec.md" ]]; then
    echo "No existe specs/$feature/spec.md; no se modifica el contexto." >&2
    exit 1
fi
if [[ ! -f "$project_root/.specify/integration.json" ]]; then
    echo 'Spec Kit no está inicializado en este proyecto.' >&2
    exit 1
fi
context_tmp="$(mktemp "$project_root/.specify/feature.json.XXXXXX")"
trap 'rm -f -- "$context_tmp"' EXIT
printf '{"feature_directory":"specs/%s"}\n' "$feature" > "$context_tmp"
mv -- "$context_tmp" "$project_root/.specify/feature.json"
printf '{"FEATURE":"%s","FEATURE_DIRECTORY":"specs/%s"}\n' "$feature" "$feature"
