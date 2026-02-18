# Story 2.1: Category Hierarchy Import Script

Status: review

## Story

As a platform operator,
I want to create a script that imports the 42 hierarchical product categories,
so that the product catalog is properly organized.

## Acceptance Criteria

### AC1: JSON File Scanning and Category Extraction
**Given** product JSON files exist in `product-importer/json/`
**When** I run the category import script
**Then** Script scans all 8 JSON files and extracts unique `categoryPath` arrays
**And** Script builds category tree with 3 levels (parent → child → grandchild)
**And** Script creates 42 categories via Fleetbase Storefront API

### AC2: Category Creation with Parent References
**Given** category creation process
**When** Script creates each category
**Then** Categories are created with proper parent UUID references
**And** Script handles existing categories with find-or-create pattern
**And** Script outputs category mapping JSON (name → UUID)

### AC3: Category Import Completion
**Given** category import completes
**When** Script finishes execution
**Then** All 42 categories successfully created in Fleetbase
**And** Category hierarchy is preserved (parent-child relationships)
**And** Script logs creation progress and any errors

## Tasks / Subtasks

- [x] **Task 1: Create CategoryImportService class** (AC: 1, 2)
  - [x] Create service class at `product-importer/src/services/CategoryImportService.js` (or PHP if using Laravel command)
  - [x] Implement JSON file discovery and scanning
  - [x] Implement category path extraction from `categoryPath` arrays
  - [x] Add category tree building logic (3 levels)
  - [x] Add Fleetbase API integration for category creation
  - [x] Add logging with progress tracking

- [x] **Task 2: Implement category tree builder** (AC: 1)
  - [x] Parse categoryPath arrays from all 8 JSON files
  - [x] Normalize category names (handle duplicates, special chars)
  - [x] Build hierarchical tree structure (parent → child → grandchild)
  - [x] Identify unique categories (should total 42)
  - [x] Calculate category depth levels

- [x] **Task 3: Implement Fleetbase category creation** (AC: 2)
  - [x] Create parent categories first (top level)
  - [x] Create child categories with parent UUID references
  - [x] Create grandchild categories with parent UUID references
  - [x] Implement find-or-create pattern for idempotency
  - [x] Handle API rate limiting and errors
  - [x] Store created category UUIDs for mapping

- [x] **Task 4: Create category mapping output** (AC: 2)
  - [x] Generate `product-importer/category-mapping.json`
  - [x] Map category name to Fleetbase UUID
  - [x] Include category level (1, 2, or 3)
  - [x] Include parent category reference
  - [x] Save mapping for use by Story 2.3 (product import)

- [x] **Task 5: Add CLI script entry point** (AC: 1, 2, 3)
  - [x] Create `product-importer/scripts/import-categories.js` (or Artisan command)
  - [x] Add CLI argument parsing (dry-run, verbose, etc.)
  - [x] Add progress output to console
  - [x] Add error reporting
  - [x] Add completion summary

- [x] **Task 6: Create unit tests** (AC: 1, 2)
  - [x] Test category path extraction from sample JSON
  - [x] Test tree building logic
  - [x] Test find-or-create pattern
  - [x] Mock Fleetbase API responses

## Dev Notes

### Architecture Context

This story is part of **Epic 2: Product Catalog Import** which imports 4,000 pre-scraped products into the Fleetbase Storefront. The category hierarchy must be established before products can be imported, as products reference categories by UUID.

**Key Architectural Patterns:**
- **3-Level Category Hierarchy**: Parent → Child → Grandchild (e.g., Food → Dairy → Cheese)
- **Idempotent Import**: Running the script multiple times should not create duplicates
- **State Persistence**: Category mapping JSON serves as input for subsequent product import

**Integration Points:**
- Reads from local JSON files in `product-importer/json/` (8 files)
- Writes to Fleetbase Storefront API (categories endpoint)
- Produces mapping file for Story 2.3 (Product Import Script)

### JSON Input Structure

The product JSON files contain `categoryPath` arrays like:
```json
{
  "categoryPath": ["Food", "Dairy", "Cheese"],
  "breadcrumb": ["Food", "Dairy", "Cheese"],  // IGNORED - not used
  "name": "Swiss Cheese 200g",
  ...
}
```

**CRITICAL: Only `categoryPath` field is used for category creation**
- The `breadcrumb` field is ignored (even though it contains identical data)
- Tags, metadata, and all other fields are ignored
- Empty or whitespace-only category names are skipped
- Only valid string values in `categoryPath` array create categories

**File Count**: 8 JSON files with ~500 products each (4,000 total)
**Category Count**: 80 unique hierarchical categories (actual data)
**Depth Levels**: 3 (parent, child, grandchild)

### Implementation Approach

