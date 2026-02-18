# Story 2.3: Product Import Script - Core Engine

Status: ready-for-dev

## Story

As a platform operator,
I want to import 4,000 products with variants and metadata,
so that the marketplace is populated with the complete catalog.

## Acceptance Criteria

### AC1: Data Loading and Mapping
**Given** category mapping and image mapping files exist
**When** I run the product import script
**Then** Script reads all 8 product JSON files
**And** Script loads category UUID mapping from Story 2.1
**And** Script loads image URL mapping from Story 2.2

### AC2: Product Creation with Variants and Categories
**Given** product creation process
**When** Script creates each product
**Then** Products are created via Fleetbase Storefront API
**And** Products include variants (SKU, price, inventory)
**And** Products are linked to correct categories via UUID
**And** Product images referenced by Fleetbase URLs

### AC3: Metadata Preservation and Tagging
**Given** product metadata
**When** Products are imported
**Then** Metadata is preserved (ingredients, nutrition, allergens, origin)
**And** Tags are created from brand names
**And** Product status is set to 'published'

### AC4: Batch Processing with Resume Capability
**Given** import processing
**When** Script processes products
**Then** Script processes in batches of 100 products
**And** Script implements 1-2 second delay between batches
**And** Script saves progress state for resume capability
**And** Script handles API errors with retry logic (3 attempts)

### AC5: Import Completion and Reporting
**Given** import completes
**When** Script finishes execution
**Then** All 4,000 products successfully imported
**And** Script generates import report (success/failure counts)
**And** State file saved: `product-importer/import-state.json`
**And** Error log saved: `product-importer/import-errors.log`

## Tasks / Subtasks

- [ ] **Task 1: Create ProductImportService class** (AC: 1, 2, 3, 4)
  - [ ] Create service class at `product-importer/src/services/ProductImportService.js`
  - [ ] Implement mapping file loaders (category + image)
  - [ ] Implement JSON file reader for 8 product files
  - [ ] Add Fleetbase Storefront API integration
  - [ ] Add batch processing orchestration
  - [ ] Add state management for resume capability

- [ ] **Task 2: Implement product data transformer** (AC: 2, 3)
  - [ ] Transform source JSON to Fleetbase Product API format
  - [ ] Map category paths to Fleetbase category UUIDs
  - [ ] Map local image paths to Fleetbase URLs
  - [ ] Build variant data structure (SKU, price, inventory)
  - [ ] Extract and normalize metadata fields
  - [ ] Generate brand tags from product data

- [ ] **Task 3: Implement product creation logic** (AC: 2)
  - [ ] Create product via Fleetbase Storefront API
  - [ ] Create product variants with proper structure
  - [ ] Link product to category by UUID
  - [ ] Attach product images by Fleetbase URL
  - [ ] Set product status to 'published'
  - [ ] Handle product creation errors

- [ ] **Task 4: Implement batch processing** (AC: 4)
  - [ ] Configure batch size (default: 100 products)
  - [ ] Process batches sequentially
  - [ ] Add 1-2 second delay between batches
  - [ ] Track progress across batches
  - [ ] Save state after each batch
  - [ ] Support resume from saved state

- [ ] **Task 5: Implement retry and error handling** (AC: 4)
  - [ ] Add retry mechanism with exponential backoff
  - [ ] Maximum 3 retry attempts per product
  - [ ] Log failed products with error details
  - [ ] Continue processing remaining products
  - [ ] Categorize errors (API, validation, network)

- [ ] **Task 6: Create import state and error files** (AC: 4, 5)
  - [ ] Generate `product-importer/import-state.json`
  - [ ] Track processed products, current batch, progress
  - [ ] Generate `product-importer/import-errors.log`
  - [ ] Log errors with timestamps and context
  - [ ] Include failed product IDs for retry

- [ ] **Task 7: Create import report** (AC: 5)
  - [ ] Generate `product-importer/import-report.json`
  - [ ] Include success/failure counts
  - [ ] Include timing statistics
  - [ ] List failed products with reasons
  - [ ] Include recommendations for retry

