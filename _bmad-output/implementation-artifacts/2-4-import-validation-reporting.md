# Story 2.4: Import Validation & Reporting

Status: ready-for-dev

## Story

As a platform operator,
I want to validate the product import and generate reports,
so that I can verify data integrity and identify any issues.

## Acceptance Criteria

### AC1: Product Count Validation
**Given** product import has completed
**When** I run the validation script
**Then** Script queries Fleetbase API for total product count
**And** Script verifies all 4,000 products imported

### AC2: Data Integrity Checks
**Given** validation checks
**When** Script validates imported data
**Then** Script checks category assignments (all products have categories)
**And** Script validates image URLs (all images accessible)
**And** Script verifies variant data (SKU, price, inventory present)

### AC3: Report Generation
**Given** validation completes
**When** Script generates report
**Then** Script generates import summary report (Markdown)
**And** Report includes: total products, categories, images, errors
**And** Script identifies missing/failed products
**And** Script generates retry list for failed products

### AC4: Success Rate Validation
**Given** report is generated
**When** I view the report
**Then** Report saved: `product-importer/import-report.md`
**And** Report shows ≥99% product import success rate
**And** Report shows 100% category and image migration success

## Tasks / Subtasks

- [ ] **Task 1: Create ImportValidationService class** (AC: 1, 2)
  - [ ] Create service class at `product-importer/src/services/ImportValidationService.js`
  - [ ] Implement Fleetbase API queries for product count
  - [ ] Implement data integrity check methods
  - [ ] Add validation result aggregation

- [ ] **Task 2: Implement product count validation** (AC: 1)
  - [ ] Query Fleetbase API for total product count
  - [ ] Compare against expected 4,000 products
  - [ ] Calculate completion percentage
  - [ ] Identify discrepancy if count doesn't match

- [ ] **Task 3: Implement category assignment validation** (AC: 2)
  - [ ] Query products without category assignments
  - [ ] Validate category UUID references exist
  - [ ] Check for orphaned products
  - [ ] Generate list of products needing category fix

- [ ] **Task 4: Implement image URL validation** (AC: 2)
  - [ ] Extract all image URLs from imported products
  - [ ] HTTP HEAD request to validate accessibility
  - [ ] Check for 404 or broken image links
  - [ ] Generate list of products with broken images

- [ ] **Task 5: Implement variant data validation** (AC: 2)
  - [ ] Query products and check variant presence
  - [ ] Validate SKU format and uniqueness
  - [ ] Verify price is present and positive
  - [ ] Verify inventory quantity is present
  - [ ] Generate list of products with incomplete variants

- [ ] **Task 6: Generate Markdown report** (AC: 3, 4)
  - [ ] Create `product-importer/import-report.md`
  - [ ] Include executive summary section
  - [ ] Include detailed validation results
  - [ ] Include recommendations section
  - [ ] Format with tables and charts representation

- [ ] **Task 7: Generate retry list** (AC: 3)
  - [ ] Create `product-importer/retry-list.json`
  - [ ] Include products needing category fix
  - [ ] Include products needing image fix
  - [ ] Include products with variant issues
  - [ ] Format for use with import script

- [ ] **Task 8: Add CLI script entry point** (AC: 1, 2, 3, 4)
  - [ ] Create `product-importer/scripts/validate-import.js`
  - [ ] Add CLI arguments (--skip-image-check, --verbose)
  - [ ] Add progress output during validation
  - [ ] Add report preview to console

- [ ] **Task 9: Create unit tests** (AC: 2, 3)
  - [ ] Test product count calculation
  - [ ] Test validation logic for each check type
  - [ ] Test report generation
  - [ ] Mock Fleetbase API responses

## Dev Notes

### Architecture Context

This is the **validation and quality assurance story** for Epic 2. It runs after the product import (Story 2.3) to verify that all 4,000 products were imported correctly with complete data.

**Dependencies:**
- **Story 2.3 Completion**: Products must be imported before validation
- **Fleetbase API Access**: Read-only queries to verify data
- **Input Files**: `import-state.json` and `import-report.json` from Story 2.3

**Key Architectural Patterns:**
- **Read-Only Validation**: No data modifications, only queries and reports
- **Comprehensive Checks**: Multiple validation dimensions (count, categories, images, variants)
- **Actionable Reports**: Generate specific lists for fixing issues
- **Success Metrics**: Clear pass/fail criteria (≥99% success rate)

### Fleetbase API Queries

**Product Count Query:**
```javascript
// GET /storefront/v1/products?limit=1
// Response includes meta.total for total count
const response = await fleetbaseAPI.get('/storefront/v1/products', {
  params: { limit: 1, status: 'published' }
});
const totalProducts = response.data.meta.total;
```

