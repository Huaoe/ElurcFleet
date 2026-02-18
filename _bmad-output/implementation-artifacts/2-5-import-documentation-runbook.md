# Story 2.5: Import Documentation & Runbook

Status: ready-for-dev

## Story

As a platform operator,
I want comprehensive documentation for the import process,
so that I can re-run imports and troubleshoot issues.

## Acceptance Criteria

### AC1: Documentation Creation
**Given** import scripts are complete
**When** Documentation is created
**Then** README created: `product-importer/README.md`
**And** Documentation includes prerequisites (Node.js, API keys)

### AC2: Step-by-Step Instructions
**Given** documentation content
**When** I read the documentation
**Then** Documentation includes step-by-step import instructions
**And** Documentation includes script usage examples
**And** Documentation includes troubleshooting guide
**And** Documentation includes re-run/resume instructions

### AC3: Operational Documentation
**Given** operational needs
**When** I need to perform import operations
**Then** Documentation includes rollback procedures
**And** Documentation includes expected timings (~30 min for full import)
**And** Documentation includes environment variable setup
**And** Documentation includes rate limiting and batch size tuning

## Tasks / Subtasks

- [ ] **Task 1: Create comprehensive README.md** (AC: 1, 2, 3)
  - [ ] Write overview section explaining the import system
  - [ ] Document prerequisites (Node.js, API keys, source files)
  - [ ] Document project structure
  - [ ] Include quick start guide
  - [ ] Add FAQ section

- [ ] **Task 2: Document step-by-step workflow** (AC: 2)
  - [ ] Document Story 2.1: Category import steps
  - [ ] Document Story 2.2: Image migration steps
  - [ ] Document Story 2.3: Product import steps
  - [ ] Document Story 2.4: Validation steps
  - [ ] Include expected outputs at each step

- [ ] **Task 3: Document script usage examples** (AC: 2)
  - [ ] Common usage patterns
  - [ ] CLI argument reference
  - [ ] Example commands for each script
  - [ ] Sample output examples

- [ ] **Task 4: Create troubleshooting guide** (AC: 2)
  - [ ] Common errors and solutions
  - [ ] API rate limiting issues
  - [ ] Network timeout handling
  - [ ] Data validation failures
  - [ ] Recovery procedures

- [ ] **Task 5: Document re-run/resume procedures** (AC: 2)
  - [ ] Resume from interruption
  - [ ] Partial re-import scenarios
  - [ ] Retry failed products
  - [ ] Clean restart procedures

- [ ] **Task 6: Document rollback procedures** (AC: 3)
  - [ ] Identify when rollback is needed
  - [ ] Product deletion procedures
  - [ ] Category cleanup
  - [ ] Image cleanup

- [ ] **Task 7: Document configuration options** (AC: 3)
  - [ ] Environment variables reference
  - [ ] Batch size tuning guidance
  - [ ] Rate limiting configuration
  - [ ] Performance optimization tips

- [ ] **Task 8: Create runbook for operational scenarios** (AC: 3)
  - [ ] First-time import procedure
  - [ ] Weekly/monthly refresh procedure
  - [ ] Emergency procedures
  - [ ] Monitoring and alerts setup

## Dev Notes

### Architecture Context

This is the **final documentation story** for Epic 2. It creates comprehensive operational documentation that allows platform operators to run the import system independently.

**Key Documentation Goals:**
- **Self-Service**: Enable operators to run imports without developer assistance
- **Troubleshooting**: Provide clear guidance for common issues
- **Operational Excellence**: Include runbooks for various scenarios
- **Knowledge Transfer**: Document tribal knowledge and best practices

**Target Audience:**
- Platform operators
- DevOps engineers
- Technical support staff
- Future maintainers

### README Structure

**`product-importer/README.md`:**