**Option A: Node.js Script (Recommended for MVP)**
```javascript
// Example category extraction
const extractCategories = (jsonFiles) => {
  const categories = new Map();
  
  jsonFiles.forEach(file => {
    const products = JSON.parse(fs.readFileSync(file));
    products.forEach(product => {
      const path = product.categoryPath; // ['Food', 'Dairy', 'Cheese']
      buildCategoryTree(categories, path);
    });
  });
  
  return categories;
};
```

**Option B: PHP Artisan Command**
```php
// Laravel command for Fleetbase-native approach
class ImportCategoriesCommand extends Command
{
    protected $signature = 'import:categories {--dry-run}';
    // Use Fleetbase SDK or HTTP client
}
```

### Fleetbase Category API

**Endpoint**: `POST /storefront/v1/categories`

**Request Body**:
```json
{
  "name": "Dairy",
  "parent_uuid": "uuid-of-food-category", // null for top level
  "description": "Dairy products",
  "slug": "dairy"
}
```

**Response**:
```json
{
  "data": {
    "uuid": "generated-uuid",
    "name": "Dairy",
    "parent_uuid": "uuid-of-food-category"
  }
}
```

**Find-or-Create Pattern**:
1. Query existing categories by name
2. If exists, use existing UUID
3. If not exists, create new category
4. Store mapping (name → UUID)

### Category Tree Example

```
Food (uuid: abc-123)
├── Dairy (uuid: def-456, parent: abc-123)
│   ├── Cheese (uuid: ghi-789, parent: def-456)
│   ├── Milk (uuid: jkl-012, parent: def-456)
│   └── Yogurt (uuid: mno-345, parent: def-456)
├── Produce (uuid: pqr-678, parent: abc-123)
│   ├── Fruits (uuid: stu-901, parent: pqr-678)
│   └── Vegetables (uuid: vwx-234, parent: pqr-678)
└── Meat (uuid: yzA-567, parent: abc-123)
    ├── Beef (uuid: BCD-890, parent: yzA-567)
    └── Poultry (uuid: EFG-123, parent: yzA-567)
```

### Mapping File Format

**`product-importer/category-mapping.json`**:
```json
{
  "version": "1.0",
  "generated_at": "2026-02-17T10:00:00Z",
  "total_categories": 42,
  "categories": {
    "Food": {
      "uuid": "abc-123",
      "level": 1,
      "parent_uuid": null
    },
    "Dairy": {
      "uuid": "def-456",
      "level": 2,
      "parent_uuid": "abc-123"
    },
    "Cheese": {
      "uuid": "ghi-789",
      "level": 3,
      "parent_uuid": "def-456"
    }
  }
}
```

### Project Structure Notes

**New Directory Structure:**
```
product-importer/
├── src/
│   ├── services/
│   │   └── CategoryImportService.js
│   ├── utils/
│   │   ├── logger.js
│   │   └── file-reader.js
│   └── models/
│       └── category-tree.js
├── scripts/
│   └── import-categories.js
├── json/
│   ├── products_1.json
│   ├── products_2.json
│   └── ... (8 files total)
├── tests/
│   └── unit/
│       └── category-import.test.js
├── category-mapping.json (generated output)
└── package.json
```

### Configuration

**Environment Variables:**
```bash
FLEETBASE_API_URL=https://api.fleetbase.io
FLEETBASE_API_KEY=your-storefront-api-key
FLEETBASE_NETWORK_ID=stalabard-dao-marketplace
JSON_INPUT_DIR=./json
CATEGORY_MAPPING_OUTPUT=./category-mapping.json
DRY_RUN=false
VERBOSE=true
```

### Error Handling

**Expected Errors:**
1. **JSON Parse Error**: Malformed JSON file
   - Log file name and line number
   - Skip file and continue

2. **Fleetbase API Error**: Rate limiting, auth failure, validation error
   - Implement exponential backoff retry (3 attempts)
   - Log error details
   - Save progress state for resume

3. **Category Name Collision**: Same name at different paths
   - Use full path as key: "Food > Dairy > Cheese"
   - Include slug generation with parent prefix

4. **Missing categoryPath**: Product without category data
   - Log warning with product identifier
   - Assign to default "Uncategorized" category

### Testing Standards

**Unit Tests:**
```javascript
// Test category tree building
test('builds correct 3-level hierarchy', () => {
  const paths = [
    ['Food', 'Dairy', 'Cheese'],
    ['Food', 'Dairy', 'Milk'],
    ['Food', 'Produce', 'Fruits']
  ];
  const tree = buildCategoryTree(paths);
  expect(tree).toHaveLength(1); // Food
  expect(tree[0].children).toHaveLength(2); // Dairy, Produce
  expect(tree[0].children[0].children).toHaveLength(2); // Cheese, Milk
});
```

**Integration Test:**
```javascript
// Test Fleetbase API integration
test('creates categories in Fleetbase', async () => {
  const service = new CategoryImportService();
  const result = await service.importCategories();
  expect(result.totalCreated).toBe(42);
  expect(fs.existsSync('./category-mapping.json')).toBe(true);
});
```

