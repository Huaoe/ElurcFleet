## Relations
@structure/product_importer/import_strategy_and_epic_overview.md
@structure/product_importer/medusa_product_json_schema.md

## Raw Concept
**Task:**
Implement database seeding for Fleetbase categories and products due to lack of public APIs

**Changes:**
- Created CategorySeeder for direct catalog_subjects table insertion
- Created ProductSeeder for direct products table insertion
- Added CLI scripts for category and product seeding
- Configured dry-run testing for bulk imports

**Files:**
- product-importer/seeders/CategorySeeder.js
- product-importer/seeders/ProductSeeder.js
- product-importer/scripts/seed-categories.js
- product-importer/scripts/seed-products.js

**Flow:**
JSON source -> Seeder logic -> Direct MySQL insert (catalog_subjects/products/catalog_category_products)

**Timestamp:** 2026-02-18

**Author:** Developer

## Narrative
### Structure
The seeding system bypasses the API layer to interact directly with the Fleetbase database schema in the product-importer directory.

### Dependencies
Requires mysql2 for database connectivity and uuid for record ID generation.

### Features
Supports bulk creation of 59 categories and 4000 products with automatic category linking. Includes dry-run capability for validation.

### Rules
Rule 1: Use direct database insertion as /storefront/v1/categories and /int/v1/categories endpoints are unavailable.
Rule 2: Maintain links in catalog_category_products for category-product relationships.

### Examples
Execution commands: npm run seed:categories, npm run seed:products
