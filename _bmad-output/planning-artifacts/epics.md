---
stepsCompleted:
  - step-01-validate-prerequisites
  - step-01-requirements-extracted
  - step-02-design-epics
  - step-03-create-stories
inputDocuments:
  - c:/Projects/ElurcFleet/_bmad-output/planning-artifacts/prd.md
  - c:/Projects/ElurcFleet/_bmad-output/planning-artifacts/architecture.md
---

# ElurcFleet - Epic Breakdown

## Overview

This document provides the complete epic and story breakdown for ElurcFleet, decomposing the requirements from the PRD, UX Design if it exists, and Architecture requirements into implementable stories.

## Requirements Inventory

### Functional Requirements

**FR-001**: System SHALL allow platform access only to verified community members.
**FR-002**: System SHALL verify membership using DAO NFT badge ownership.
**FR-003**: Every verified member SHALL be able to act as both seller and buyer.
**FR-004**: Seller views SHALL only expose that seller's own listings and seller operations.
**FR-005**: System SHALL deny non-member access to protected routes and actions.
**FR-010**: Verified member (seller with Store) SHALL create product with title, description, category, price, media, and metadata using native Fleetbase Storefront.
**FR-011**: Seller SHALL publish product directly without pre-approval (governance status: approved).
**FR-012**: DAO moderator SHALL intervene only when a product is flagged or policy violation is detected.
**FR-013**: Intervention SHALL support actions such as warning, suspend, or unpublish with explicit rationale.
**FR-014**: All intervention actions SHALL be auditable and visible to the product owner (seller).
**FR-020**: Buyers SHALL see only currently published and non-suspended products (governance-filtered).
**FR-021**: Sellers SHALL manage only their own store's products and orders (store-scoped).
**FR-022**: Orders SHALL preserve buyer and seller attribution.
**FR-023**: Buyers SHALL select a delivery point (grocery store Place) during checkout.
**FR-024**: Orders SHALL route through selected delivery point for customer pickup.
**FR-025**: System SHALL track 3-leg order flow: seller → delivery hub → customer pickup.
**FR-026**: Sellers SHALL receive order notifications with delivery hub details (address, hours).
**FR-027**: Sellers SHALL be able to update order activity status (dispatched to hub, at delivery point).
**FR-028**: Grocery stores SHALL be able to mark orders ready for pickup.
**FR-029**: Customers SHALL receive pickup code and notification when order is ready.
**FR-030**: Checkout SHALL allow ELURC-only payment for MVP.
**FR-031**: Checkout SHALL support Phantom as first wallet option for verified members.
**FR-032**: Platform SHALL NOT custody private keys or sign user transactions.
**FR-033**: Payment flow SHALL verify transaction outcome before order completion.
**FR-034**: Payment record SHALL store token address, wallet address, tx hash, amount, and network.
**FR-035**: If payment provider is not ELURC, checkout SHALL fail with explicit error.
**FR-040**: System SHALL log product lifecycle transitions (created, published, suspended, unpublished).
**FR-041**: System SHALL log order routing transitions (dispatched, at hub, ready, completed).
**FR-042**: System SHALL log DAO moderation actions with actor, timestamp, action, and rationale.
**FR-043**: System SHALL expose operational summaries for products, orders, delivery points, issues, moderation actions, and payment outcomes.
**FR-044**: System SHALL provide Network-wide order visibility for platform operators.
**FR-045**: System SHALL track delivery point performance metrics (orders per hub, processing time).

### NonFunctional Requirements

**NFR-001**: Enforce role-based authorization on all custom APIs.
**NFR-002**: Enforce server-side ownership checks for seller-scoped resources.
**NFR-003**: Keep secrets server-side only; avoid exposing sensitive credentials in public runtime variables.
**NFR-004**: Include request-level audit metadata for governance and payment operations.
**NFR-005**: Membership verification checks MUST be server-side enforced for protected routes.
**NFR-010**: Payment verification path MUST be idempotent.
**NFR-011**: Listing publish and moderation workflows MUST be retry-safe.
**NFR-012**: API responses for moderation actions SHOULD return deterministic state transitions.
**NFR-020**: P95 response time <= 600ms for primary read/list APIs under MVP load.
**NFR-021**: Listing and order views SHOULD paginate and filter efficiently.
**NFR-030**: Correlate logs by member_id, product_id, store_id, place_id, issue_id, order_id, fleetops_order_id, payment_session_id.
**NFR-031**: Emit structured events for product published/updated/suspended, order routing status changes, delivery point notifications, and payment confirmed/failed.
**NFR-032**: Track order routing metrics (time at each leg, delivery hub utilization).
**NFR-033**: Use Laravel's logging system with structured context.
**NFR-034**: Integrate with monitoring tools (Laravel Telescope for dev, Sentry for production).

### Additional Requirements

**Architecture - Fleetbase Platform Setup:**
- Use Fleetbase as the base platform (Docker-based installation for MVP)
- Install and configure Storefront extension (core e-commerce functionality)
- Install and configure FleetOps extension (delivery routing and Places)
- Install Pallet extension (optional for inventory management)

**Architecture - Fleetbase Network Configuration:**
- Create "Stalabard DAO Marketplace" Network in Fleetbase Console (multi-vendor architecture)
- Configure network currency and invitation settings
- Generate Network Key for Storefront App integration
- Each seller operates as an independent Store within the Network

**Architecture - Custom Extensions Required:**
- Membership Extension (fleetbase/membership): NFT verification, wallet identity management
- DAO Governance Extension (fleetbase/dao-governance): Product governance, moderation, seller verification
- ELURC Payment Provider Extension (fleetbase/elurc-payment): Blockchain payment integration
- Order Routing Integration: FleetOps 3-leg delivery workflow

**Architecture - Delivery Points Configuration:**
- Configure grocery stores as FleetOps Places (delivery hubs)
- Tag places with: type='delivery_hub', subtype='grocery_store'
- Set operating hours and service areas for each Place
- Define geographic zones for delivery routing

**Architecture - Database Requirements:**
- PostgreSQL or MySQL for data persistence
- Redis for session management, cache, and job queues
- Custom migration files for extension models

**Architecture - Integration Requirements:**
- Solana RPC integration for NFT ownership verification
- Solana RPC integration for ELURC transaction verification
- Blockchain configuration: DAO address, NFT collection, ELURC token address
- Phantom wallet SDK integration in Storefront App

**Architecture - Security & Access Control:**
- Server-side membership verification middleware (VerifyMemberMiddleware)
- Store-scoped authorization guards (ProductOwnershipGuard)
- Role-based access control using Fleetbase IAM (Member, Moderator, Admin)
- No client-side security trust; all checks server-side

**Architecture - Event-Driven Architecture:**
- Laravel event system for audit trails and notifications
- Event listeners for product lifecycle, order routing, payment verification, moderation actions
- Audit event logging to dedicated table or external service

**Architecture - API Requirements:**
- RESTful API routes following Fleetbase conventions
- Storefront API routes for members (products, orders, checkout, delivery points)
- Internal/Admin routes for moderation and network management
- Laravel Form Requests for input validation
- Fleetbase JSON API response format

**Architecture - Observability Requirements:**
- Structured logging with correlation IDs
- Laravel Telescope for development monitoring
- Sentry for production error tracking
- Admin dashboards for network health, delivery point performance, order routing, moderation queue, payment outcomes
- Performance monitoring: API response times, RPC latency, payment verification duration

**Architecture - Deployment Requirements:**
- Docker Compose deployment for MVP (self-hosted)
- Environment variables for DAO configuration, ELURC token, Solana RPC
- Same migration lineage across local, staging, and production environments
- Configuration for Fleetbase base URL and API keys

**Architecture - Testing Requirements:**
- Unit tests for all services (governance, routing, payment)
- Integration tests for API routes
- E2E test suite covering full 3-leg delivery workflow
- Security tests for ownership isolation and non-member access denial
- Performance baseline tests (membership verification <2s, listing queries <600ms P95, payment verification <5s)

### FR Coverage Map