- [ ] **Task 8: Add CLI script entry point** (AC: 1, 4, 5)
  - [ ] Create `product-importer/scripts/import-products.js`
  - [ ] Add CLI arguments (--batch-size, --dry-run, --verbose, --resume)
  - [ ] Add progress output ("500/4000 products imported")
  - [ ] Add ETA calculation
  - [ ] Add completion summary

- [ ] **Task 9: Create unit tests** (AC: 2, 3, 4)
  - [ ] Test product data transformation
  - [ ] Test category UUID mapping
  - [ ] Test image URL mapping
  - [ ] Test variant structure generation
  - [ ] Test batch processing logic
  - [ ] Mock Fleetbase API responses

## Dev Notes

### Architecture Context

This is the **core product import story** and the culmination of Epic 2. It depends on outputs from Stories 2.1 (category mapping) and 2.2 (image mapping) to create 4,000 products in Fleetbase with complete data.

**Dependencies:**
- **Story 2.1 Output**: `category-mapping.json` (category name → Fleetbase UUID)
- **Story 2.2 Output**: `image-url-mapping.json` (local path → Fleetbase URL)
- **Input Data**: 8 JSON files in `product-importer/json/` (~500 products each)

**Key Architectural Patterns:**
- **Data Transformation**: Convert source JSON format to Fleetbase API format
- **Reference Mapping**: Use category UUIDs and image URLs from previous stories
- **Batch Processing**: 100 products per batch with delays
- **Resume Capability**: Save state after each batch for crash recovery
- **Idempotent Creation**: Track created products to avoid duplicates

### JSON Input Structure

**Source Product JSON Format:**
```json
{
  "id": "prod_001",
  "name": "Organic Whole Milk",
  "description": "Fresh organic whole milk from local farms...",
  "categoryPath": ["Food", "Dairy", "Milk"],
  "brand": "Green Valley Dairy",
  "images": [
    "uploads/product_001/main.jpg",
    "uploads/product_001/side.jpg"
  ],
  "variants": [
    {
      "sku": "GV-MILK-001-1L",
      "size": "1 Liter",
      "price": 4.99,
      "currency": "USD",
      "inventory": 50
    },
    {
      "sku": "GV-MILK-001-2L",
      "size": "2 Liter",
      "price": 8.99,
      "currency": "USD",
      "inventory": 30
    }
  ],
  "metadata": {
    "ingredients": "Organic whole milk",
    "nutrition_facts": "Calories: 150 per serving...",
    "allergens": ["milk"],
    "origin": "Local Farm Cooperative",
    "organic": true,
    "shelf_life_days": 14
  }
}
```

**Input Files:**
- `product-importer/json/products_1.json` (~500 products)
- `product-importer/json/products_2.json` (~500 products)
- ... through `products_8.json`
- **Total**: ~4,000 products across 8 files

### Fleetbase Product API

**Endpoint**: `POST /storefront/v1/products`

**Request Body:**
```json
{
  "name": "Organic Whole Milk",
  "description": "Fresh organic whole milk from local farms...",
  "status": "published",
  "category_uuid": "abc-123-def-456",  // From category-mapping.json
  "tags": ["Green Valley Dairy", "organic", "dairy"],
  "images": [
    {
      "url": "https://cdn.fleetbase.io/public/file-uuid-1.jpg",
      "order": 1
    },
    {
      "url": "https://cdn.fleetbase.io/public/file-uuid-2.jpg",
      "order": 2
    }
  ],
  "variants": [
    {
      "name": "1 Liter",
      "sku": "GV-MILK-001-1L",
      "price": 4.99,
      "currency": "USD",
      "quantity": 50
    },
    {
      "name": "2 Liter",
      "sku": "GV-MILK-001-2L",
      "price": 8.99,
      "currency": "USD",
      "quantity": 30
    }
  ],
  "meta": {
    "ingredients": "Organic whole milk",
    "nutrition_facts": "Calories: 150 per serving...",
    "allergens": ["milk"],
    "origin": "Local Farm Cooperative",
    "organic": true,
    "shelf_life_days": 14
  }
}
```

**Response:**
```json
{
  "data": {
    "uuid": "product-uuid-in-fleetbase",
    "public_id": "prod_public_id",
    "name": "Organic Whole Milk",
    "status": "published",
    "category_uuid": "abc-123-def-456",
    "variants": [...],
    "images": [...]
  }
}
```