```markdown
# Product Catalog Import System

A comprehensive import system for populating the Fleetbase marketplace with 4,000 products, 42 categories, and 4,610 images.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Quick Start](#quick-start)
4. [Detailed Workflow](#detailed-workflow)
5. [Configuration](#configuration)
6. [Troubleshooting](#troubleshooting)
7. [Operational Runbooks](#operational-runbooks)
8. [API Reference](#api-reference)

---

## Overview

This import system consists of 4 sequential steps:

1. **Category Import** (Story 2.1) - Creates 42 hierarchical categories
2. **Image Migration** (Story 2.2) - Uploads 4,610 product images
3. **Product Import** (Story 2.3) - Imports 4,000 products with variants
4. **Validation** (Story 2.4) - Verifies import success

**Expected Results:**
- 4,000+ products in Fleetbase
- 42 categories with hierarchy
- 4,600+ accessible product images
- ≥99% import success rate

---

## Prerequisites

### System Requirements

- **Node.js**: v18.0.0 or higher
- **npm**: v8.0.0 or higher
- **Memory**: 4GB RAM minimum (8GB recommended)
- **Disk**: 5GB free space (for source files and logs)
- **Network**: Stable internet connection

### Fleetbase Requirements

- Fleetbase instance running (local or cloud)
- Storefront extension installed and activated
- Valid API key with write permissions
- Network configured: "Stalabard DAO Marketplace"

### Source Files

Ensure you have:
```
product-importer/
├── json/
│   ├── products_1.json     # ~500 products
│   ├── products_2.json     # ~500 products
│   └── ... (8 files total)
└── uploads/
    └── product_*/         # 4,610 image files
```

### API Configuration

Create `.env` file:
```bash
FLEETBASE_API_URL=https://your-fleetbase.com
FLEETBASE_API_KEY=your-api-key-here
FLEETBASE_NETWORK_ID=stalabard-dao-marketplace
```

---

## Quick Start

### Full Import (Recommended for First Time)

```bash
# 1. Install dependencies
npm install

# 2. Run complete import workflow
npm run import:all

# This executes:
# - import-categories
# - migrate-images  
# - import-products
# - validate-import
```

### Manual Step-by-Step

```bash
# Step 1: Import categories (generates category-mapping.json)
npm run import:categories

# Step 2: Migrate images (generates image-url-mapping.json)
npm run migrate:images

# Step 3: Import products (uses mapping files)
npm run import:products

# Step 4: Validate results
npm run validate:import
```

---

## Detailed Workflow

### Story 2.1: Category Import

**Purpose:** Create 42 hierarchical product categories in Fleetbase

**Input:**
- `json/products_*.json` (scans all 8 files for category paths)

**Output:**
- `category-mapping.json` (category name → Fleetbase UUID)

**Command:**
```bash
npm run import:categories
```

**Expected Output:**
```
Category Import Started
Scanning 8 JSON files for categories...
Found 42 unique categories
Building 3-level hierarchy...
Creating categories in Fleetbase...
[1/42] Created: Food (uuid: abc-123)
[2/42] Created: Dairy (uuid: def-456)
...
[42/42] Created: Swiss Cheese (uuid: xyz-789)
Import complete!
Saved mapping to: category-mapping.json
```

**Verification:**
```bash
cat category-mapping.json | jq '.total_categories'
# Expected: 42
```

**Timing:** ~2-3 minutes

---

### Story 2.2: Image Migration

**Purpose:** Upload 4,610 product images to Fleetbase storage

**Input:**
- `uploads/` directory with product images

**Output:**
- `image-url-mapping.json` (local path → Fleetbase URL)

**Command:**
```bash
npm run migrate:images
```

**Expected Output:**
```
Image Migration Started
Total images: 4,610
Batch size: 50
Estimated time: ~30 minutes

[1/93] Processing batch... (50 images)
Uploaded: 50 | Failed: 0 | Skipped: 0
Progress: 50/4610 (1.1%) | ETA: 28 minutes
...
[93/93] Processing batch... (10 images)
Migration complete!
Saved mapping to: image-url-mapping.json
```

**Verification:**
```bash
cat image-url-mapping.json | jq '.total_images'
# Expected: ~4,610
```

**Timing:** ~30-40 minutes (depends on image sizes and network)

**Resume from Interruption:**
```bash
npm run migrate:images -- --resume
```

---

### Story 2.3: Product Import

**Purpose:** Import 4,000 products with variants and metadata

**Prerequisites:**
- `category-mapping.json` from Step 1
- `image-url-mapping.json` from Step 2

**Input:**
- `json/products_*.json` (8 files)

**Output:**
- `import-state.json` (progress tracking)
- `import-errors.log` (error details)
- `import-report.json` (summary)

**Command:**
```bash
npm run import:products
```

**Expected Output:**
```
Product Import Started
Loading category mapping... ✓ (42 categories)
Loading image mapping... ✓ (4,610 images)
Total products to import: 4,000
Batch size: 100
Estimated time: ~60 minutes