**Products Without Categories:**
```javascript
// Query products with null/undefined category_uuid
const products = await fleetbaseAPI.get('/storefront/v1/products', {
  params: { 
    limit: 100,
    'category_uuid:isNull': true 
  }
});
```

**Products with Variants:**
```javascript
// Get products and check variant presence
const product = await fleetbaseAPI.get(`/storefront/v1/products/${productId}`);
const hasVariants = product.data.variants && product.data.variants.length > 0;
```

### Validation Checks Implementation

**Product Count Check:**
```javascript
async validateProductCount(expectedCount = 4000) {
  const actualCount = await this.getProductCount();
  const percentage = (actualCount / expectedCount) * 100;
  
  return {
    check: 'Product Count',
    expected: expectedCount,
    actual: actualCount,
    percentage: percentage.toFixed(2),
    passed: actualCount >= expectedCount * 0.99, // ≥99%
    issues: actualCount < expectedCount 
      ? [`Missing ${expectedCount - actualCount} products`] 
      : []
  };
}
```

**Category Assignment Check:**
```javascript
async validateCategories() {
  const issues = [];
  let checked = 0;
  
  // Paginate through all products
  let page = 1;
  let hasMore = true;
  
  while (hasMore) {
    const response = await this.getProductsPage(page);
    const products = response.data;
    
    for (const product of products) {
      checked++;
      
      if (!product.category_uuid) {
        issues.push({
          product_id: product.public_id,
          product_name: product.name,
          issue: 'Missing category assignment',
          severity: 'high'
        });
      }
    }
    
    hasMore = products.length === 100; // If less than 100, we're done
    page++;
  }
  
  return {
    check: 'Category Assignments',
    checked,
    passed: issues.length === 0,
    issue_count: issues.length,
    issues: issues.slice(0, 100) // Limit to first 100 for report
  };
}
```

**Image URL Validation:**
```javascript
async validateImageUrls() {
  const issues = [];
  const batchSize = 50; // Check 50 images at a time
  
  // Get all products with images
  const products = await this.getAllProductsWithImages();
  const allImages = this.extractImageUrls(products);
  
  // Check images in batches
  const batches = chunkArray(allImages, batchSize);
  
  for (const batch of batches) {
    const checks = await Promise.all(
      batch.map(url => this.checkImageUrl(url))
    );
    
    for (const check of checks) {
      if (!check.accessible) {
        issues.push({
          image_url: check.url,
          status_code: check.statusCode,
          issue: 'Image not accessible',
          products_affected: check.products
        });
      }
    }
  }
  
  return {
    check: 'Image URLs',
    total_images: allImages.length,
    passed: issues.length === 0,
    issue_count: issues.length,
    issues
  };
}

async checkImageUrl(url) {
  try {
    // Use HEAD request to check without downloading
    const response = await axios.head(url, { timeout: 5000 });
    return {
      url,
      accessible: response.status === 200,
      statusCode: response.status
    };
  } catch (error) {
    return {
      url,
      accessible: false,
      statusCode: error.response?.status || 'ERROR',
      error: error.message
    };
  }
}
```

**Variant Data Validation:**
```javascript
async validateVariants() {
  const issues = [];
  const seenSkus = new Set();
  
  const products = await this.getAllProducts();
  
  for (const product of products) {
    const productIssues = [];
    
    // Check if product has variants
    if (!product.variants || product.variants.length === 0) {
      productIssues.push('No variants defined');
    } else {
      for (const variant of product.variants) {
        // Validate SKU
        if (!variant.sku) {
          productIssues.push('Missing SKU');
        } else if (seenSkus.has(variant.sku)) {
          productIssues.push(`Duplicate SKU: ${variant.sku}`);
        } else {
          seenSkus.add(variant.sku);
        }
        
        // Validate price
        if (variant.price === undefined || variant.price === null) {
          productIssues.push('Missing price');
        } else if (variant.price <= 0) {
          productIssues.push(`Invalid price: ${variant.price}`);
        }
        
        // Validate inventory
        if (variant.quantity === undefined || variant.quantity === null) {
          productIssues.push('Missing inventory quantity');
        }
      }
    }
    
    if (productIssues.length > 0) {
      issues.push({
        product_id: product.public_id,
        product_name: product.name,
        issues: productIssues,
        severity: productIssues.length > 2 ? 'high' : 'medium'
      });
    }
  }
  
  return {
    check: 'Variant Data',
    total_products: products.length,
    passed: issues.length === 0,
    issue_count: issues.length,
    issues: issues.slice(0, 100)
  };
}
```