- **FR-001**: Epic 1 - Platform access restricted to verified members
- **FR-002**: Epic 1 - NFT badge ownership verification
- **FR-003**: Epic 1, Epic 2 - Members can act as both seller and buyer
- **FR-004**: Epic 2 - Seller views scoped to own store
- **FR-005**: Epic 1 - Non-member access denial
- **FR-010**: Epic 4 - Product creation with Storefront
- **FR-011**: Epic 4 - Direct product publishing (approved status)
- **FR-012**: Epic 9 - DAO reactive moderation on flagged products
- **FR-013**: Epic 9 - Moderation actions (warn, suspend, unpublish)
- **FR-014**: Epic 4, Epic 9 - Auditable intervention actions
- **FR-020**: Epic 4, Epic 6 - Governance-filtered product visibility
- **FR-021**: Epic 2 - Store-scoped product/order management
- **FR-022**: Epic 6 - Order attribution (buyer/seller)
- **FR-023**: Epic 5, Epic 6 - Delivery point selection during checkout
- **FR-024**: Epic 6 - Orders route through delivery points
- **FR-025**: Epic 8 - 3-leg order flow tracking
- **FR-026**: Epic 8 - Seller order notifications with hub details
- **FR-027**: Epic 8 - Seller order activity updates
- **FR-028**: Epic 8 - Grocery store ready-for-pickup marking
- **FR-029**: Epic 8 - Customer pickup codes and notifications
- **FR-030**: Epic 7 - ELURC-only payment enforcement
- **FR-031**: Epic 7 - Phantom wallet support
- **FR-032**: Epic 7 - Non-custodial (no private key custody)
- **FR-033**: Epic 7 - Transaction verification before completion
- **FR-034**: Epic 7 - Payment metadata persistence
- **FR-035**: Epic 7 - Non-ELURC payment rejection
- **FR-040**: Epic 4 - Product lifecycle logging
- **FR-041**: Epic 8 - Order routing transition logging
- **FR-042**: Epic 9 - Moderation action logging
- **FR-043**: Epic 9 - Operational summaries and reports
- **FR-044**: Epic 8, Epic 9 - Network-wide order visibility
- **FR-045**: Epic 5, Epic 9 - Delivery point performance metrics

## Epic List

### Epic 1: Platform Foundation & Verified Access
DAO members can verify their NFT badge ownership and access the members-only marketplace platform.

**FRs covered:** FR-001, FR-002, FR-003, FR-005

**Implementation Notes:** Sets up Fleetbase platform, Stalabard DAO Network, membership verification system with NFT badge checks. Complete authentication and authorization foundation.

---

### Epic 2: Seller Network Participation
Verified members can join the Stalabard Network as independent sellers with their own stores.

**FRs covered:** FR-003, FR-004, FR-021

**Implementation Notes:** Store creation within Network, seller-scoped access controls, Fleetbase Console dashboard access for sellers.

---

### Epic 4: Product Catalog Management
Sellers can create, publish, and manage products with DAO governance metadata.

**FRs covered:** FR-010, FR-011, FR-014, FR-020, FR-040

**Implementation Notes:** Native Fleetbase Storefront product creation extended with ProductGovernance, direct publishing, lifecycle logging.

---

### Epic 5: Delivery Point Infrastructure
Platform operators can configure grocery stores as delivery hubs, and buyers can view available pickup locations.

**FRs covered:** FR-023, FR-045

**Implementation Notes:** FleetOps Places configuration, delivery hub tagging, operating hours, geographic zones for routing.

---

### Epic 6: Marketplace Shopping & Checkout
Buyers can browse published products across all Network stores and select delivery points during checkout.

**FRs covered:** FR-020, FR-022, FR-023, FR-024

**Implementation Notes:** Network-wide product browsing with governance filtering, cart management, delivery point selection UI.

---

### Epic 7: ELURC Non-Custodial Payments
Buyers can complete purchases using ELURC tokens via Phantom wallet with non-custodial payment verification.

**FRs covered:** FR-030, FR-031, FR-032, FR-033, FR-034, FR-035

**Implementation Notes:** ELURC Payment Provider extension, Solana RPC transaction verification, idempotent payment processing, Phantom wallet integration.

---

### Epic 8: 3-Leg Order Fulfillment & Routing
Orders route through a 3-leg delivery workflow (seller → grocery store → customer pickup) with status tracking and notifications.

**FRs covered:** FR-025, FR-026, FR-027, FR-028, FR-029, FR-041, FR-044

**Implementation Notes:** FleetOps order routing integration, custom activity flow, seller dispatch, hub confirmation, customer pickup codes, notification system.

---

### Epic 9: DAO Moderation & Platform Oversight
DAO moderators can reactively intervene on flagged products, and platform operators can monitor network health and performance.

**FRs covered:** FR-012, FR-013, FR-014, FR-042, FR-043, FR-044, FR-045

**Implementation Notes:** Product issue reporting, moderation actions with rationale, audit trails, admin dashboards for network health, delivery point performance, order routing metrics.

---

## Epic 1: Platform Foundation & Verified Access

DAO members can verify their NFT badge ownership and access the members-only marketplace platform.

### Story 1.1: Fleetbase Platform Setup & Network Creation

As a platform operator,
I want to install Fleetbase and create the Stalabard DAO Marketplace Network,
So that the multi-vendor marketplace infrastructure is ready for member onboarding.

**Acceptance Criteria:**

**Given** Docker is installed on the system
**When** I execute the Fleetbase Docker installation process
**Then** Fleetbase API is running and accessible
**And** Storefront extension is installed and configured
**And** FleetOps extension is installed and configured
**And** PostgreSQL/MySQL database is initialized

**Given** Fleetbase is successfully installed
**When** I create a new Network named "Stalabard DAO Marketplace"
**Then** Network is created with unique Network ID
**And** Network Key is generated for Storefront App integration
**And** Network currency settings are configured

**Given** the Network is created
**When** I configure environment variables for DAO integration
**Then** DAO_ADDRESS is set to D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq
**And** DAO_NFT_COLLECTION is set to 3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c
**And** SOLANA_RPC_URL is configured for blockchain integration
**And** Redis is configured for session and cache management

### Story 1.2: Membership Extension Scaffold & Database Models

As a developer,
I want to create the Membership Extension with database models,
So that member identity and profile data can be persisted.

**Acceptance Criteria:**

**Given** Fleetbase is installed
**When** I scaffold the Membership Extension using Fleetbase CLI
**Then** Extension directory structure is created at fleetbase-membership/
**And** ServiceProvider is registered with Fleetbase
**And** Extension is activated in Fleetbase Console

**Given** the extension scaffold exists
**When** I create the MemberIdentity Eloquent model
**Then** Model has fields: uuid, wallet_address (unique indexed), membership_status (enum), verified_at, nft_token_account, last_verified_at, metadata (JSON)
**And** Model defines belongsTo(User) relationship (optional)
**And** Model defines hasOne(MemberProfile) relationship

**Given** the MemberIdentity model exists
**When** I create the MemberProfile Eloquent model
**Then** Model has fields: uuid, member_identity_uuid, display_name, avatar_url, bio, metadata (JSON)
**And** Model defines belongsTo(MemberIdentity) relationship

**Given** both models are defined
**When** I create and run database migrations
**Then** member_identities table is created with proper indexes
**And** member_profiles table is created with foreign key to member_identities
**And** Migrations run successfully without errors

### Story 1.3: NFT Badge Ownership Verification Service

As a developer,
I want to implement NFT badge ownership verification via Solana RPC,
So that the platform can verify DAO membership server-side.

**Acceptance Criteria:**

**Given** Membership Extension exists
**When** I create MembershipVerificationService class
**Then** Service class is registered in ServiceProvider
**And** Service is injectable via dependency injection

**Given** the service class exists
**When** I implement verifyMembership(wallet_address, signature) method
**Then** Method validates wallet ownership via signature verification
**And** Method queries Solana RPC for NFT token accounts owned by wallet
**And** Method checks if NFT collection matches DAO_NFT_COLLECTION

**Given** NFT ownership verification succeeds
**When** verifyMembership is called for a valid member
**Then** MemberIdentity record is created with membership_status: 'verified'
**And** verified_at timestamp is set
**And** nft_token_account is stored
**And** Method returns verification result with member data

**Given** NFT ownership verification fails
**When** verifyMembership is called for an invalid wallet
**Then** Method returns verification result with failure reason
**And** No MemberIdentity record is created
**And** Error is logged with wallet_address and failure reason

**Given** a previously verified member
**When** verifyMembership is called again
**Then** Existing MemberIdentity record is updated
**And** last_verified_at timestamp is refreshed
**And** Idempotent behavior is maintained

### Story 1.4: Member Wallet Connection & Verification API

As a DAO member,
I want to connect my Phantom wallet and verify my NFT badge ownership,
So that I can access the members-only marketplace.

**Acceptance Criteria:**

**Given** the API route is registered
**When** I POST to /membership/verify with wallet_address and signature
**Then** VerifyMemberMiddleware is NOT applied (public endpoint)
**And** Input validation ensures wallet_address and signature are present
**And** MembershipVerificationService.verifyMembership() is called

**Given** NFT verification succeeds
**When** POST /membership/verify completes
**Then** Response returns 200 status
**And** Response includes member_uuid, membership_status: 'verified'
**And** Response includes MemberProfile data
**And** Session or JWT token is created for authenticated requests

**Given** NFT verification fails
**When** POST /membership/verify is called with invalid wallet
**Then** Response returns 403 status
**And** Response includes error message explaining verification failure
**And** No session or token is created

**Given** I am a verified member
**When** I GET /membership/status with valid authentication
**Then** Response returns current membership_status
**And** Response includes last_verified_at timestamp
**And** Response confirms verified status