[1/40] Processing batch... (100 products)
Batch complete: 100 successful, 0 failed
Progress: 100/4000 (2.5%) | ETA: 58 minutes
...
[40/40] Processing batch... (100 products)
Import complete!
Total: 4,000 | Successful: 3,995 | Failed: 5
Success rate: 99.88%

Reports saved:
- import-state.json
- import-errors.log
- import-report.json
```

**Verification:**
```bash
cat import-report.json | jq '.summary.success_rate'
# Expected: "99.88%" or higher
```

**Timing:** ~60-90 minutes

**Resume from Interruption:**
```bash
npm run import:products -- --resume
```

---

### Story 2.4: Import Validation

**Purpose:** Verify import success and generate comprehensive report

**Command:**
```bash
npm run validate:import
```

**Validation Checks:**
1. Product count (expect 4,000+)
2. Category assignments (all products have categories)
3. Image URLs (all images accessible)
4. Variant data (SKU, price, inventory present)

**Expected Output:**
```
Import Validation Started
Querying Fleetbase API...

[1/4] Checking product count...
✓ Products found: 3,995/4,000 (99.88%)

[2/4] Checking category assignments...
✓ All products have categories

[3/4] Checking image URLs...
⚠ 5 images not accessible

[4/4] Checking variant data...
✓ All products have valid variants

Overall Status: PASSED (≥99% threshold met)

Reports generated:
- import-report.md
- retry-list.json
```

**View Report:**
```bash
cat import-report.md
```

---

## Configuration

### Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `FLEETBASE_API_URL` | Yes | - | Fleetbase API endpoint |
| `FLEETBASE_API_KEY` | Yes | - | API key with write permissions |
| `FLEETBASE_NETWORK_ID` | Yes | - | Network identifier |
| `BATCH_SIZE` | No | 100 | Products per batch |
| `BATCH_DELAY_MS` | No | 1500 | Delay between batches (ms) |
| `MAX_RETRIES` | No | 3 | Retry attempts per item |
| `DRY_RUN` | No | false | Run without making changes |
| `VERBOSE` | No | false | Detailed logging |

### Batch Size Tuning

**Small Batches (50 products):**
- Use for unstable networks
- Lower memory usage
- Longer total time
- Safer for production

**Large Batches (200 products):**
- Use for fast networks
- Higher memory usage
- Shorter total time
- Risk of timeout

**Recommended:**
- Categories: batch size 20 (API limit)
- Images: batch size 50 (file upload size)
- Products: batch size 100 (balanced)

### Rate Limiting

Fleetbase API has rate limits:
- 100 requests/minute for standard operations
- 10 requests/minute for file uploads

The scripts respect these limits with built-in delays.

**If you hit rate limits:**
```bash
# Increase batch delay
BATCH_DELAY_MS=3000 npm run import:products

# Or reduce batch size
BATCH_SIZE=50 npm run import:products
```

---

## Troubleshooting

### Common Errors

#### 1. "Category not found"

**Symptom:**
```
Error: Category not found: UnknownCategory
Product: prod_0032
```

**Cause:** Product references a category not in the mapping

**Solution:**
```bash
# Option 1: Add category to mapping manually
# Edit category-mapping.json and add the missing category

# Option 2: Skip product and continue
npm run import:products -- --skip-on-error

# Option 3: Fix source data and re-import
```

---

#### 2. "Image upload failed"

**Symptom:**
```
Error: Image upload failed after 3 retries
Image: uploads/product_042/main.jpg
```

**Cause:** Network timeout or file too large

**Solution:**
```bash
# Retry specific image
npm run migrate:images -- --retry-file=uploads/product_042/main.jpg

# Or retry all failed
npm run migrate:images -- --retry-failed
```

---

#### 3. "SKU already exists"

**Symptom:**
```
Error: SKU already exists: DUP-SKU-001
Product: prod_0089
```

**Cause:** Duplicate SKU in source data or previously imported

**Solution:**
```bash
# Check for duplicates in source
grep -r "DUP-SKU-001" json/

# Fix source data
# Or generate unique SKU in transformer
```

---

#### 4. "API rate limit exceeded"

**Symptom:**
```
Error: 429 Too Many Requests
```

**Solution:**
```bash
# Increase delays
BATCH_DELAY_MS=3000 npm run import:products

# Or wait and resume
# (Script automatically retries with backoff)
```

---

#### 5. "Network timeout"

**Symptom:**
```
Error: ETIMEDOUT
Error: ECONNRESET
```

**Solution:**
```bash
# Check network connection
ping fleetbase-api-url