### Data Transformation Logic

**Category Mapping:**
```javascript
function mapCategory(categoryPath, categoryMapping) {
  // categoryPath: ["Food", "Dairy", "Milk"]
  // Use the deepest (most specific) category for the product
  const deepestCategory = categoryPath[categoryPath.length - 1];
  
  const mapping = categoryMapping.categories[deepestCategory];
  if (!mapping) {
    throw new Error(`Category not found: ${deepestCategory}`);
  }
  
  return mapping.uuid;
}
```

**Image Mapping:**
```javascript
function mapImages(productImages, imageMapping) {
  return productImages.map((localPath, index) => {
    const mapping = imageMapping.mappings[localPath];
    if (!mapping) {
      console.warn(`Image not found in mapping: ${localPath}`);
      return null;
    }
    
    return {
      url: mapping.url,
      order: index + 1
    };
  }).filter(img => img !== null);
}
```

**Variant Transformation:**
```javascript
function transformVariants(variants) {
  return variants.map(variant => ({
    name: variant.size || 'Default',
    sku: variant.sku,
    price: variant.price,
    currency: variant.currency || 'USD',
    quantity: variant.inventory || 0
  }));
}
```

**Tag Generation:**
```javascript
function generateTags(product) {
  const tags = [];
  
  // Brand tag
  if (product.brand) {
    tags.push(product.brand);
  }
  
  // Metadata-based tags
  if (product.metadata?.organic) {
    tags.push('organic');
  }
  if (product.metadata?.gluten_free) {
    tags.push('gluten-free');
  }
  
  // Category-based tags
  tags.push(...product.categoryPath);
  
  return [...new Set(tags)]; // Remove duplicates
}
```

### Batch Processing

**Configuration:**
```javascript
const BATCH_SIZE = 100; // products per batch
const BATCH_DELAY_MS = 1500; // 1.5 seconds between batches
const MAX_RETRIES = 3;
```

**Processing Logic:**
```javascript
async importProducts() {
  // Load all products from 8 JSON files
  const allProducts = await this.loadAllProductFiles();
  
  // Load mappings
  const categoryMapping = await this.loadCategoryMapping();
  const imageMapping = await this.loadImageMapping();
  
  // Check for resume state
  const startIndex = await this.getResumeIndex();
  const productsToProcess = allProducts.slice(startIndex);
  
  // Split into batches
  const batches = chunkArray(productsToProcess, BATCH_SIZE);
  
  const results = {
    total: allProducts.length,
    processed: startIndex,
    successful: 0,
    failed: 0,
    errors: []
  };
  
  for (let i = 0; i < batches.length; i++) {
    const batchNumber = Math.floor(startIndex / BATCH_SIZE) + i + 1;
    console.log(`[${batchNumber}/${Math.ceil(allProducts.length / BATCH_SIZE)}] Processing batch...`);
    
    const batchResults = await this.processBatch(
      batches[i],
      categoryMapping,
      imageMapping
    );
    
    results.successful += batchResults.successful;
    results.failed += batchResults.failed;
    results.errors.push(...batchResults.errors);
    results.processed += batches[i].length;
    
    // Save progress state
    await this.saveProgressState(results.processed);
    
    // Delay between batches
    if (i < batches.length - 1) {
      await sleep(BATCH_DELAY_MS);
    }
  }
  
  return results;
}
```

**Batch Processing with Concurrency:**
```javascript
async processBatch(products, categoryMapping, imageMapping) {
  const results = { successful: 0, failed: 0, errors: [] };
  
  // Process with limited concurrency (5 at a time)
  const CONCURRENCY = 5;
  
  await pMap(products, async (product) => {
    try {
      await this.importProduct(product, categoryMapping, imageMapping);
      results.successful++;
    } catch (error) {
      results.failed++;
      results.errors.push({
        product_id: product.id,
        product_name: product.name,
        error: error.message,
        timestamp: new Date().toISOString()
      });
    }
  }, { concurrency: CONCURRENCY });
  
  return results;
}
```

### Retry Logic