### Report Generation

**Markdown Report Format (`import-report.md`):**
```markdown
# Product Import Validation Report

**Generated:** 2026-02-17 14:30:00 UTC  
**Validator:** ImportValidationService v1.0

---

## Executive Summary

| Metric | Value | Status |
|--------|-------|--------|
| Total Products Expected | 4,000 | - |
| Total Products Imported | 3,995 | ✓ |
| Success Rate | 99.88% | ✓ |
| Overall Status | **PASSED** | ✅ |

### Validation Results Summary

| Check | Status | Details |
|-------|--------|---------|
| Product Count | ✅ PASS | 3,995/4,000 (99.88%) |
| Category Assignments | ✅ PASS | All products have categories |
| Image URLs | ⚠️ WARN | 5 images not accessible |
| Variant Data | ✅ PASS | All SKUs valid |

---

## Detailed Validation Results

### 1. Product Count Validation

**Status:** ✅ PASSED (≥99% threshold met)

- **Expected:** 4,000 products
- **Actual:** 3,995 products
- **Missing:** 5 products
- **Success Rate:** 99.88%

The import achieved the required ≥99% success rate.

### 2. Category Assignments

**Status:** ✅ PASSED

All 3,995 imported products have valid category assignments.

- Products checked: 3,995
- Products with categories: 3,995
- Products missing categories: 0

### 3. Image URL Validation

**Status:** ⚠️ WARNING

- Total images checked: 4,605
- Accessible images: 4,600
- Broken images: 5
- Success Rate: 99.89%

#### Broken Images

| Image URL | Status | Products Affected |
|-----------|--------|-------------------|
| https://cdn.fleetbase.io/broken-1.jpg | 404 | prod_042, prod_189 |
| https://cdn.fleetbase.io/broken-2.jpg | 500 | prod_256 |
| ... | ... | ... |

**Recommendation:** Re-run image migration for affected products.

### 4. Variant Data Validation

**Status:** ✅ PASSED

All products have valid variant data with proper SKUs, prices, and inventory.

- Products with variants: 3,995
- Total variants: 8,450
- Duplicate SKUs: 0
- Missing prices: 0
- Missing inventory: 0

---

## Issues Summary

### Critical Issues (Require Immediate Fix)

None

### Warning Issues (Should Be Addressed)

1. **5 Broken Image URLs**
   - Impact: Products display without images
   - Action: Re-migrate images for affected products
   - Products affected: prod_042, prod_189, prod_256, prod_301, prod_445

### Missing Products

5 products from the source data were not successfully imported:

| Source ID | Product Name | Likely Cause |
|-----------|--------------|--------------|
| prod_0032 | "Unknown Brand Item" | Category not found |
| prod_0089 | "Duplicate SKU Item" | SKU conflict |
| ... | ... | ... |

---

## Recommendations

1. **Immediate Actions:**
   - Fix 5 broken image URLs by re-running image migration
   - Manually import 5 missing products after fixing source data

2. **Process Improvements:**
   - Add pre-validation for category existence before import
   - Implement SKU uniqueness check before product creation
   - Increase batch size for faster image migration

3. **Monitoring:**
   - Set up daily image URL health checks
   - Monitor product count for drift
   - Track variant data quality metrics

---

## Retry Instructions

To fix identified issues:

```bash
# Fix broken images
npm run migrate:images -- --retry-only

# Re-import missing products
npm run import:products -- --import-file=retry-list.json

# Re-validate after fixes
npm run validate:import
```

---

**Report End**
```

### Retry List Format

**`retry-list.json`:**
```json
{
  "generated_at": "2026-02-17T14:30:00Z",
  "summary": {
    "total_issues": 10,
    "products_to_retry": 8
  },
  "by_issue_type": {
    "category_missing": [
      {
        "source_id": "prod_0032",
        "name": "Unknown Brand Item",
        "category_path": ["Food", "Unknown", "Item"],
        "suggested_fix": "Add 'Unknown' category to mapping"
      }
    ],
    "broken_images": [
      {
        "source_id": "prod_0042",
        "name": "Product with broken image",
        "broken_image_urls": [
          "https://cdn.fleetbase.io/broken-1.jpg"
        ],
        "suggested_fix": "Re-upload image"
      }
    ],
    "variant_issues": [
      {
        "source_id": "prod_0089",
        "name": "Duplicate SKU Item",
        "issues": ["Duplicate SKU: DUP-SKU-001"],
        "suggested_fix": "Generate unique SKU"
      }
    ]
  },
  "combined_retry_list": [
    "prod_0032",
    "prod_0042",
    "prod_0089",
    ...
  ]
}
```