# Resume from interruption
npm run import:products -- --resume

# Increase timeout
API_TIMEOUT=30000 npm run import:products
```

---

### Validation Failures

#### Success Rate < 99%

**Investigate:**
```bash
# Check failed products
cat import-errors.log

# Check validation report
cat import-report.md | grep -A 20 "Failed Products"

# Retry failed products
npm run import:products -- --retry-list=retry-list.json
```

#### Broken Image URLs

**Investigate:**
```bash
# Check which images are broken
cat import-report.md | grep -A 10 "Broken Images"

# Re-migrate specific images
npm run migrate:images -- --retry-only
```

---

## Operational Runbooks

### Runbook 1: First-Time Import

**Preparation:**
1. Verify Fleetbase instance is running
2. Verify API key has write permissions
3. Verify source files are present
4. Create `.env` file with configuration

**Execution:**
```bash
# Step 1: Install dependencies
npm install

# Step 2: Run full workflow
npm run import:all

# Step 3: Review validation report
cat import-report.md
```

**Success Criteria:**
- ≥99% product import success
- 100% category creation success
- ≥99% image upload success

---

### Runbook 2: Weekly Refresh (Partial Update)

**Scenario:** Update existing products with new data

**Preparation:**
1. Get latest product JSON files
2. Backup current mappings

**Execution:**
```bash
# Backup current state
cp import-state.json import-state-backup-$(date +%Y%m%d).json

# Run selective import
npm run import:products -- --update-existing

# Validate
npm run validate:import
```

---

### Runbook 3: Emergency Rollback

**Scenario:** Import caused data corruption

**Warning:** Destructive operation - use with caution

**Steps:**
```bash
# 1. Stop all import processes
# 2. Identify products to remove
# (Use validation report to identify bad products)

# 3. Delete corrupted products
# (Requires Fleetbase admin access)

# 4. Clean up orphaned data
# - Remove unused categories
# - Remove orphaned images

# 5. Re-import clean data
npm run import:all
```

---

### Runbook 4: Resume Interrupted Import

**Scenario:** Import stopped mid-process (power failure, network issue)

**Steps:**
```bash
# 1. Check current state
cat import-state.json | jq '.progress'

# 2. Resume from interruption
npm run import:products -- --resume

# 3. Validate final results
npm run validate:import
```

---

## API Reference

### Scripts

#### `npm run import:categories`

Import product categories from source JSON files.

**Options:**
- `--dry-run`: Preview without creating categories
- `--verbose`: Detailed logging

**Output Files:**
- `category-mapping.json`

---

#### `npm run migrate:images`

Upload product images to Fleetbase storage.

**Options:**
- `--dry-run`: Preview without uploading
- `--resume`: Resume from interruption
- `--batch-size=N`: Custom batch size
- `--retry-failed`: Retry failed uploads only

**Output Files:**
- `image-url-mapping.json`
- `migration-state.json`

---

#### `npm run import:products`

Import products with variants and metadata.

**Options:**
- `--dry-run`: Preview without importing
- `--resume`: Resume from interruption
- `--batch-size=N`: Custom batch size
- `--skip-on-error`: Continue on errors
- `--retry-list=FILE`: Retry specific products

**Output Files:**
- `import-state.json`
- `import-errors.log`
- `import-report.json`

---

#### `npm run validate:import`

Validate import results and generate reports.

**Options:**
- `--skip-image-check`: Skip image URL validation
- `--verbose`: Detailed validation output

**Output Files:**
- `import-report.md`
- `retry-list.json`

---

#### `npm run import:all`

Run complete import workflow (categories → images → products → validation).

**Options:**
- All options from individual scripts
- `--resume`: Resume from any step

---

## FAQ

**Q: How long does a full import take?**
A: Approximately 90-120 minutes:
- Categories: 2-3 minutes
- Images: 30-40 minutes
- Products: 60-90 minutes
- Validation: 5-10 minutes

**Q: Can I run imports in parallel?**
A: No, steps must be sequential (categories → images → products). However, you can run validation in parallel with the final product batches.

**Q: What if I need to stop mid-import?**
A: Press Ctrl+C to stop gracefully. The script saves progress state and can resume with `--resume` flag.

**Q: Can I import partial data?**
A: Yes, use `--import-file=products_1.json` to import specific files.

**Q: How do I update existing products?**
A: Use `--update-existing` flag to update products by SKU match.

**Q: What if validation shows <99% success?**
A: Check `import-errors.log` for details, fix source data issues, and retry failed products using `retry-list.json`.

**Q: Can I import to multiple environments?**
A: Yes, create separate `.env` files (`.env.staging`, `.env.production`) and use:
```bash
NODE_ENV=staging npm run import:all
```

---

## Support

**Issues:**
- Check `import-errors.log` for detailed error messages
- Review `import-report.md` for validation results
- Consult Troubleshooting section above

**Contact:**
- Technical issues: DevOps team
- Data issues: Data engineering team
- Fleetbase issues: Fleetbase support

---

**Version:** 1.0.0  
**Last Updated:** 2026-02-17  
**Compatible with:** Fleetbase v0.4.x+
```