```javascript
async importProduct(product, categoryMapping, imageMapping, attempt = 1) {
  try {
    const transformed = this.transformProduct(product, categoryMapping, imageMapping);
    return await this.fleetbaseAPI.createProduct(transformed);
  } catch (error) {
    // Don't retry validation errors
    if (error.status === 422) {
      throw error;
    }
    
    if (attempt >= MAX_RETRIES) {
      throw new Error(`Failed after ${MAX_RETRIES} attempts: ${error.message}`);
    }
    
    // Exponential backoff
    const delay = Math.pow(2, attempt) * 1000;
    console.log(`Retry ${attempt}/${MAX_RETRIES} for ${product.id} after ${delay}ms`);
    await sleep(delay);
    
    return this.importProduct(product, categoryMapping, imageMapping, attempt + 1);
  }
}
```

### State Management

**Resume State File (`import-state.json`):**
```json
{
  "version": "1.0",
  "started_at": "2026-02-17T10:00:00Z",
  "last_updated": "2026-02-17T10:25:30Z",
  "current_batch": 25,
  "total_batches": 40,
  "progress": {
    "total": 4000,
    "processed": 2500,
    "successful": 2495,
    "failed": 5
  },
  "processed_product_ids": [
    "prod_001",
    "prod_002",
    ...
  ],
  "failed_products": [
    {
      "id": "prod_042",
      "name": "Failed Product",
      "error": "Category not found: UnknownCategory"
    }
  ]
}
```

**Idempotency Check:**
```javascript
async shouldProcessProduct(productId) {
  const state = await this.loadState();
  
  // Check if already processed successfully
  if (state.processed_product_ids.includes(productId)) {
    return false; // Skip
  }
  
  // Check if in failed list (allow retry)
  const failedProduct = state.failed_products.find(p => p.id === productId);
  if (failedProduct) {
    console.log(`Retrying failed product: ${productId}`);
    return true;
  }
  
  return true;
}
```

### Project Structure

```
product-importer/
├── src/
│   ├── services/
│   │   ├── CategoryImportService.js     (Story 2.1)
│   │   ├── ImageMigrationService.js    (Story 2.2)
│   │   └── ProductImportService.js     <-- NEW
│   ├── utils/
│   │   ├── logger.js
│   │   ├── file-reader.js
│   │   └── batch-processor.js
│   ├── transformers/
│   │   └── product-transformer.js      <-- NEW
│   └── models/
│       ├── category-tree.js
│       ├── image-file.js
│       └── product.js                  <-- NEW
├── scripts/
│   ├── import-categories.js            (Story 2.1)
│   ├── migrate-images.js               (Story 2.2)
│   └── import-products.js              <-- NEW
├── json/                               (source data)
│   ├── products_1.json
│   └── ...
├── tests/
│   └── unit/
│       ├── category-import.test.js
│       ├── image-migration.test.js
│       └── product-import.test.js       <-- NEW
├── category-mapping.json               (from Story 2.1)
├── image-url-mapping.json              (from Story 2.2)
├── import-state.json                   (generated)
├── import-errors.log                   (generated)
├── import-report.json                  (generated)
└── package.json
```

### Configuration

**Environment Variables:**
```bash
# Fleetbase Configuration
FLEETBASE_API_URL=https://api.fleetbase.io
FLEETBASE_API_KEY=your-storefront-api-key
FLEETBASE_NETWORK_ID=stalabard-dao-marketplace

# Input Data
JSON_INPUT_DIR=./json
CATEGORY_MAPPING_FILE=./category-mapping.json
IMAGE_MAPPING_FILE=./image-url-mapping.json

# Output Files
IMPORT_STATE_FILE=./import-state.json
IMPORT_ERRORS_FILE=./import-errors.log
IMPORT_REPORT_FILE=./import-report.json

# Processing Settings
BATCH_SIZE=100
BATCH_DELAY_MS=1500
MAX_RETRIES=3
CONCURRENCY=5

# Runtime Options
DRY_RUN=false
VERBOSE=true
RESUME=false
```

### Error Handling

**Error Categories:**

1. **Category Not Found**: Category from product not in mapping
   - Log warning with product ID and category path
   - Assign to default category or skip product
   - Include in error log

2. **Image Not Found**: Product image not in image mapping
   - Log warning
   - Create product without that image
   - Continue with other images

3. **API Validation Error**: Fleetbase rejects product data
   - Don't retry (permanent error)
   - Log full error response
   - Include in failed products list

