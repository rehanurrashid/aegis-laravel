File: <portal>/<page>.php (attached)

Execute Pass 1 (Centralize) then Pass 2 (Design) on the attached file. Stop before wiring.

Pass 1 — Centralize per Aegis_Global_Wiring_Prompt.md
- Swap to canonical includes (page_head.php, sidebar.php, header.php, page_foot.php)
- Add the mandatory theme_loader.php block + body theme class
- Drop local JS that duplicates _shared.js globals
- Retarget asset paths to root globals
- Rename outdated filename references

Pass 2 — Design per Aegis_Desing_Prompt_Short.md (STRICT — apply every rule)
Read Aegis_Desing_Prompt_Short.md first. Apply all 20 rules verbatim — do not skip any. For class definitions, edge cases, or anything the short prompt doesn't cover, fall back to Aegis_Desing_Prompt.md as the comprehensive reference. On conflict, the short prompt wins.

Reference page: provider-portal/<equivalent>.php (in KB) — mirror its hero variant, stat chip layout, tab pattern, modal structure, and form layout.

Then seed verification:
- Check seed.json for the rows this page needs to render meaningfully (every state visible — active, pending, empty, suspended, etc.)
- If missing rows, add them to seed.json with realistic data linked to existing demo users
- If schema CHECK constraints are too narrow for new values, flag for db.php widening (don't edit db.php silently)
- If new read helpers are needed, add to models.php (functions only, no top-level code)
- Do NOT write to models_write.php or save_*.php — that's the wiring pass (Pass 3)

Deliverables:
1. Refactored <page>.php
2. Updated seed.json (if rows added)
3. Updated models.php (if read helpers added)
4. Summary in this order:
   - Centralization diff (locals dropped, JS helpers dropped)
   - Design — Pass 2 checklist: mark each of the 20 rules from Aegis_Desing_Prompt_Short.md as APPLIED / N/A / FLAGGED. No silent skips.
   - Seed additions (with row counts and which states each row covers)
   - Anything flagged for owner approval



=================================================================================



CLAUDE DESING PROMPT FOR SECTION:

Given you all necessary css, js, documents to understand the project context and file structrure and centrazlied system, read everything deeply and understand it. 

Next I want you to only redesing class="agmt-card" this specific section using shared.css global classes make changes to classes where necessary give me updated html file contains specific agmt-card section along with css and js.

Don't redesing everything just redesign agmt-card and specific modals linedin with it, strictly follow the Aegis Design prompt and brand guidelines please. 