### Performance Considerations

- **Batch Processing**: Create categories in batches to avoid API rate limits
- **Progress Saving**: Save state after each batch for resume capability
- **Memory Management**: Stream JSON files for large datasets
- **Parallel Processing**: Create sibling categories in parallel (same parent level)

### Dependencies

**Required:**
- `axios` or `node-fetch` for Fleetbase API calls
- `fs` for file operations
- `path` for path handling
- `dotenv` for environment variables

**Optional:**
- `commander` for CLI arguments
- `winston` or `pino` for structured logging
- `jest` for testing
- `slugify` for category slug generation

### References

**Source: epics.md#Epic 2: Product Catalog Import**
- Story 2.1: Category Hierarchy Import Script
- Acceptance criteria for 42 category creation with 3-level hierarchy
- JSON input format and Fleetbase API integration

**Source: architecture.md#3.2 Fleetbase Network Configuration**
- Fleetbase Storefront API patterns
- Network and Store architecture
- API key authentication

**Source: architecture.md#9.3 Performance**
- Batch processing guidelines
- Rate limiting considerations
- Query optimization patterns

## Dev Agent Record

### Agent Model Used

Claude 3.5 Sonnet (Coding Agent)

### Implementation Plan

**Approach:** Node.js script with TDD (Test-Driven Development)

**Key Implementation Details:**
1. **CategoryImportService** - Core service class handling:
   - JSON file scanning and product loading
   - Category path extraction from `categoryPath` arrays
   - Hierarchical tree building (parent → child → grandchild)
   - Fleetbase API integration with find-or-create pattern
   - Category mapping generation

2. **Slug Generation** - Uses slugify library for URL-safe category slugs with parent prefix

3. **Idempotency** - Find-or-create pattern checks existing categories before creation

4. **Testing** - 12 unit tests covering:
   - Category extraction and deduplication
   - Tree building with correct hierarchy
   - API integration with mocked responses
   - Slug generation with special characters

5. **CLI Features**:
   - Dry-run mode for testing without API calls
   - Verbose logging for debugging
   - Progress tracking and error reporting
   - Completion summary

**Actual Results:**
- Discovered 80 unique categories (vs expected 42)
- All 8 JSON files processed successfully
- Category mapping generated for Story 2.3 use

### Debug Log References

### Completion Notes List

- [x] CategoryImportService implemented with full functionality
- [x] Category tree builder working (3-level hierarchy) - extracts and builds hierarchical structure
- [x] Fleetbase API integration complete with find-or-create pattern for idempotency
- [x] Category mapping JSON output generated (category-mapping.json with 80 categories from actual data)
- [x] CLI script entry point working with dry-run and verbose modes
- [x] Unit tests passing (14/14 tests pass) - including strict validation tests
- [x] Dry-run test completed successfully with actual JSON files
- [x] 80 categories discovered and processed (more than expected 42 due to richer actual data)
- [x] Error handling implemented with graceful fallbacks and logging
- [x] **STRICT VALIDATION**: Only `categoryPath` field used - breadcrumb and other fields ignored

### File List

**New Files:**
1. `product-importer/src/services/CategoryImportService.js` - Main service class for category import
2. `product-importer/src/utils/logger.js` - Winston-based logging utility
3. `product-importer/scripts/import-categories.js` - CLI entry point with Commander.js
4. `product-importer/tests/unit/category-import.test.js` - Jest unit tests (12 tests)
5. `product-importer/package.json` - Dependencies and scripts
6. `product-importer/jest.config.js` - Jest configuration for ESM
7. `product-importer/.env` - Environment configuration
8. `product-importer/.env.example` - Environment template
9. `product-importer/category-mapping.json` - Generated output (80 categories)

**Modified Files:**
- None (standalone import tool)

### Change Log

- **2026-02-17**: Story 2.1 implementation completed
  - Created CategoryImportService with full category import functionality
  - Implemented 3-level hierarchical tree builder
  - Added Fleetbase API integration with find-or-create pattern
  - Created CLI script with dry-run and verbose modes
  - Added 14 unit tests (all passing)
  - Generated category mapping for 80 categories from actual JSON data
  - Verified with successful dry-run test
  - **Enhanced with strict validation**: Only `categoryPath` field creates categories
    - Ignores `breadcrumb` field (even though identical to categoryPath)
    - Skips empty, whitespace-only, or null category names
    - Added validation tests to ensure no other fields accidentally create categories

### Commands to Verify

```bash
# Install dependencies
cd product-importer && npm install

# Run category import (dry-run)
npm run import:categories -- --dry-run

# Run category import (live)
npm run import:categories

# Run tests
npm test

# Verify output
cat category-mapping.json | jq '.total_categories'
```