4. **Network/Rate Limit Error**: Temporary API issues
   - Retry with exponential backoff (3 attempts)
   - Log retry attempts
   - Include in error log if all retries fail

5. **SKU Conflict**: Duplicate SKU in import
   - Log error with product IDs
   - Skip duplicate
   - Include in error log

**Error Log Format (`import-errors.log`):**
```
[2026-02-17T10:15:30Z] ERROR: Failed to import product prod_042
  Product: "Unknown Brand Item"
  Error: Category not found: UnknownCategory
  Category Path: ["Food", "Unknown", "Item"]

[2026-02-17T10:16:45Z] ERROR: Failed to import product prod_189
  Product: "Duplicate SKU Item"
  Error: SKU already exists: DUP-SKU-001
  Retry attempts: 3/3
```

### Import Report Format

**`import-report.json`:**
```json
{
  "version": "1.0",
  "generated_at": "2026-02-17T11:00:00Z",
  "summary": {
    "total_products": 4000,
    "successful": 3992,
    "failed": 8,
    "success_rate": "99.8%"
  },
  "timing": {
    "started_at": "2026-02-17T10:00:00Z",
    "completed_at": "2026-02-17T11:00:00Z",
    "duration_minutes": 60,
    "average_per_product_ms": 900
  },
  "categories_used": {
    "Dairy": 450,
    "Produce": 380,
    "Meat": 320,
    ...
  },
  "failed_products": [
    {
      "id": "prod_042",
      "name": "Unknown Brand Item",
      "error": "Category not found: UnknownCategory",
      "recommendation": "Add category to mapping or fix source data"
    },
    {
      "id": "prod_189",
      "name": "Duplicate SKU Item",
      "error": "SKU already exists: DUP-SKU-001",
      "recommendation": "Generate unique SKU or skip product"
    }
  ],
  "retry_recommendations": [
    "Run with --resume to retry failed products after fixing data",
    "Check import-errors.log for detailed error messages",
    "Verify category-mapping.json has all required categories"
  ]
}
```

### Progress Tracking

**Console Output:**
```
Product Import Started
Total products: 4,000
Batch size: 100
Estimated time: ~60 minutes

Loading mappings...
✓ Category mapping: 42 categories loaded
✓ Image mapping: 4,610 images loaded

[1/40] Processing batch... (100 products)
Batch complete: 100 successful, 0 failed
Progress: 100/4000 (2.5%) | ETA: 58 minutes

[2/40] Processing batch... (100 products)
Batch complete: 99 successful, 1 failed
Progress: 200/4000 (5.0%) | ETA: 57 minutes
Warning: 1 product failed (see import-errors.log)
...

[40/40] Processing batch... (100 products)
Batch complete: 98 successful, 2 failed
Progress: 4000/4000 (100%) | ETA: 0 minutes

Import Complete!
Total: 4,000 | Successful: 3,992 | Failed: 8
Success rate: 99.8%
Duration: 60 minutes

Report saved to: import-report.json
Errors saved to: import-errors.log
State saved to: import-state.json
```

### Performance Considerations

- **Memory Management**: Stream JSON files instead of loading all at once
  ```javascript
  const stream = fs.createReadStream(filePath);
  const parser = JSONStream.parse('*');
  stream.pipe(parser);
  ```

- **Concurrent Processing**: Process 5 products concurrently within batch
  - Balance speed vs API rate limits
  - Prevents memory issues with large batches

- **Progressive State Saving**: Save state after each batch
  - Prevents data loss on crash
  - Allows resume without reprocessing

- **Batch Delays**: 1.5 second delay between batches
  - Respects API rate limits
  - Prevents server overload

### Testing

