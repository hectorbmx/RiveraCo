## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, use the installed graphify skill or instructions before doing anything else.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
## Protected UI

- Do not replace, normalize, or remove the production sidebar/navbar icons in `resources/views/layouts/admin.blade.php` unless the user explicitly asks for an icon change. Preserve UTF-8 encoding when editing this file; avoid broad text rewrites that can corrupt emoji/accented characters.
## Encoding

- All source, Blade, config, route, docs, and test files must stay UTF-8. Do not save files as Windows-1252/ANSI.
- Before finishing changes that touch user-facing Spanish text, run `php tools/check_mojibake.php <touched files>` and fix any `Ã`, `Â`, `â€`, or replacement-character output. Run `php tools/check_mojibake.php` for a full cleanup/release gate.

## Sandbox local de Codex

- En este equipo es frecuente que el sandbox local de Codex bloquee lecturas, escrituras o validaciones con errores tipo `deny-read ACLs`, aun cuando los archivos pertenezcan al proyecto.
- Cuando un comando necesario falle por ese motivo, vuelve a ejecutarlo inmediatamente con `sandbox_permissions: "require_escalated"`, una justificacion breve y un `prefix_rule` acotado cuando aplique.
- No te detengas a buscar rodeos ni alternativas fragiles si la accion es segura y necesaria para leer, escribir o validar archivos del proyecto.
- Esto aplica a inspeccion, creacion/edicion de archivos, `php -l`, `php artisan route:list`, `php artisan view:cache`, `git status` y validaciones equivalentes.