**Given** I am a verified member
**When** I GET /membership/profile with valid authentication
**Then** Response returns MemberProfile data (display_name, avatar_url, bio)
**And** Response includes membership metadata

### Story 1.5: Membership Verification Middleware & Access Control

As a platform operator,
I want all protected routes to enforce membership verification,
So that only verified DAO members can access marketplace features.

**Acceptance Criteria:**

**Given** Membership Extension exists
**When** I create VerifyMemberMiddleware class
**Then** Middleware is registered in Fleetbase HTTP kernel
**And** Middleware can be applied to route groups

**Given** the middleware is registered
**When** I apply middleware to protected route groups
**Then** Middleware is applied to /storefront/v1/* routes (except public browse)
**And** Middleware is applied to /int/v1/* admin routes with role checks

**Given** a non-member attempts access
**When** Protected route request is made without verified membership
**Then** Middleware returns 403 Forbidden response
**And** Error message states "Verified DAO membership required"
**And** Request is blocked before reaching controller

**Given** a verified member makes request
**When** Protected route request includes valid authentication
**Then** Middleware checks MemberIdentity.membership_status == 'verified'
**And** Middleware allows request to proceed to controller
**And** Member context is available in request

**Given** membership verification check fails
**When** RPC service is unavailable or times out
**Then** Middleware denies access with clear error message
**And** Failure is logged with correlation_id for troubleshooting
**And** Retry-after header is included in response

### Story 1.6: Member Profile Management

As a verified member,
I want to create and update my profile information,
So that other members can identify me in the marketplace.

**Acceptance Criteria:**

**Given** I am a verified member
**When** I first access the platform after verification
**Then** MemberProfile record is automatically created
**And** Profile is linked to my MemberIdentity
**And** Default display_name is set from wallet address (truncated)

**Given** my MemberProfile exists
**When** I PATCH /membership/profile with display_name, avatar_url, bio
**Then** Profile fields are validated (display_name max 50 chars, valid URL for avatar)
**And** Profile is updated with new values
**And** Response returns updated profile data

**Given** I provide an invalid avatar URL
**When** I attempt to update my profile
**Then** Request returns 422 Validation Error
**And** Error message specifies invalid avatar_url format

**Given** another member views my products or orders
**When** They query seller or buyer attribution
**Then** My display_name and avatar_url are visible
**And** My wallet_address is NOT exposed (privacy)

## Epic 2: Product Catalog Import

Import 4,000 products from pre-scraped JSON files into the Fleetbase Storefront "shop" network, including hierarchical category creation, image migration, variant management, and metadata preservation.

### Story 2.1: Category Hierarchy Import Script

**As a platform operator,**
**I want to create a script that imports the 42 hierarchical product categories,**
**So that the product catalog is properly organized.**

**Acceptance Criteria:**

**Given** product JSON files exist in `product-importer/json/`
**When** I run the category import script
**Then** Script scans all 8 JSON files and extracts unique `categoryPath` arrays
**And** Script builds category tree with 3 levels (parent → child → grandchild)
**And** Script creates 42 categories via Fleetbase Storefront API

**Given** category creation process
**When** Script creates each category
**Then** Categories are created with proper parent UUID references
**And** Script handles existing categories with find-or-create pattern
**And** Script outputs category mapping JSON (name → UUID)

**Given** category import completes
**When** Script finishes execution
**Then** All 42 categories successfully created in Fleetbase
**And** Category hierarchy is preserved (parent-child relationships)
**And** Script logs creation progress and any errors

### Story 2.2: Image Migration to Fleetbase Storage

**As a platform operator,**
**I want to migrate product images from local storage to Fleetbase,**
**So that product images are properly hosted and accessible.**

**Acceptance Criteria:**

**Given** product images exist in `product-importer/uploads/` (4,610 files)
**When** I run the image migration script
**Then** Script scans directory and identifies all image files
**And** Script uploads images to Fleetbase storage via API

**Given** image upload process
**When** Script uploads each image
**Then** Images are processed in batches of 50-100
**And** Script handles upload failures with retry logic (3 attempts)
**And** Script skips already-uploaded images (idempotent)

**Given** image migration completes
**When** Script finishes execution
**Then** Script generates mapping JSON (local path → Fleetbase URL)
**And** All 4,610 images successfully uploaded and accessible
**And** Script logs upload progress ("1000/4610 uploaded")
**And** Mapping file saved: `product-importer/image-url-mapping.json`

### Story 2.3: Product Import Script - Core Engine

**As a platform operator,**
**I want to import 4,000 products with variants and metadata,**
**So that the marketplace is populated with the complete catalog.**

**Acceptance Criteria:**

**Given** category mapping and image mapping files exist
**When** I run the product import script
**Then** Script reads all 8 product JSON files
**And** Script loads category UUID mapping from Story 2.1
**And** Script loads image URL mapping from Story 2.2

**Given** product creation process
**When** Script creates each product
**Then** Products are created via Fleetbase Storefront API
**And** Products include variants (SKU, price, inventory)
**And** Products are linked to correct categories via UUID
**And** Product images referenced by Fleetbase URLs

**Given** product metadata
**When** Products are imported
**Then** Metadata is preserved (ingredients, nutrition, allergens, origin)
**And** Tags are created from brand names
**And** Product status is set to 'published'

**Given** import processing
**When** Script processes products
**Then** Script processes in batches of 100 products
**And** Script implements 1-2 second delay between batches
**And** Script saves progress state for resume capability
**And** Script handles API errors with retry logic (3 attempts)

**Given** import completes
**When** Script finishes execution
**Then** All 4,000 products successfully imported
**And** Script generates import report (success/failure counts)
**And** State file saved: `product-importer/import-state.json`
**And** Error log saved: `product-importer/import-errors.log`

### Story 2.4: Import Validation & Reporting

**As a platform operator,**
**I want to validate the product import and generate reports,**
**So that I can verify data integrity and identify any issues.**

**Acceptance Criteria:**

**Given** product import has completed
**When** I run the validation script
**Then** Script queries Fleetbase API for total product count
**And** Script verifies all 4,000 products imported

**Given** validation checks
**When** Script validates imported data
**Then** Script checks category assignments (all products have categories)
**And** Script validates image URLs (all images accessible)
**And** Script verifies variant data (SKU, price, inventory present)

**Given** validation completes
**When** Script generates report
**Then** Script generates import summary report (Markdown)
**And** Report includes: total products, categories, images, errors
**And** Script identifies missing/failed products
**And** Script generates retry list for failed products

**Given** report is generated
**When** I view the report
**Then** Report saved: `product-importer/import-report.md`
**And** Report shows ≥99% product import success rate
**And** Report shows 100% category and image migration success

### Story 2.5: Import Documentation & Runbook

**As a platform operator,**
**I want comprehensive documentation for the import process,**
**So that I can re-run imports and troubleshoot issues.**

**Acceptance Criteria:**

**Given** import scripts are complete
**When** Documentation is created
**Then** README created: `product-importer/README.md`
**And** Documentation includes prerequisites (Node.js, API keys)

**Given** documentation content
**When** I read the documentation
**Then** Documentation includes step-by-step import instructions
**And** Documentation includes script usage examples
**And** Documentation includes troubleshooting guide
**And** Documentation includes re-run/resume instructions

**Given** operational needs
**When** I need to perform import operations
**Then** Documentation includes rollback procedures
**And** Documentation includes expected timings (~30 min for full import)
**And** Documentation includes environment variable setup
**And** Documentation includes rate limiting and batch size tuning

---

## Epic 3: Seller Network Participation

Verified members can join the Stalabard Network as independent sellers with their own stores.

### Story 3.1: Store Creation Service & Network Invitation

As a developer,
I want to implement store creation within the Network for verified members,
So that members can join as independent sellers.

**Acceptance Criteria:**

**Given** Membership Extension exists
**When** I create StoreCreationService class
**Then** Service is registered in MembershipServiceProvider
**And** Service can create Stores within the Network

**Given** the service exists
**When** I implement createSellerStore(member_profile_uuid) method
**Then** Method queries Network by name "Stalabard DAO Marketplace"
**And** Method creates new Store within the Network
**And** Method sets Store.name from MemberProfile.display_name
**And** Method generates Store API key for seller access

**Given** store creation succeeds
**When** createSellerStore completes
**Then** Store is linked to MemberProfile via store_uuid
**And** MemberProfile.store_uuid is updated
**And** Store status is set to 'active'
**And** Method returns Store data with Store ID and API key

**Given** a member already has a Store
**When** createSellerStore is called again
**Then** Method returns existing Store data
**And** Idempotent behavior prevents duplicate stores
**And** No error is thrown

### Story 3.2: Seller Store Creation API

As a verified member,
I want to request seller status and get my own store,
So that I can list products in the marketplace.

**Acceptance Criteria:**

**Given** I am a verified member
**When** I POST to /membership/store/create
**Then** VerifyMemberMiddleware is applied (members only)
**And** Request validates I don't already have a Store
**And** StoreCreationService.createSellerStore() is called

**Given** store creation succeeds
**When** POST /membership/store/create completes
**Then** Response returns 201 Created status
**And** Response includes Store ID, Store name, and Store API key
**And** Response includes Fleetbase Console access URL
**And** MemberProfile is updated with store_uuid

**Given** I already have a Store
**When** I POST to /membership/store/create again
**Then** Response returns 200 OK status
**And** Response includes existing Store data
**And** Message states "Store already exists"

**Given** Network is at capacity
**When** I attempt to create a Store
**Then** Response returns 503 Service Unavailable
**And** Error message explains Network capacity limitation

### Story 3.3: Store-Scoped Authorization Guard

As a platform operator,
I want to enforce store-scoped access control,
So that sellers can only access their own store's resources.

**Acceptance Criteria:**

**Given** DAO Governance Extension exists
**When** I create ProductOwnershipGuard service class
**Then** Service is registered in DAOGovernanceServiceProvider
**And** Service is injectable in controllers and services

**Given** the guard service exists
**When** I implement validateOwnership(seller_member_profile_uuid, resource) method
**Then** Method checks if resource belongs to seller's Store
**And** Method queries Store via seller's MemberProfile.store_uuid
**And** Method validates resource.store_uuid matches seller's store_uuid

**Given** ownership validation succeeds
**When** validateOwnership is called for seller's own resource
**Then** Method returns true
**And** No exception is thrown

**Given** ownership validation fails
**When** validateOwnership is called for another seller's resource
**Then** Method throws AuthorizationException
**And** Exception message states "Access denied: resource belongs to another store"
**And** Exception includes correlation_id for audit trail

**Given** guard is applied to product endpoints
**When** Seller A attempts to PATCH Seller B's product
**Then** Request is blocked at service layer
**And** Response returns 403 Forbidden
**And** Audit log records unauthorized access attempt

### Story 3.4: Seller Store Dashboard Access

As a seller,
I want to access Fleetbase Console to manage my store,
So that I can create and manage products using the native interface.

**Acceptance Criteria:**

**Given** I have a Store in the Network
**When** I access Fleetbase Console with my Store API key
**Then** I am authenticated as the Store owner
**And** I see my Store dashboard
**And** Dashboard shows store-scoped navigation

**Given** I am logged into Console
**When** I navigate to Products section
**Then** I see only products belonging to my Store
**And** Product list is filtered by my store_uuid
**And** I can create new products

**Given** I am logged into Console
**When** I navigate to Orders section
**Then** I see only orders for products from my Store
**And** Order list is filtered by my store_uuid
**And** I cannot see orders from other stores

**Given** I attempt to access another Store's resources
**When** I manually construct URL to another Store's product
**Then** Console returns 403 Forbidden
**And** Error message states "Access denied"
**And** I am redirected to my Store dashboard

**Given** Network owner views Console
**When** Network owner accesses Network management
**Then** Owner sees all Stores in the Network
**And** Owner can view Network-wide order statistics
**And** Owner CANNOT access individual Store's private data (products/orders in detail)

## Epic 4: Product Catalog Management

Sellers can create, publish, and manage products with DAO governance metadata.

### Story 4.1: DAO Governance Extension Scaffold & Models

As a developer,
I want to create the DAO Governance Extension with product governance models,
So that products can be tracked with DAO-specific metadata.

**Acceptance Criteria:**

**Given** Fleetbase is installed
**When** I scaffold the DAO Governance Extension using Fleetbase CLI
**Then** Extension directory structure is created at fleetbase-dao-governance/
**And** ServiceProvider is registered with Fleetbase
**And** Extension is activated in Fleetbase Console

**Given** the extension scaffold exists
**When** I create the ProductGovernance Eloquent model
**Then** Model has fields: uuid, product_uuid (FK), seller_member_profile_uuid, governance_status (enum: pending_approval/approved/flagged/suspended), flagged_at, suspended_at, metadata (JSON)
**And** Model defines belongsTo(Product) relationship (Storefront)
**And** Model defines belongsTo(MemberProfile, 'seller') relationship

**Given** ProductGovernance model exists
**When** I create database migration
**Then** product_governance table is created with indexes
**And** Foreign key to storefront.products table is defined
**And** Foreign key to member_profiles table is defined
**And** Index on governance_status for efficient filtering

### Story 4.2: Product Creation with Governance Metadata

As a seller,
I want to create products using Fleetbase Storefront with governance tracking,
So that my products are linked to my seller profile.

**Acceptance Criteria:**

**Given** I am a seller with a Store
**When** I POST to /storefront/v1/products with product data
**Then** VerifyMemberMiddleware is applied (members only)
**And** ProductOwnershipGuard validates I have a Store
**And** Native Storefront Product is created with my store_uuid

**Given** Product creation succeeds
**When** ProductGovernanceService.createProduct() is called
**Then** ProductGovernance record is created linked to Product
**And** seller_member_profile_uuid is set to my MemberProfile
**And** governance_status is set to 'approved' (direct publish for MVP)
**And** ProductCreated event is fired

**Given** product and governance are created
**When** POST /storefront/v1/products completes
**Then** Response returns 201 Created status
**And** Response includes Product data with uuid, name, price, status
**And** Response includes governance_status: 'approved'
**And** Product is immediately visible in marketplace

**Given** I don't have a Store
**When** I attempt to create a product
**Then** Response returns 403 Forbidden
**And** Error message states "Must be a seller with active Store"

### Story 4.3: Product Publishing & Lifecycle Events

As a seller,
I want to publish products directly without pre-approval,
So that my products are immediately available to buyers.

**Acceptance Criteria:**

**Given** I have an unpublished product
**When** I POST to /storefront/v1/products/{id}/publish
**Then** ProductOwnershipGuard validates product belongs to my Store
**And** Product.status is updated to 'published'
**And** ProductGovernance.governance_status remains 'approved'

**Given** publish succeeds
**When** POST /storefront/v1/products/{id}/publish completes
**Then** Response returns 200 OK
**And** Response includes updated Product with status: 'published'
**And** ProductPublished event is fired with product_uuid, seller_uuid, timestamp

**Given** ProductPublished event fires
**When** AuditEventListener receives event
**Then** Audit log records: event_type='product_published', actor_uuid, subject_uuid=product_uuid
**And** Log includes governance_status snapshot
**And** Log includes store_uuid for traceability

**Given** I update product details
**When** I PATCH /storefront/v1/products/{id}
**Then** ProductOwnershipGuard validates ownership
**And** Product fields are updated
**And** ProductUpdated event is fired

### Story 4.4: Store-Scoped Product List API

As a seller,
I want to view only my own products,
So that I can manage my store's inventory.

**Acceptance Criteria:**

**Given** I am a seller with products
**When** I GET /storefront/v1/products/mine
**Then** VerifyMemberMiddleware is applied
**And** Query filters products by my store_uuid
**And** ProductOwnershipGuard ensures store-scoped results

**Given** query executes
**When** GET /storefront/v1/products/mine completes
**Then** Response returns only products from my Store
**And** Each product includes governance metadata
**And** Results are paginated (default 20 per page)
**And** Total count is included in response metadata

**Given** another seller's products exist
**When** I GET /storefront/v1/products/mine
**Then** Other sellers' products are NOT in results
**And** Only my store_uuid products are returned

**Given** I am a buyer (not seller)
**When** I GET /storefront/v1/products/mine
**Then** Response returns 403 Forbidden
**And** Error message states "Seller status required"

### Story 4.5: Network-Wide Product Browse with Governance Filtering

As a buyer,
I want to browse all published products across the Network,
So that I can discover items from all sellers.

**Acceptance Criteria:**

**Given** products exist from multiple stores
**When** I GET /storefront/v1/products (without /mine)
**Then** Query joins Product with ProductGovernance
**And** Query filters governance_status IN ('approved')
**And** Query filters Product.status = 'published'

**Given** governance filtering applies
**When** GET /storefront/v1/products completes
**Then** Response includes products from all Network stores
**And** Suspended or flagged products are excluded
**And** Each product includes seller attribution (display_name, avatar)
**And** Results are paginated and sortable

**Given** a product is flagged or suspended
**When** Buyer browses products
**Then** Flagged/suspended products do NOT appear in results
**And** Governance filtering is enforced server-side

**Given** I filter by category or search term
**When** I GET /storefront/v1/products?category=X&search=Y
**Then** Governance filters are still applied
**And** Only approved, published products match query

### Story 4.6: Product Lifecycle Logging & Audit Trail

As a platform operator,
I want all product lifecycle transitions logged,
So that governance actions are auditable.

**Acceptance Criteria:**

**Given** ProductCreated event fires
**When** AuditEventListener processes event
**Then** Audit record is created with event_type='product_created'
**And** Record includes: product_uuid, seller_member_profile_uuid, store_uuid, governance_status='approved', timestamp

**Given** ProductPublished event fires
**When** AuditEventListener processes event
**Then** Audit record is created with event_type='product_published'
**And** Record includes previous and new status

**Given** ProductUpdated event fires
**When** AuditEventListener processes event
**Then** Audit record is created with changed fields
**And** Record includes correlation_id for request tracing

**Given** audit events are logged
**When** Admin queries audit trail for a product
**Then** GET /int/v1/admin/audit-events?subject_uuid={product_uuid}
**And** Response returns chronological event history
**And** Each event includes actor, action, timestamp, state changes

## Epic 5: Delivery Point Infrastructure

Platform operators can configure grocery stores as delivery hubs, and buyers can view available pickup locations.

### Story 5.1: FleetOps Places Configuration for Delivery Hubs

As a platform operator,
I want to configure grocery stores as FleetOps Places,
So that they can serve as delivery points for customer order pickup.

**Acceptance Criteria:**

**Given** FleetOps extension is installed
**When** I POST to /int/v1/places with grocery store details
**Then** Place record is created with type='delivery_hub'
**And** Place is tagged with subtype='grocery_store'
**And** Place includes: name, address, coordinates, operating_hours

**Given** Place creation succeeds
**When** POST /int/v1/places completes
**Then** Response returns 201 Created status
**And** Response includes place_uuid and place details
**And** Place is associated with the Network
**And** Place status is set to 'active'

**Given** operating hours are specified
**When** Place is configured
**Then** Operating hours are stored as JSON: {"monday": "9am-5pm", "tuesday": "9am-5pm", ...}
**And** Hours are validated for proper time format
**And** Closed days can be marked

**Given** geographic zone is defined
**When** Place is created with service_area polygon
**Then** Service area coordinates are stored
**And** Service area radius can be specified as alternative
**And** Multiple Places can have overlapping service areas

### Story 5.2: Delivery Point Capacity & Metadata Configuration

As a platform operator,
I want to configure capacity limits and metadata for delivery hubs,
So that order routing respects hub constraints.

**Acceptance Criteria:**

**Given** a Place exists
**When** I PATCH /int/v1/places/{id} with capacity settings
**Then** max_concurrent_orders is set (default: unlimited)
**And** buffer_time_minutes is configured (time between orders)
**And** Custom metadata is stored in JSON field

**Given** capacity metadata is set
**When** Place configuration is updated
**Then** Capacity rules are stored: max_daily_orders, max_concurrent_orders
**And** Notification settings are configured (email, SMS for hub staff)
**And** Special instructions field is available (e.g., "Use back entrance")

**Given** multiple delivery hubs exist
**When** I GET /int/v1/places
**Then** Response returns all Places filtered by type='delivery_hub'
**And** Each Place includes status, operating hours, capacity settings
**And** Results show current order count vs capacity

### Story 5.3: Buyer-Facing Delivery Point Browse API

As a buyer,
I want to see available grocery store pickup locations,
So that I can choose a convenient delivery point during checkout.

**Acceptance Criteria:**

**Given** active delivery hubs exist
**When** I GET /storefront/v1/places
**Then** VerifyMemberMiddleware is applied (members only)
**And** Query filters Places by type='delivery_hub' AND status='active'
**And** Only Places within operating hours or with future availability are shown

**Given** query executes
**When** GET /storefront/v1/places completes
**Then** Response includes: place_uuid, name, address, coordinates, operating_hours
**And** Each Place includes distance from user location (if provided)
**And** Current capacity status is indicated (available/limited/full)
**And** Special instructions are included

**Given** I provide my location coordinates
**When** I GET /storefront/v1/places?lat=X&lon=Y
**Then** Results are sorted by distance from my location
**And** Only Places within reasonable range are returned
**And** Distance is calculated and displayed

**Given** a Place is at capacity
**When** Buyer views available Places
**Then** Place is still shown but marked as "Limited availability"
**And** Estimated next available time is displayed

### Story 5.4: Delivery Point Performance Metrics Tracking

As a platform operator,
I want to track delivery point performance metrics,
So that I can optimize hub operations and identify issues.

**Acceptance Criteria:**

**Given** orders are routed through Places
**When** OrderActivityChanged events fire for delivery hubs
**Then** Metrics are tracked: orders_received, orders_ready, orders_completed
**And** Timing metrics are captured: avg_time_at_hub, avg_pickup_time

**Given** metrics are tracked
**When** I GET /int/v1/places/{id}/metrics
**Then** Response includes: total_orders, active_orders, completed_orders
**And** Response includes: avg_processing_time, capacity_utilization
**And** Metrics are aggregated by time period (today, week, month)

**Given** multiple hubs exist
**When** Admin views dashboard
**Then** Comparative metrics are shown across all hubs
**And** Underperforming hubs are flagged
**And** Capacity bottlenecks are highlighted

## Epic 6: Marketplace Shopping & Checkout

Buyers can browse published products across all Network stores and select delivery points during checkout.

### Story 6.1: Shopping Cart Management

As a buyer,
I want to add products to my cart and manage quantities,
So that I can purchase multiple items in a single order.

**Acceptance Criteria:**

**Given** I am browsing products
**When** I POST to /storefront/v1/cart/items with product_uuid and quantity
**Then** VerifyMemberMiddleware is applied (members only)
**And** Product availability is verified
**And** Cart item is created or quantity is updated if already in cart

**Given** cart item is added
**When** POST /storefront/v1/cart/items completes
**Then** Response returns 200 OK
**And** Response includes updated cart with all items
**And** Each item shows: product details, quantity, unit price, subtotal
**And** Cart total is calculated

**Given** I have items in cart
**When** I PATCH /storefront/v1/cart/items/{id} with new quantity
**Then** Quantity is updated (1 or more)
**And** Subtotal is recalculated
**And** Response includes updated cart

**Given** I want to remove an item
**When** I DELETE /storefront/v1/cart/items/{id}
**Then** Item is removed from cart
**And** Cart total is recalculated
**And** Response returns updated cart

**Given** I have a cart from previous session
**When** I GET /storefront/v1/cart
**Then** Cart persists across sessions
**And** Product availability is re-checked
**And** Unavailable items are flagged

### Story 6.2: Checkout Flow with Delivery Point Selection

As a buyer,
I want to initiate checkout and select my delivery point,
So that I can specify where to pick up my order.

**Acceptance Criteria:**

**Given** I have items in cart
**When** I POST to /storefront/v1/checkout
**Then** VerifyMemberMiddleware is applied
**And** Cart items are validated for availability
**And** Checkout session is created

**Given** checkout session exists
**When** I POST to /storefront/v1/checkout/delivery-point with place_uuid
**Then** Place is validated (active, within operating hours)
**And** Place capacity is checked
**And** Delivery point is associated with checkout session
**And** Estimated pickup time is calculated

**Given** delivery point is selected
**When** Checkout continues
**Then** Order draft is created with:
  - Customer (linked to MemberProfile)
  - Products from cart
  - Selected delivery point (place_uuid)
  - Order status: 'pending_payment'
**And** Payment intent is created
**And** Response includes payment details and order summary

**Given** selected Place reaches capacity
**When** Buyer attempts checkout
**Then** Warning is displayed about limited availability
**And** Alternative nearby Places are suggested
**And** Buyer can override and proceed or select different Place

### Story 6.3: Order Attribution & Multi-Store Order Handling

As a platform,
I want to properly attribute orders to buyers and sellers,
So that multi-vendor orders are handled correctly.

**Acceptance Criteria:**

**Given** cart contains items from multiple stores
**When** Checkout is initiated
**Then** System detects multi-store order
**And** Separate Storefront Orders are created per Store
**And** Each Order is linked to its Store

**Given** multiple orders are created
**When** Orders are associated with buyer
**Then** Each Order preserves:
  - customer_uuid (buyer's MemberProfile linked Customer)
  - store_uuid (seller's Store)
  - Selected delivery point (same place_uuid for all)
**And** Buyer receives consolidated order confirmation
**And** Each seller receives their respective order notification

**Given** order is created
**When** Order record is persisted
**Then** Order includes:
  - Buyer MemberProfile reference
  - Seller Store reference
  - Delivery Place reference
  - Order items with product details
  - Order status
  - Timestamps

## Epic 7: ELURC Non-Custodial Payments

Buyers can complete purchases using ELURC tokens via Phantom wallet with non-custodial payment verification.

### Story 7.1: ELURC Payment Provider Extension Setup

As a developer,
I want to create the ELURC Payment Provider Extension,
So that blockchain payments can be integrated with Fleetbase.

**Acceptance Criteria:**

**Given** Fleetbase is installed
**When** I scaffold the ELURC Payment Extension
**Then** Extension directory is created at fleetbase-elurc-payment/
**And** ServiceProvider is registered
**And** Extension is activated

**Given** extension scaffold exists
**When** I create PaymentIntentMetadata Eloquent model
**Then** Model has fields: uuid, order_uuid, transaction_uuid, wallet_address, token_address, amount, network, tx_hash (unique indexed), provider='elurc', verification_status (enum), confirmed_at, failure_reason, metadata (JSON)
**And** Model defines belongsTo(Order) relationship
**And** Model defines belongsTo(Transaction) relationship

**Given** PaymentIntentMetadata model exists
**When** I create database migration
**Then** payment_intent_metadata table is created
**And** Unique index on tx_hash is created
**And** Index on order_uuid for lookups
**And** Foreign keys to orders and transactions tables

**Given** payment provider interface exists
**When** I implement ElurCPaymentProvider class
**Then** Provider implements Fleetbase PaymentGateway interface
**And** Provider is registered in Fleetbase payment system
**And** Provider identifier is 'elurc'

### Story 7.2: ELURC Payment Intent Creation

As a buyer,
I want to create an ELURC payment intent during checkout,
So that I know the exact payment details for my Phantom wallet.

**Acceptance Criteria:**

**Given** checkout has order with delivery point selected
**When** I POST to /payments/v1/elurc/intent with order_uuid
**Then** ElurCPaymentService.createPaymentIntent() is called
**And** Service validates order is in 'pending_payment' status
**And** Service calculates total amount in ELURC

**Given** payment intent is created
**When** POST /payments/v1/elurc/intent completes
**Then** PaymentIntentMetadata record is created
**And** Response returns:
  - recipient_address (platform ELURC wallet)
  - token_address (ELURC mint address)
  - amount (in ELURC tokens)
  - network (mainnet-beta or devnet)
  - order_uuid
**And** verification_status is set to 'pending'

**Given** non-ELURC payment is attempted
**When** Different payment provider is selected
**Then** Request returns 400 Bad Request
**And** Error message states "ELURC-only payment required for MVP"
**And** Alternative payment methods are rejected

### Story 7.3: Phantom Wallet Integration & Transaction Signing

As a buyer,
I want to sign ELURC payment transaction with Phantom wallet,
So that I can complete payment without custodial key management.

**Acceptance Criteria:**

**Given** payment intent is created
**When** Storefront App receives payment details
**Then** Phantom wallet SDK is invoked
**And** Transaction is constructed:
  - From: buyer's wallet_address
  - To: platform recipient_address
  - Token: ELURC token_address
  - Amount: order total

**Given** Phantom prompts for signature
**When** I approve transaction in Phantom
**Then** Transaction is signed with my private key (client-side)
**And** Transaction is broadcasted to Solana network
**And** tx_hash (transaction signature) is returned

**Given** transaction is broadcasted
**When** App receives tx_hash
**Then** App submits tx_hash to backend for verification
**And** POST to /payments/v1/verify with tx_hash and order_uuid
**And** Platform never accesses my private keys

**Given** I reject transaction in Phantom
**When** Payment flow is cancelled
**Then** Order remains in 'pending_payment' status
**And** Payment intent remains 'pending'
**And** Buyer can retry payment

### Story 7.4: Server-Side Transaction Verification

As a platform,
I want to verify ELURC transactions on-chain before completing orders,
So that payment authenticity is guaranteed.

**Acceptance Criteria:**

**Given** tx_hash is submitted
**When** POST to /payments/v1/verify is received
**Then** ElurCPaymentService.verifyTransaction() is called
**And** Idempotency check: if tx_hash already verified, return existing result

**Given** verification begins
**When** Service queries Solana RPC
**Then** Transaction details are fetched by signature (tx_hash)
**And** Service validates:
  - Token address matches ELURC_TOKEN_ADDRESS
  - Amount matches order total
  - Recipient matches ELURC_PLATFORM_WALLET
  - Network matches configured ELURC_NETWORK
  - Transaction status is confirmed (configurable depth)

**Given** verification succeeds
**When** All validation passes
**Then** PaymentIntentMetadata.verification_status = 'confirmed'
**And** confirmed_at timestamp is set
**And** PaymentVerified event is fired
**And** Response returns 200 OK with status: 'confirmed'

**Given** verification fails
**When** Validation checks fail
**Then** PaymentIntentMetadata.verification_status = 'failed'
**And** failure_reason is recorded (e.g., "Amount mismatch", "Invalid token")
**And** PaymentFailed event is fired
**And** Response returns 400 Bad Request with failure details

**Given** transaction is pending confirmation
**When** Confirmation depth not yet reached
**Then** verification_status remains 'pending'
**And** Response returns 202 Accepted
**And** Client polls /payments/v1/status/{order_uuid} for updates

### Story 7.5: Payment Verification Job & Order Completion

As a platform,
I want to complete orders after payment verification,
So that the fulfillment process can begin.

**Acceptance Criteria:**

**Given** PaymentVerified event fires
**When** Event listener processes event
**Then** Order status is updated from 'pending_payment' to 'paid'
**And** Order completion workflow is triggered
**And** Audit log records payment confirmation

**Given** order is marked 'paid'
**When** Order completion proceeds
**Then** FleetOps Order is created for routing (Epic 7)
**And** Seller receives order notification
**And** Buyer receives order confirmation
**And** Payment is considered final

**Given** payment remains 'pending' beyond timeout
**When** PaymentVerificationJob runs (async)
**Then** Job re-checks transaction status via RPC
**And** Job retries with exponential backoff
**And** After max retries, payment is marked 'failed'
**And** Order is cancelled or buyer is notified to retry

**Given** payment verification is idempotent
**When** Duplicate verification requests occur
**Then** Existing PaymentIntentMetadata record is retrieved by tx_hash
**And** Same result is returned without re-verification
**And** No duplicate order completion is triggered

### Story 7.6: Payment Status Polling API

As a buyer,
I want to poll payment status during transaction confirmation,
So that I know when my order is ready.

**Acceptance Criteria:**

**Given** payment is submitted and pending
**When** I GET /payments/v1/status/{order_uuid}
**Then** VerifyMemberMiddleware is applied
**And** Service queries PaymentIntentMetadata by order_uuid
**And** Response includes verification_status: 'pending'/'confirmed'/'failed'

**Given** payment is confirmed
**When** GET /payments/v1/status/{order_uuid} is called
**Then** Response returns status: 'confirmed'
**And** Response includes order_uuid, tx_hash, confirmed_at timestamp
**And** Order status is 'paid'

**Given** payment failed
**When** GET /payments/v1/status/{order_uuid} is called
**Then** Response returns status: 'failed'
**And** Response includes failure_reason
**And** Guidance for retry is provided

**Given** payment is still pending
**When** Buyer polls repeatedly
**Then** Response consistently returns 'pending' until confirmed
**And** Estimated confirmation time is provided
**And** Polling is rate-limited to prevent abuse

## Epic 8: 3-Leg Order Fulfillment & Routing

Orders route through a 3-leg delivery workflow (seller → grocery store → customer pickup) with status tracking and notifications.

### Story 8.1: FleetOps Order Routing Integration Service

As a developer,
I want to integrate FleetOps order routing with Storefront orders,
So that orders can be tracked through the 3-leg delivery workflow.

**Acceptance Criteria:**

**Given** Storefront and FleetOps extensions are installed
**When** I create OrderRoutingService class
**Then** Service is registered in ServiceProvider
**And** Service can create FleetOps Orders linked to Storefront Orders

**Given** OrderRoutingService exists
**When** I implement createThreeLegOrder(order, place_uuid) method
**Then** Method creates FleetOps Order with custom activity flow
**And** FleetOps Order is linked to Storefront Order via order_uuid
**And** Delivery point (Place) is set as waypoint

**Given** 3-leg activity flow is configured
**When** FleetOps Order is created
**Then** Activity statuses are: created → dispatched_to_hub → at_delivery_point → ready_for_pickup → completed
**And** Custom fields are set: grocery_store_place_uuid, pickup_code, seller_store_uuid
**And** Initial status is 'created'

### Story 8.2: FleetOps Order Creation on Payment Completion

As a platform,
I want to automatically create FleetOps routing orders after payment confirmation,
So that fulfillment workflow begins immediately.

**Acceptance Criteria:**

**Given** PaymentVerified event fires
**When** OrderCompletionListener processes event
**Then** OrderRoutingService.createThreeLegOrder() is called
**And** Storefront Order uuid is passed
**And** Selected delivery point place_uuid is passed

**Given** FleetOps Order creation succeeds
**When** createThreeLegOrder completes
**Then** FleetOps Order is created with:
  - Linked to Storefront Order (order_uuid)
  - Waypoint 1: Seller location (Store address)
  - Waypoint 2: Grocery store Place
  - Waypoint 3: Customer (virtual - pickup location is Place)
  - Status: 'created'
  - pickup_code generated (6-digit alphanumeric)

**Given** FleetOps Order is created
**When** Creation completes
**Then** OrderRoutingInitialized event is fired
**And** Seller is notified with order details and delivery hub info
**And** Grocery store is notified of incoming order (optional for MVP)

### Story 8.3: Seller Order Dispatch & Hub Notification

As a seller,
I want to mark orders as dispatched to the delivery hub,
So that the grocery store knows my order is on the way.

**Acceptance Criteria:**

**Given** I have a paid order
**When** I PATCH /storefront/v1/orders/{id}/activity with status='dispatched_to_hub'
**Then** VerifyMemberMiddleware is applied
**And** ProductOwnershipGuard validates order belongs to my Store
**And** OrderRoutingService.updateOrderActivity() is called

**Given** activity update is valid
**When** updateOrderActivity processes request
**Then** FleetOps Order status is updated to 'dispatched_to_hub'
**And** dispatched_at timestamp is recorded
**And** OrderDispatchedToHub event is fired

**Given** OrderDispatchedToHub event fires
**When** Event listener processes event
**Then** Grocery store receives notification:
  - Order ID and pickup_code
  - Estimated arrival time
  - Seller contact info
  - Order items summary
**And** Audit log records dispatch activity
**And** Order tracking is updated for buyer visibility

**Given** I provide estimated arrival time
**When** Dispatch is recorded
**Then** estimated_arrival_at is stored
**And** Grocery store sees expected delivery window

### Story 8.4: Grocery Store Order Receipt & Confirmation

As a grocery store operator,
I want to confirm order receipt when seller delivers,
So that the order status reflects it's at my location.

**Acceptance Criteria:**

**Given** order is dispatched to my hub
**When** Seller/driver arrives with order
**Then** I receive notification of incoming order (if not already notified)
**And** I see order details: pickup_code, items, customer info

**Given** I verify order delivery
**When** I update order status via hub interface (or API)
**Then** PATCH /int/v1/orders/{id}/hub-activity with status='at_delivery_point'
**And** FleetOps Order status is updated to 'at_delivery_point'
**And** received_at_hub timestamp is recorded

**Given** hub confirmation succeeds
**When** Status update completes
**Then** OrderAtDeliveryPoint event is fired
**And** Audit log records hub receipt
**And** Time at first leg (seller → hub) is calculated for metrics
**And** Customer is notified order is being prepared for pickup (optional)

**Given** I need to mark order ready for customer
**When** I PATCH /int/v1/orders/{id}/hub-activity with status='ready_for_pickup'
**Then** FleetOps Order status is updated to 'ready_for_pickup'
**And** ready_at timestamp is recorded
**And** OrderReadyForPickup event is fired

### Story 8.5: Customer Pickup Notification & Code Verification

As a buyer,
I want to receive a pickup code and notification when my order is ready,
So that I can collect my order from the grocery store.

**Acceptance Criteria:**

**Given** OrderReadyForPickup event fires
**When** CustomerNotificationListener processes event
**Then** Customer receives notification via app/email/SMS:
  - Order is ready for pickup
  - Grocery store name and address
  - Operating hours
  - pickup_code (6-digit code)
  - Special instructions (if any)

**Given** I arrive at grocery store
**When** I present my pickup_code to hub staff
**Then** Hub staff verifies code matches order
**And** Hub staff hands over order to me

**Given** hub confirms pickup
**When** Hub staff updates order status
**Then** PATCH /int/v1/orders/{id}/hub-activity with status='completed' and pickup_code for verification
**And** pickup_code is validated against order
**And** If code matches, FleetOps Order status is updated to 'completed'

**Given** pickup is confirmed
**When** Order completion succeeds
**Then** OrderCompleted event is fired
**And** completed_at timestamp is recorded
**And** Seller receives completion notification
**And** Buyer receives confirmation (order collected successfully)
**And** Audit log records completion

**Given** wrong pickup_code is provided
**When** Hub attempts to complete order
**Then** Request returns 400 Bad Request
**And** Error message states "Invalid pickup code"
**And** Order remains in 'ready_for_pickup' status

### Story 8.6: Order Status Tracking for All Parties

As a buyer, seller, or platform operator,
I want to view current order routing status,
So that I can track delivery progress.

**Acceptance Criteria:**

**Given** I am a buyer with an order
**When** I GET /storefront/v1/orders/{id}
**Then** Response includes Storefront Order details
**And** Response includes FleetOps routing status
**And** Current activity status is shown (dispatched/at_hub/ready/completed)
**And** Timestamps for each leg are included
**And** Delivery point details are included

**Given** I am a seller
**When** I GET /storefront/v1/orders/mine
**Then** Response includes only my Store's orders
**And** Each order shows routing status
**And** Orders are filterable by status

**Given** I am a platform operator
**When** I GET /int/v1/orders
**Then** Response includes all orders across Network
**And** Orders are filterable by routing status, delivery point, date range
**And** Aggregate metrics are included (total active, by status)

**Given** order has multiple activity transitions
**When** Order details are queried
**Then** Activity history is included with timestamps:
  - paid_at
  - dispatched_to_hub_at
  - at_delivery_point_at
  - ready_for_pickup_at
  - completed_at
**And** Time spent at each leg is calculated

### Story 8.7: Order Routing Event Logging & Audit Trail

As a platform operator,
I want all order routing transitions logged,
So that fulfillment workflow is auditable.

**Acceptance Criteria:**

**Given** OrderDispatchedToHub event fires
**When** AuditEventListener processes event
**Then** Audit record is created with event_type='order_dispatched_to_hub'
**And** Record includes: order_uuid, fleetops_order_uuid, seller_store_uuid, place_uuid, timestamp

**Given** OrderAtDeliveryPoint event fires
**When** AuditEventListener processes event
**Then** Audit record is created with event_type='order_at_delivery_point'
**And** Record includes time_in_transit (dispatched → arrived at hub)

**Given** OrderReadyForPickup event fires
**When** AuditEventListener processes event
**Then** Audit record is created with event_type='order_ready_for_pickup'
**And** Record includes time_at_hub (arrived → ready)

**Given** OrderCompleted event fires
**When** AuditEventListener processes event
**Then** Audit record is created with event_type='order_completed'
**And** Record includes total_fulfillment_time (paid → completed)
**And** Record includes pickup_code verification confirmation

**Given** audit events are logged
**When** Admin queries order audit trail
**Then** GET /int/v1/admin/audit-events?subject_uuid={order_uuid}&subject_type=order
**And** Response returns chronological routing history
**And** Each transition includes actor (seller/hub/customer), timestamp, status change

### Story 8.8: Order Routing Metrics Dashboard

As a platform operator,
I want to view order routing performance metrics,
So that I can identify bottlenecks and optimize delivery workflow.

**Acceptance Criteria:**

**Given** orders have completed routing
**When** I access admin dashboard
**Then** Dashboard shows metrics:
  - Total orders by status (dispatched, at hub, ready, completed)
  - Average time per leg (seller→hub, hub processing, pickup)
  - Orders per delivery hub (current and historical)
  - Completion rate and average total fulfillment time

**Given** metrics API exists
**When** I GET /int/v1/admin/metrics/order-routing
**Then** Response includes aggregated metrics by time period
**And** Metrics are filterable by date range, delivery hub, seller
**And** Performance trends are calculated (improving/declining)

**Given** delivery hub performance is tracked
**When** I view hub-specific metrics
**Then** For each Place, metrics show:
  - Total orders processed
  - Average processing time (arrival → ready)
  - Current capacity utilization
  - Peak hours and bottlenecks

**Given** bottlenecks are identified
**When** Dashboard highlights issues
**Then** Hubs with long processing times are flagged
**And** Sellers with delayed dispatches are identified
**And** Recommendations are provided (add capacity, adjust hours)

## Epic 9: DAO Moderation & Platform Oversight

DAO moderators can reactively intervene on flagged products, and platform operators can monitor network health and performance.

### Story 9.1: Product Issue Reporting Models & API

As a developer,
I want to create ProductIssue and ModerationAction models,
So that product flagging and moderation actions can be tracked.

**Acceptance Criteria:**

**Given** DAO Governance Extension exists
**When** I create ProductIssue Eloquent model
**Then** Model has fields: uuid, product_uuid, reporter_member_profile_uuid, status (enum: pending/reviewing/resolved/dismissed), reason, details, created_at
**And** Model defines belongsTo(Product) relationship
**And** Model defines belongsTo(MemberProfile, 'reporter') relationship
**And** Model defines hasMany(ModerationAction) relationship

**Given** ProductIssue model exists
**When** I create ModerationAction Eloquent model
**Then** Model has fields: uuid, issue_uuid, product_uuid, moderator_user_uuid, action (enum: warn/suspend/unpublish/dismiss), rationale, previous_status, new_status, acted_at
**And** Model defines belongsTo(ProductIssue) relationship
**And** Model defines belongsTo(Product) relationship
**And** Model defines belongsTo(User, 'moderator') relationship

**Given** both models are defined
**When** I create database migrations
**Then** product_issues table is created with indexes
**And** moderation_actions table is created with indexes
**And** Foreign keys are properly defined
**And** Indexes on status, product_uuid, created_at for efficient queries

### Story 9.2: Product Issue Reporting by Members

As a verified member,
I want to report problematic products,
So that DAO moderators can review and take action.

**Acceptance Criteria:**

**Given** I am a verified member browsing products
**When** I POST to /storefront/v1/products/{id}/issues with reason and details
**Then** VerifyMemberMiddleware is applied (members only)
**And** Product existence is validated
**And** ProductIssue record is created

**Given** issue creation succeeds
**When** POST completes
**Then** ProductIssue is created with:
  - product_uuid
  - reporter_member_profile_uuid (my profile)
  - status: 'pending'
  - reason (category like "Prohibited item", "Misleading description")
  - details (free text explanation)
**And** IssueReported event is fired
**And** Response returns 201 Created with issue details

**Given** IssueReported event fires
**When** Event listener processes event
**Then** DAO moderators are notified of new issue
**And** Audit log records issue report
**And** Product is NOT automatically suspended (reactive moderation only)

**Given** I am the reporter
**When** I GET /storefront/v1/issues
**Then** Response includes all issues I've reported
**And** Each issue shows: product, status, reason, created_at

**Given** I report the same product multiple times
**When** Creating duplicate issue
**Then** System allows multiple issues per product
**And** Each issue is tracked separately

### Story 9.3: DAO Moderation Queue & Issue Review

As a DAO moderator,
I want to view flagged product issues in a moderation queue,
So that I can review and prioritize interventions.

**Acceptance Criteria:**

**Given** I am a DAO moderator
**When** I GET /int/v1/moderation/issues
**Then** Authentication with moderator role is required
**And** Query returns ProductIssues with status: 'pending' or 'reviewing'
**And** Results are sorted by created_at (oldest first)

**Given** moderation queue is queried
**When** GET /int/v1/moderation/issues completes
**Then** Response includes for each issue:
  - issue_uuid
  - Product details (name, seller, status)
  - Reporter details (display_name, not wallet)
  - Reason and details
  - created_at timestamp
  - Issue status
**And** Results are paginated
**And** Total pending count is included

**Given** I want to view a specific issue
**When** I GET /int/v1/moderation/issues/{id}
**Then** Response includes full issue details
**And** Product governance history is included
**And** Previous moderation actions on this product (if any)
**And** Reporter's report history (pattern analysis)

**Given** I mark issue as under review
**When** I PATCH /int/v1/moderation/issues/{id} with status='reviewing'
**Then** Issue status is updated
**And** Issue is assigned to me (moderator_uuid)
**And** reviewed_at timestamp is set

### Story 9.4: Moderation Action Application

As a DAO moderator,
I want to apply moderation actions on flagged products,
So that policy violations are addressed with clear rationale.

**Acceptance Criteria:**

**Given** I am reviewing an issue
**When** I POST to /int/v1/moderation/issues/{id}/actions with action and rationale
**Then** Moderator role is verified
**And** ModerationAction record is created
**And** Action is one of: warn, suspend, unpublish, dismiss

**Given** action is 'suspend'
**When** Moderation action is applied
**Then** ProductGovernance.governance_status is updated to 'suspended'
**And** Product is no longer visible in marketplace browse
**And** ProductSuspended event is fired
**And** previous_status and new_status are recorded

**Given** action is 'unpublish'
**When** Moderation action is applied
**Then** Product.status is updated to 'unpublished'
**And** ProductGovernance.governance_status is updated to 'suspended'
**And** ProductUnpublished event is fired

**Given** action is 'warn'
**When** Moderation action is applied
**Then** ProductGovernance.flagged_at is set
**And** governance_status is updated to 'flagged'
**And** Product remains visible (with flag metadata)
**And** Seller is notified of warning

**Given** action is 'dismiss'
**When** Moderation action is applied
**Then** ProductIssue.status is updated to 'dismissed'
**And** No changes to Product or ProductGovernance
**And** Reporter is notified issue was reviewed and dismissed

**Given** moderation action succeeds
**When** POST to /int/v1/moderation/issues/{id}/actions completes
**Then** Response returns 200 OK
**And** Response includes action details and updated product status
**And** ModerationActionApplied event is fired

### Story 9.5: Seller Notification of Moderation Actions

As a seller,
I want to be notified when my products are moderated,
So that I understand what happened and why.

**Acceptance Criteria:**

**Given** ModerationActionApplied event fires
**When** Event listener processes event
**Then** Seller receives notification with:
  - Product name and ID
  - Action taken (suspended, unpublished, warned, issue dismissed)
  - Moderator rationale
  - Timestamp of action
  - How to appeal or address issue (if applicable)

**Given** my product is suspended
**When** I view my products
**Then** GET /storefront/v1/products/mine shows suspended product
**And** governance_status: 'suspended' is visible
**And** Moderation action details are included
**And** I can see the rationale

**Given** my product is flagged with warning
**When** I view product details
**Then** Warning details are shown
**And** I can update product to address concerns
**And** Product remains published during remediation

**Given** issue was dismissed
**When** Seller is notified
**Then** Notification states no violation found
**And** Product remains unaffected

### Story 9.6: Moderation Action Audit Trail

As a platform operator,
I want all moderation actions logged,
So that DAO governance is transparent and auditable.

**Acceptance Criteria:**

**Given** ModerationActionApplied event fires
**When** AuditEventListener processes event
**Then** Audit record is created with event_type='moderation_action_applied'
**And** Record includes: action, moderator_user_uuid, issue_uuid, product_uuid, rationale, previous_status, new_status, timestamp

**Given** moderation history exists
**When** I GET /int/v1/admin/audit-events?subject_type=moderation
**Then** Response returns all moderation actions
**And** Each record includes moderator, product, action, rationale
**And** Results are filterable by moderator, action type, date range

**Given** product has moderation history
**When** I query product audit trail
**Then** GET /int/v1/admin/audit-events?subject_uuid={product_uuid}
**And** Response includes all governance events:
  - Product created
  - Product published
  - Issue reported
  - Moderation actions taken
**And** Complete timeline is visible

### Story 9.7: Network Health Dashboard

As a platform operator,
I want to view Network-wide health metrics,
So that I can monitor marketplace performance and identify issues.

**Acceptance Criteria:**

**Given** Network has active stores and orders
**When** I access admin dashboard
**Then** Dashboard shows Network metrics:
  - Total stores (active, invited, suspended)
  - Total products (published, suspended, flagged)
  - Total orders (by status: paid, dispatched, at hub, ready, completed)
  - Total members (verified, active buyers, active sellers)

**Given** metrics API exists
**When** I GET /int/v1/admin/metrics/network
**Then** Response includes aggregated metrics
**And** Metrics are calculated for time periods (today, week, month, all-time)
**And** Growth trends are shown (new stores, new products, order volume)

**Given** I want store-level metrics
**When** I GET /int/v1/network/stores with metrics
**Then** Each store shows:
  - Total products
  - Total orders
  - Revenue (if tracked)
  - Member rating (if implemented)
  - Last activity timestamp

**Given** issues or anomalies exist
**When** Dashboard is displayed
**Then** Warnings are highlighted:
  - Stores with no recent activity
  - High product suspension rate
  - Low order completion rate
  - Payment verification failures

### Story 9.8: Moderation & Performance Operational Dashboard

As a platform operator,
I want comprehensive operational dashboards,
So that I can monitor all aspects of the marketplace.

**Acceptance Criteria:**

**Given** admin dashboard exists
**When** I access /int/v1/admin/dashboard
**Then** Dashboard sections include:
  - **Product Lifecycle**: Published, suspended, flagged counts
  - **Order Routing**: Orders by status, average fulfillment time
  - **Delivery Points**: Hub performance, capacity utilization
  - **Moderation**: Pending issues, actions taken, avg response time
  - **Payment Outcomes**: Success rate, failure reasons, pending count
  - **Member Activity**: Active members, top sellers, engagement metrics

**Given** moderation dashboard exists
**When** I access moderation section
**Then** Dashboard shows:
  - Pending issues count (requiring review)
  - Issues by category (prohibited items, fraud, quality)
  - Average time to resolution
  - Actions distribution (warn vs suspend vs dismiss)
  - Repeat offenders (sellers with multiple issues)

**Given** delivery point dashboard exists
**When** I access delivery section
**Then** Dashboard shows:
  - Orders per hub (current and historical)
  - Average processing time per hub
  - Capacity utilization (current orders vs max)
  - Bottlenecks and delays
  - Hub comparison metrics

**Given** payment dashboard exists
**When** I access payment section
**Then** Dashboard shows:
  - Total payments (confirmed, pending, failed)
  - Success rate percentage
  - Failed payment reasons breakdown
  - Average confirmation time
  - Pending payments requiring attention

**Given** I need detailed reports
**When** I export dashboard data
**Then** CSV/JSON export is available for all metrics
**And** Date range filtering is supported
**And** Store-level drill-down is available