**Unit Tests:**
```javascript
// Test product transformation
test('transforms product data correctly', () => {
  const source = loadTestProduct();
  const categoryMapping = loadTestCategoryMapping();
  const imageMapping = loadTestImageMapping();
  
  const transformed = transformProduct(source, categoryMapping, imageMapping);
  
  expect(transformed.name).toBe(source.name);
  expect(transformed.category_uuid).toBeDefined();
  expect(transformed.variants).toHaveLength(source.variants.length);
  expect(transformed.images).toHaveLength(source.images.length);
});

// Test batch processing
test('processes products in correct batch sizes', () => {
  const products = Array(250).fill({ id: 'test' });
  const batches = chunkArray(products, 100);
  
  expect(batches).toHaveLength(3);
  expect(batches[0]).toHaveLength(100);
  expect(batches[2]).toHaveLength(50);
});

// Test category mapping
test('maps category path to UUID', () => {
  const path = ["Food", "Dairy", "Milk"];
  const mapping = {
    categories: {
      "Milk": { uuid: "milk-uuid", level: 3 }
    }
  };
  
  const uuid = mapCategory(path, mapping);
  expect(uuid).toBe("milk-uuid");
});
```

**Integration Test:**
```javascript
test('imports sample products to Fleetbase', async () => {
  const service = new ProductImportService();
  
  // Create test mappings
  const testCategoryMapping = { /* ... */ };
  const testImageMapping = { /* ... */ };
  
  fs.writeFileSync('category-mapping.json', JSON.stringify(testCategoryMapping));
  fs.writeFileSync('image-url-mapping.json', JSON.stringify(testImageMapping));
  
  const result = await service.importProducts('./test-json');
  
  expect(result.total).toBe(10);
  expect(result.successful).toBeGreaterThan(0);
  expect(fs.existsSync('./import-report.json')).toBe(true);
});
```

### References

**Source: epics.md#Epic 2: Product Catalog Import**
- Story 2.3: Product Import Script - Core Engine
- 4,000 product import with variants and metadata
- Batch processing and resume capability

**Source: _bmad-output/implementation-artifacts/2-1-category-hierarchy-import-script.md**
- Category mapping file format and usage
- Project structure patterns

**Source: _bmad-output/implementation-artifacts/2-2-image-migration-to-fleetbase-storage.md**
- Image mapping file format and usage
- Batch processing patterns
- State management approach

## Dev Agent Record

### Agent Model Used

Claude 3.5 Sonnet (Coding Agent)

### Debug Log References

### Completion Notes List

- [ ] ProductImportService implemented
- [ ] Product data transformer working (source JSON → Fleetbase format)
- [ ] Category UUID mapping integrated
- [ ] Image URL mapping integrated
- [ ] Variant structure generation working
- [ ] Fleetbase Storefront API integration complete
- [ ] Batch processing (100 products) with delays
- [ ] Retry logic with exponential backoff (3 attempts)
- [ ] Resume capability from saved state
- [ ] Import state file generation
- [ ] Error log generation
- [ ] Import report generation
- [ ] CLI script entry point working
- [ ] Unit tests passing
- [ ] Integration test with Fleetbase passing
- [ ] 4,000 products successfully imported
- [ ] ≥99% success rate achieved

### File List

**New Files:**
1. `product-importer/src/services/ProductImportService.js`
2. `product-importer/src/transformers/product-transformer.js`
3. `product-importer/src/models/product.js`
4. `product-importer/scripts/import-products.js`
5. `product-importer/tests/unit/product-import.test.js`
6. `product-importer/import-state.json` (generated)
7. `product-importer/import-errors.log` (generated)
8. `product-importer/import-report.json` (generated)

**Shared Files (from Stories 2.1, 2.2):**
- `product-importer/src/utils/logger.js`
- `product-importer/src/utils/batch-processor.js`
- `product-importer/src/utils/file-reader.js`
- `product-importer/package.json`
- `product-importer/.env.example`

**Input Files (required):**
- `product-importer/json/products_*.json` (8 files)
- `product-importer/category-mapping.json` (from Story 2.1)
- `product-importer/image-url-mapping.json` (from Story 2.2)

### Commands to Verify

```bash
# Prerequisites: Stories 2.1 and 2.2 must be complete
# category-mapping.json and image-url-mapping.json must exist

# Install dependencies
cd product-importer && npm install

# Run product import (dry-run to preview)
npm run import:products -- --dry-run

# Run full product import
npm run import:products

# Resume from interruption
npm run import:products -- --resume

# Custom batch size
npm run import:products -- --batch-size=50

# Run tests
npm test

# Verify results
cat import-report.json | jq '.summary'
cat import-report.json | jq '.success_rate'
tail -f import-errors.log
```
