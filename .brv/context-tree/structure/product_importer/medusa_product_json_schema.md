## Raw Concept
**Task:**
Define product import schema for Fleetbase Storefront

**Changes:**
- Standardized product JSON structure for bulk import
- Added categoryPath support for hierarchical categorization
- Implemented variant pricing and inventory tracking
- Mapped metadata fields for nutritional and origin data

**Files:**
- product-importer/json/products-medusa-import-001-with-images.json

**Flow:**
JSON source -> categoryPath mapping -> variant/price extraction -> image URL resolution -> metadata attachment

**Timestamp:** 2026-02-17

## Narrative
### Structure
The import files are structured as JSON arrays of product objects. Each product contains nested objects for variants, images, tags, and metadata.

### Dependencies
Requires a processing script to handle 4000+ products across 8 JSON files, mapping Medusa-style handles and variants to Fleetbase Storefront models.

### Features
Supports multi-level category paths (e.g., Épicerie > Sucree > Conserve), multiple images per product, and complex metadata including ingredients, nutritional values, and origin.

### Rules
Rule 1: Category paths must be processed as arrays to maintain hierarchy.
Rule 2: Variants must include SKU, inventory_quantity, and price with currency_code.
Rule 3: Image URLs are relative to /uploads/ and must be resolved during import.
Rule 4: Metadata fields (ingredients, nutritional_values) must be preserved verbatim.

### Examples
Example Category Path: ["Épicerie", "Sucree", "Conserve Fruits (confiture, Purée, Compo"]
Example Price: {"amount": 431, "currency_code": "eur"}