### Additional Documentation Files

**`product-importer/CONTRIBUTING.md`** (for developers):
```markdown
# Contributing to Product Import System

## Development Setup

```bash
git clone <repo>
cd product-importer
npm install
npm run test
```

## Adding New Validators

1. Create validator in `src/validators/`
2. Add tests in `tests/unit/`
3. Update README.md documentation
4. Submit PR

## Code Style

- ESLint configuration in `.eslintrc.js`
- Prettier configuration in `.prettierrc`
- Run `npm run lint` before committing
```

**`product-importer/CHANGELOG.md`**:
```markdown
# Changelog

## 1.0.0 (2026-02-17)

- Initial release
- Category import with 3-level hierarchy support
- Image migration with resume capability
- Product import with variants and metadata
- Comprehensive validation and reporting
- Full documentation and runbooks
```

### Project Structure with Documentation

```
product-importer/
├── src/                        # Source code
├── scripts/                    # CLI entry points
├── tests/                      # Test files
├── docs/                       # Additional documentation
│   ├── TROUBLESHOOTING.md      # Detailed troubleshooting
│   ├── API.md                  # API integration guide
│   └── DEPLOYMENT.md           # Deployment guide
├── README.md                   # Main documentation
├── CONTRIBUTING.md             # Developer guide
├── CHANGELOG.md                # Version history
├── LICENSE                     # License file
└── package.json
```

### References

**Source: epics.md#Epic 2: Product Catalog Import**
- Story 2.5: Import Documentation & Runbook
- Comprehensive documentation requirements
- Troubleshooting and operational procedures

**Source: _bmad-output/implementation-artifacts/2-1-category-hierarchy-import-script.md**
- Category import technical details
- CLI usage patterns

**Source: _bmad-output/implementation-artifacts/2-2-image-migration-to-fleetbase-storage.md**
- Image migration technical details
- Resume and retry procedures

**Source: _bmad-output/implementation-artifacts/2-3-product-import-script-core-engine.md**
- Product import technical details
- Error handling patterns

**Source: _bmad-output/implementation-artifacts/2-4-import-validation-reporting.md**
- Validation procedures
- Report format

## Dev Agent Record

### Agent Model Used

Claude 3.5 Sonnet (Coding Agent)

### Debug Log References

### Completion Notes List

- [ ] README.md created with comprehensive documentation
- [ ] Quick start guide included
- [ ] Step-by-step workflow documented
- [ ] Script usage examples provided
- [ ] Troubleshooting guide created
- [ ] Re-run/resume procedures documented
- [ ] Rollback procedures documented
- [ ] Environment variables documented
- [ ] Batch size tuning guidance included
- [ ] Operational runbooks created
- [ ] FAQ section included
- [ ] Contributing guide created (optional)
- [ ] Changelog created (optional)

### File List

**New Files:**
1. `product-importer/README.md` (main documentation)
2. `product-importer/CONTRIBUTING.md` (developer guide)
3. `product-importer/CHANGELOG.md` (version history)
4. `product-importer/docs/TROUBLESHOOTING.md` (detailed troubleshooting)
5. `product-importer/docs/API.md` (API integration details)

**Existing Files Referenced:**
- All Story 2.x documentation files
- Import scripts and services
- Configuration files

### Verification

```bash
# Verify documentation exists
ls -la product-importer/README.md

# Check documentation completeness
grep -c "## " product-importer/README.md
# Should show multiple sections

# Verify all scripts documented
grep "npm run" product-importer/README.md

# Check troubleshooting section length
wc -l product-importer/README.md
# Expected: 500+ lines
```
