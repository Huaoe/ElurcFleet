## Raw Concept
**Task:**
Fix favicon 404 errors, Ember server startup, and missing autocomplete attributes

**Changes:**
- Added autocomplete="new-password" to password input fields in onboarding form (lines 33, 40)
- Fixed intl-tel-input path duplication in @fleetbase/ember-ui/index.js (line 119)
- Switched to this.pathBase() for proper package root resolution in ember-ui

**Flow:**
Ember server startup -> resolve dependencies -> intl-tel-input path resolution -> server starts on 4200 -> onboarding form rendered

**Timestamp:** 2026-02-19

## Narrative
### Structure
The fix spans the console onboarding component and the core ember-ui package configuration.

### Features
Corrected intl-tel-input path resolution by replacing path.dirname(require.resolve()).replace() with this.pathBase() to prevent duplicated paths.

### Rules
Rule: The Ember dev server must run on port 4200 to serve assets like favicon.ico correctly. Ensure all extensions are loaded during startup.