### Project Structure

```
product-importer/
├── src/
│   ├── services/
│   │   ├── CategoryImportService.js
│   │   ├── ImageMigrationService.js
│   │   ├── ProductImportService.js
│   │   └── ImportValidationService.js   <-- NEW
│   ├── validators/
│   │   ├── product-count.js           <-- NEW
│   │   ├── categories.js              <-- NEW
│   │   ├── images.js                <-- NEW
│   │   └── variants.js              <-- NEW
│   └── utils/
│       ├── logger.js
│       └── report-generator.js        <-- NEW
├── scripts/
│   ├── import-categories.js
│   ├── migrate-images.js
│   ├── import-products.js
│   └── validate-import.js             <-- NEW
├── tests/
│   └── unit/
│       └── import-validation.test.js  <-- NEW
├── import-report.md                   (generated)
└── retry-list.json                    (generated)
```

### Configuration

**Environment Variables:**
```bash
# Fleetbase API
FLEETBASE_API_URL=https://api.fleetbase.io
FLEETBASE_API_KEY=your-api-key

# Validation Settings
EXPECTED_PRODUCT_COUNT=4000
MIN_SUCCESS_RATE=99
IMAGE_CHECK_TIMEOUT=5000
VALIDATION_BATCH_SIZE=100

# Output Files
VALIDATION_REPORT_FILE=./import-report.md
RETRY_LIST_FILE=./retry-list.json

# Runtime Options
SKIP_IMAGE_CHECK=false
VERBOSE=true
```

### Performance Considerations

- **Pagination**: Query products in batches of 100 to avoid memory issues
- **Concurrent Image Checks**: Check 50 images concurrently to speed up validation
- **Early Exit**: Stop checking images after finding 100 broken ones (for reporting)
- **Caching**: Cache product data to avoid redundant API calls

### Testing

**Unit Tests:**
```javascript
test('validates product count correctly', async () => {
  const mockCount = 3995;
  jest.spyOn(service, 'getProductCount').mockResolvedValue(mockCount);
  
  const result = await service.validateProductCount(4000);
  
  expect(result.actual).toBe(3995);
  expect(result.percentage).toBe('99.88');
  expect(result.passed).toBe(true); // ≥99% passes
});

test('detects products without categories', async () => {
  const mockProducts = [
    { id: '1', category_uuid: 'cat-1' },
    { id: '2', category_uuid: null },
    { id: '3', category_uuid: 'cat-2' }
  ];
  jest.spyOn(service, 'getAllProducts').mockResolvedValue(mockProducts);
  
  const result = await service.validateCategories();
  
  expect(result.issue_count).toBe(1);
  expect(result.issues[0].product_id).toBe('2');
});
```

### References

**Source: epics.md#Epic 2: Product Catalog Import**
- Story 2.4: Import Validation & Reporting
- ≥99% success rate requirement
- Validation checks for categories, images, variants

**Source: _bmad-output/implementation-artifacts/2-3-product-import-script-core-engine.md**
- Import state file format
- Error logging patterns
- Report structure

## Dev Agent Record

### Agent Model Used

Claude 3.5 Sonnet (Coding Agent)

### Debug Log References

### Completion Notes List

- [ ] ImportValidationService implemented
- [ ] Product count validation working
- [ ] Category assignment validation working
- [ ] Image URL validation working
- [ ] Variant data validation working
- [ ] Markdown report generation working
- [ ] Retry list generation working
- [ ] CLI script entry point working
- [ ] Unit tests passing
- [ ] Integration test with Fleetbase passing
- [ ] ≥99% success rate validation working
- [ ] Report includes actionable recommendations

### File List

**New Files:**
1. `product-importer/src/services/ImportValidationService.js`
2. `product-importer/src/validators/product-count.js`
3. `product-importer/src/validators/categories.js`
4. `product-importer/src/validators/images.js`
5. `product-importer/src/validators/variants.js`
6. `product-importer/src/utils/report-generator.js`
7. `product-importer/scripts/validate-import.js`
8. `product-importer/tests/unit/import-validation.test.js`
9. `product-importer/import-report.md` (generated)
10. `product-importer/retry-list.json` (generated)

### Commands to Verify

```bash
# Run validation
npm run validate:import

# Skip image checks (faster)
npm run validate:import -- --skip-image-check

# Generate verbose report
npm run validate:import -- --verbose

# View generated report
cat import-report.md

# View retry list
cat retry-list.json | jq

# Run tests
npm test
```
