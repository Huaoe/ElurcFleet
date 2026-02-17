---
stepsCompleted:
  - 1
  - 2
inputDocuments:
  - c:/Projects/Stalabard/_bmad-output/planning-artifacts/product-brief-Stalabard-2026-02-14.md
  - c:/Projects/Stalabard/_bmad-output/planning-artifacts/prd.md
workflowType: 'architecture'
project_name: 'Stalabard'
user_name: 'Thomas'
date: '2026-02-14'
updated: '2026-02-16'
revision: 'Fleetbase-native multi-vendor with Networks, Places, and 3-leg delivery'
---

# Architecture Decision Document

_This document defines the target architecture for MVP implementation using Fleetbase extension patterns (extension -> service -> API route -> event hooks), based on the PRD and product brief._

## 1. Architecture Scope and Alignment

### 1.1 Source Alignment

This architecture is based on:

- `prd.md` as the primary implementation baseline for MVP behavior.
- `product-brief-Stalabard-2026-02-14.md` as strategic direction.

### 1.2 Functional Baseline for MVP

The MVP implementation baseline is:

1. Members-only marketplace (NFT badge ownership required server-side).
2. Every verified member can buy and sell.
3. Seller resources are ownership-scoped (no cross-seller access).
4. Listings are directly publishable by verified members.
5. DAO moderation is reactive (issue/policy violation driven).
6. ELURC-only checkout with non-custodial wallet flow (Phantom first).

### 1.3 Strategic Extensions from Product Brief

The product brief introduces future-ready direction:

- Multi-tenant organizations and governance role depth.
- Proposal -> DAO decision -> listing conversion pipeline.

For MVP, these are included as extensibility points without changing the PRD baseline flow.

---

## 2. High-Level Architecture

### 2.1 Layered Fleetbase Architecture

Stalabard uses the Fleetbase extension architecture:

1. **Extensions**: Custom PHP extensions that extend Fleetbase's core functionality.
2. **Models**: Eloquent ORM models for data persistence and relationships.
3. **Services**: Business logic layer encapsulating domain operations.
4. **API Routes**: RESTful HTTP endpoints with authentication and validation.
5. **Event Hooks**: Event-driven triggers for lifecycle operations and integrations.
6. **Storefront App**: React Native mobile/web app consuming Fleetbase APIs.

### 2.2 Runtime Components

- **Fleetbase Backend (PHP/Laravel)**
  - Core Fleetbase API (`core-api`) with IAM, extensions, and base services.
  - **Storefront Extension**: Products, orders, carts, customers, **Networks** (multi-vendor).
  - **FleetOps Extension**: Places (delivery points), order routing, activity flows.
  - **Pallet Extension** (optional): Inventory management across delivery points.
  - Custom Extensions:
    - **Membership Extension**: NFT verification and member identity.
    - **DAO Governance Extension**: Product approval, moderation, DAO-specific rules.
    - **ELURC Payment Provider**: Custom blockchain payment integration.
- **PostgreSQL / MySQL**
  - Persists Fleetbase core + Storefront + FleetOps + custom extension data.
- **Redis**
  - Session management, cache, job queues, real-time features.
- **Blockchain RPC Integration**
  - NFT ownership verification (Solana RPC).
  - ELURC transaction verification and monitoring.
- **Fleetbase Storefront App (React Native)**
  - Network-mode configuration with Phantom wallet integration.
  - Member buyer/seller interfaces.
- **Fleetbase Navigator App** (optional for MVP)
  - Driver app for managing seller → delivery point logistics.

---

## 3. Domain and Extension Architecture

### 3.1 Custom Extensions

#### A) **Membership Extension** (`fleetbase/membership`)

Purpose: NFT-based membership verification and wallet identity management.

**Eloquent Models:**

- `MemberIdentity`
  - Table: `member_identities`
  - Fields: `uuid`, `wallet_address` (unique indexed), `membership_status` (enum), `verified_at`, `nft_token_account`, `last_verified_at`, `metadata` (JSON)
  - Relationships: `hasOne(MemberProfile)`, `belongsTo(User)` (optional)

- `MemberProfile`
  - Table: `member_profiles`
  - Fields: `uuid`, `member_identity_uuid`, `display_name`, `avatar_url`, `bio`, `metadata` (JSON)
  - Relationships: `belongsTo(MemberIdentity)`, `hasMany(Listing)` (via DAO extension)

**Services:**
- `MembershipVerificationService`: NFT ownership verification via Solana RPC.
- `MemberIdentityService`: CRUD operations for member identities.

**Middleware:**
- `VerifyMemberMiddleware`: Protects routes requiring verified membership.

#### B) **DAO Governance Extension** (`fleetbase/dao-governance`)

Purpose: Extends Fleetbase Storefront Products with DAO-specific governance, moderation, and seller verification.

**Design Principle:** Leverage native Fleetbase `Product` model; add governance metadata via extension tables.

**Eloquent Models:**

- `ProductGovernance` (extends Product)
  - Table: `product_governance`
  - Fields: `uuid`, `product_uuid` (FK to Storefront Product), `seller_member_profile_uuid`, `governance_status` (enum: pending_approval/approved/flagged/suspended), `flagged_at`, `suspended_at`, `metadata` (JSON)
  - Relationships: `belongsTo(Product)` (Storefront), `belongsTo(MemberProfile, 'seller')`, `hasMany(ProductIssue)`
  - Purpose: Track DAO governance state for each product

- `ProductIssue`
  - Table: `product_issues`
  - Fields: `uuid`, `product_uuid`, `reporter_member_profile_uuid`, `status` (enum: pending/reviewing/resolved/dismissed), `reason`, `details`, `created_at`
  - Relationships: `belongsTo(Product)` (Storefront), `belongsTo(MemberProfile, 'reporter')`, `hasMany(ModerationAction)`

- `ModerationAction`
  - Table: `moderation_actions`
  - Fields: `uuid`, `issue_uuid`, `product_uuid`, `moderator_user_uuid`, `action` (enum: warn/suspend/unpublish/dismiss), `rationale`, `previous_status`, `new_status`, `acted_at`
  - Relationships: `belongsTo(ProductIssue)`, `belongsTo(Product)` (Storefront), `belongsTo(User, 'moderator')`

**Services:**
- `ProductGovernanceService`: Extends Storefront Product operations with DAO rules.
- `ModerationService`: Issue reporting and DAO intervention workflows.
- `ProductOwnershipGuard`: Authorization helper ensuring seller can only modify own products.

#### C) **ELURC Payment Provider Extension** (`fleetbase/elurc-payment`)

Purpose: Non-custodial blockchain payment processing for ELURC token.

**Eloquent Models:**

- `PaymentIntentMetadata`
  - Table: `payment_intent_metadata`
  - Fields: `uuid`, `order_uuid`, `transaction_uuid`, `wallet_address`, `token_address`, `amount`, `network`, `tx_hash` (unique indexed), `provider` (elurc), `verification_status` (enum: pending/confirmed/failed), `confirmed_at`, `failure_reason`, `metadata` (JSON)
  - Relationships: `belongsTo(Order)` (Storefront), `belongsTo(Transaction)` (Fleetbase)

**Services:**
- `ElurCPaymentService`: Payment intent creation and transaction verification.
- `SolanaRpcService`: Blockchain RPC integration for transaction verification.
- `PaymentVerificationJob`: Async job for transaction confirmation polling.

**Payment Provider:**
- Implements Fleetbase's payment gateway interface.
- Registers as custom payment method in Storefront.

#### D) **Audit Extension** (integrated into other extensions)

Purpose: Immutable audit trail for governance and payment events.

**Event Listeners:**
- Uses Fleetbase's built-in event system.
- Creates audit records via Laravel event listeners.

**Model (if separate):**
- `AuditEvent`
  - Table: `audit_events`
  - Fields: `uuid`, `event_type`, `actor_type`, `actor_uuid`, `subject_type`, `subject_uuid`, `correlation_id`, `payload` (JSON), `created_at`

### 3.2 Fleetbase Network Configuration

**Network Architecture:**
- **Network Name**: "Stalabard DAO Marketplace"
- **Network Owner**: Platform operator (DAO)
- **Invited Stores**: Individual DAO member sellers (each gets own Fleetbase Console store)
- **Store Independence**: Each seller manages products via their own Fleetbase Console dashboard
- **Network Oversight**: Network owner sees all orders; cannot access store private data

**Network vs Store Keys:**
- Network Key: Used by Storefront App for marketplace-wide browsing
- Store Keys: Individual sellers use for managing their own products

### 3.3 Extension Naming and Structure

Follow Fleetbase extension conventions:

```
fleetbase-membership/
├── server/
│   ├── src/
│   │   ├── Models/
│   │   │   ├── MemberIdentity.php
│   │   │   └── MemberProfile.php
│   │   ├── Services/
│   │   │   ├── MembershipVerificationService.php
│   │   │   └── MemberIdentityService.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   │   └── Resources/
│   │   └── Providers/
│   │       └── MembershipServiceProvider.php
│   ├── migrations/
│   ├── config/
│   └── composer.json
├── addon/ (Ember.js frontend)
└── package.json
```

### 3.4 Cross-Extension Relationships

Define Eloquent relationships across extensions and native Fleetbase models:

- `MemberProfile` -> Storefront `Customer` (1:1 relationship via `customer_uuid`).
- `MemberProfile` -> Storefront `Store` (1:1 for sellers via `store_uuid`).
- `ProductGovernance` -> Storefront `Product` (1:1 via `product_uuid`).
- `ProductGovernance` -> `MemberProfile` (N:1 via `seller_member_profile_uuid`).
- `ProductIssue` -> Storefront `Product` (N:1 via `product_uuid`).
- `ModerationAction` -> `ProductIssue` and Storefront `Product` (foreign keys).
- `PaymentIntentMetadata` -> Storefront `Order` (1:1 via `order_uuid`).

Use Eloquent's `with()` and query builder for cross-extension data retrieval.

### 3.5 Delivery Point Architecture (FleetOps Places)

**Places as Grocery Store Hubs:**
- Create FleetOps `Place` records for each grocery store delivery point
- Tag places: `type: 'delivery_hub'`, `subtype: 'grocery_store'`
- Configure operating hours per Place
- Set geographic zones/service areas

**3-Leg Order Flow:**
1. **Leg 1**: Seller → Grocery Store (Place)
   - Activity: "Dispatched to Hub"
   - Driver (optional): Seller self-delivers or assigned driver
2. **Leg 2**: Arrival at Grocery Store
   - Activity: "At Delivery Point"
   - Grocery store notified of incoming order
3. **Leg 3**: Customer Pickup
   - Activity: "Ready for Pickup"
   - Customer collects from grocery store
   - POD (Proof of Delivery): Customer signature/code

**Order Configuration:**
- Custom FleetOps Order Config with 3-leg activity flow
- Custom fields: `grocery_store_place_uuid`, `pickup_code`, `seller_store_uuid`
- Status transitions: Created → Dispatched → At Hub → Ready → Completed

---

## 4. Access Control Architecture

### 4.1 Authentication

- **Storefront API routes**: Fleetbase authentication (API keys, tokens, or sessions) + custom membership verification middleware.
- **Internal/Admin routes**: Fleetbase IAM with role-based permissions (DAO moderators).
- Authentication leverages Fleetbase's built-in `AuthenticatesRequests` middleware.

### 4.2 Authorization Model

**Roles (using Fleetbase IAM):**
- **Member**: Verified DAO NFT holder with buyer + seller capabilities.
- **Moderator**: DAO governance role with moderation action permissions.
- **Admin**: Platform operator role (limited to system config, no governance override).

**Authorization Rules:**
- **Seller scope rule**: Enforced via `ListingOwnershipGuard` service.
  - Validates: `$listing->seller_member_profile_uuid === $authenticatedMember->uuid`
- **Moderator scope**: Can act on any listing/issue within DAO governance scope.
- **Member-only access**: All protected routes gated by `VerifyMemberMiddleware`.

### 4.3 Enforcement Location

- **Route Middleware**: Handles authentication, membership verification, role checks.
- **Service Layer**: Enforces ownership validation and policy rules (never trust client).
- **Laravel Policies**: Define authorization rules for models (optional but recommended).
- **No client-side security**: Frontend is untrusted; all checks are server-side.

---

## 5. Service and Event Architecture (Business Logic Flows)

All business logic is encapsulated in service classes with event-driven hooks. Key service operations:

### 5.1 Core Service Operations

**1. `MembershipVerificationService::verifyMembership()`**
- Input: `wallet_address`, `signature` (wallet ownership proof)
- Validates NFT ownership via Solana RPC for configured DAO collection
- Creates or updates `MemberIdentity` with verification status
- Returns: Verification result with member profile

**2. `ProductGovernanceService::createProduct()`**
- Validates member eligibility (membership status)
- Creates Storefront `Product` via Storefront API
- Creates linked `ProductGovernance` record with seller attribution
- Sets initial `governance_status: 'approved'` (direct publish for MVP)
- Returns: Created product with governance metadata

**3. `ProductGovernanceService::publishProduct()`**
- Validates ownership (`ProductOwnershipGuard`)
- Validates membership status
- Updates Storefront Product status to `published`
- Updates `ProductGovernance.governance_status: 'approved'`
- Fires `ProductPublished` event
- Returns: Updated product

**4. `ModerationService::reportIssue()`**
- Creates `ProductIssue` with reporter attribution
- Links to Storefront `Product` and `ProductGovernance`
- Fires `IssueReported` event (triggers moderator notifications)
- Returns: Created issue

**5. `ModerationService::applyModerationAction()`**
- Moderator role required
- Validates deterministic state transition
- Creates `ModerationAction` record with rationale
- Updates Storefront Product status (unpublish if suspended)
- Updates `ProductGovernance.governance_status`
- Fires `ModerationActionApplied` event
- Returns: Moderation action result

**6. `OrderRoutingService::createThreeLegOrder()` (FleetOps integration)**
- Creates FleetOps Order with custom 3-leg activity flow
- Sets waypoints: Seller Location → Grocery Store Place → Customer
- Initializes activity status: `created`
- Links to Storefront Order via `order_uuid`
- Returns: Created FleetOps order with routing

**7. `OrderRoutingService::updateOrderActivity()`**
- Updates FleetOps Order activity status
- Transitions: `dispatched_to_hub` → `at_delivery_point` → `ready_for_pickup` → `completed`
- Sends notifications to relevant parties (seller, grocery store, customer)
- Fires activity change events
- Returns: Updated order with new activity status

**8. `ElurCPaymentService::validateProvider()`**
- Validates payment provider/token == ELURC only
- Throws exception if invalid provider selected
- Returns: Validation result

**9. `ElurCPaymentService::verifyAndCompleteOrder()`**
- Idempotent: Checks for existing verification by `tx_hash`
- Verifies on-chain transaction:
  - Token address matches ELURC
  - Amount matches order total
  - Transaction confirmed (configurable depth)
- Creates/updates `PaymentIntentMetadata`
- Completes Storefront order if verified
- Fires `PaymentVerified` or `PaymentFailed` event
- Returns: Order completion result

### 5.2 Service Design Principles

- **Idempotency**: Payment verification and publish operations are idempotent.
- **Database Transactions**: Use Laravel DB transactions for multi-step operations.
- **Retry Safety**: All state transitions are deterministic and retry-safe.
- **Event-Driven**: Services fire Laravel events for audit, notifications, and side effects.
- **Separation of Concerns**: Services handle business logic; controllers handle HTTP.

### 5.3 Event-Driven Hooks

**Events:**
- `MemberVerified`
- `ProductCreated`, `ProductPublished`, `ProductSuspended`, `ProductUnpublished` (Storefront + Governance)
- `IssueReported`, `IssueStatusChanged`
- `ModerationActionApplied`
- `OrderActivityChanged` (FleetOps integration)
- `OrderDispatchedToHub`, `OrderAtDeliveryPoint`, `OrderReadyForPickup`
- `PaymentIntentCreated`, `PaymentVerified`, `PaymentFailed`

**Listeners:**
- `SendMemberWelcomeNotification`
- `CreateAuditEventListener` (logs all governance/payment/order events)
- `NotifyModeratorsOfIssue`
- `NotifyGroceryStoreOfIncomingOrder` (on `OrderDispatchedToHub`)
- `NotifyCustomerOrderReady` (on `OrderReadyForPickup`)
- `SendOrderConfirmationNotification`

---

## 6. API Architecture

### 6.1 Route Groups

**Storefront API Routes** (Member/Public)

Base: `/storefront/v1` (Native Storefront extension routes + custom extensions)

```php
// Membership (Custom Extension)
POST   /membership/verify         // Verify wallet + NFT ownership
GET    /membership/status         // Check current member status
GET    /membership/profile        // Get authenticated member profile
POST   /membership/store/create   // Create seller store in Network (invitation flow)

// Products (Native Storefront + DAO Governance Extension)
POST   /products                  // Create product (native Storefront + governance metadata)
PATCH  /products/{id}             // Update own product
POST   /products/{id}/publish     // Publish product (governance check)
GET    /products/mine             // List own products (seller view)
DELETE /products/{id}             // Unpublish/delete own product
GET    /products                  // Browse published products (network-wide, filtered by governance)
GET    /products/{id}             // Get product details

// Issues (DAO Governance Extension)
POST   /products/{id}/issues      // Report product issue
GET    /issues                    // List own reports
GET    /issues/{id}               // Get issue status

// Orders & Delivery (Storefront + FleetOps Integration)
POST   /checkout                  // Create checkout (Storefront + FleetOps routing)
GET    /orders/mine               // List own orders (buyer/seller view)
GET    /orders/{id}               // Get order details with delivery status
PATCH  /orders/{id}/activity      // Update order activity (seller: dispatch to hub)

// Payments (Custom ELURC Extension)
POST   /payments/elurc/intent     // Create ELURC payment intent
POST   /payments/verify           // Submit tx_hash for verification
GET    /payments/status/{order_id} // Poll payment/order status

// Delivery Points (FleetOps Places - read-only for customers)
GET    /places                    // List grocery store delivery points
GET    /places/{id}               // Get delivery point details (hours, location)
```

**Internal/Admin Routes** (DAO Moderators)

Base: `/int/v1` (Fleetbase internal API pattern)

```php
// Network Management (network owner)
GET    /network                   // Get network details
POST   /network/stores/invite     // Invite seller to join network
GET    /network/stores            // List all stores in network
PATCH  /network/stores/{id}/status // Approve/suspend store

// Moderation (moderator role required)
GET    /moderation/issues         // List flagged product issues (queue)
GET    /moderation/issues/{id}    // Get issue details
POST   /moderation/issues/{id}/actions // Apply moderation action
GET    /moderation/actions        // List moderation history

// Delivery Point Management (FleetOps Places)
POST   /places                    // Create grocery store delivery point
PATCH  /places/{id}               // Update delivery point (hours, capacity)
GET    /places                    // List all delivery points

// Order Oversight (FleetOps integration)
GET    /orders                    // List all orders (network-wide)
GET    /orders/{id}/routing       // View order routing/activity flow

// Admin oversight
GET    /admin/products            // List all products (admin view with governance status)
GET    /admin/members             // List all members
GET    /admin/audit-events        // Audit trail
```

### 6.2 API Standards

- **Validation**: Use Laravel Form Requests for input validation.
- **Authentication**: Apply Fleetbase's authentication middleware + custom `VerifyMemberMiddleware`.
- **Authorization**: Use Laravel Policies or service-layer guards.
- **Response Format**: Follow Fleetbase's JSON API response structure:
  ```json
  {
    "data": {...},
    "meta": {...},
    "links": {...}
  }
  ```
- **Error Handling**: Use Fleetbase's exception handler with structured error responses.
- **Business Logic**: Keep in service layer, not controllers.
- **RESTful Conventions**: Use proper HTTP verbs (GET, POST, PATCH, DELETE).

---

## 7. Payment Architecture (ELURC, Non-Custodial)

### 7.1 Payment Principles

- **Non-Custodial**: Platform never handles or stores private keys.
- **Client-Side Signing**: Buyer signs transaction in Phantom wallet (browser/mobile).
- **Server-Side Verification**: Backend verifies on-chain transaction before order completion.
- **ELURC-Only**: Payment provider enforces ELURC token; rejects other providers.

### 7.2 Payment Processing Sequence (with 3-Leg Order Routing)

1. **Checkout Initiation**
   - Member creates order via Storefront API with selected delivery point (Place)
   - `ElurCPaymentService::createPaymentIntent()` generates payment session
   - `OrderRoutingService::createThreeLegOrder()` creates FleetOps order with routing
   - Returns: Payment details (recipient address, amount, token address, network) + delivery point info

2. **Client-Side Transaction**
   - Storefront app prompts Phantom wallet signature
   - User approves and broadcasts transaction
   - App receives `tx_hash` (transaction signature)

3. **Transaction Submission**
   - App submits `tx_hash` to `POST /payments/verify`
   - Creates `PaymentIntentMetadata` with `verification_status: pending`

4. **Server-Side Verification** (`ElurCPaymentService::verifyTransaction()`)
   - Queries Solana RPC for transaction details
   - Validates:
     - Token address == configured ELURC token
     - Amount matches order total
     - Recipient matches platform wallet
     - Network matches configured network
     - Transaction confirmed (configurable confirmation count)
   - Updates `verification_status`: `confirmed` or `failed`

5. **Order Completion & Routing Initiation**
   - If `confirmed`:
     - Complete Storefront order
     - Fire `PaymentVerified` event
     - **Initialize FleetOps order routing**: Status = `created`, awaiting seller dispatch
     - Notify seller of new order with delivery hub details
   - If `failed`: Return error with `failure_reason`, fire `PaymentFailed` event
   - If `pending`: Return polling status (client polls `/payments/status/{order_id}`)

6. **Async Confirmation** (Optional)
   - `PaymentVerificationJob` queued for async polling
   - Retries verification until confirmed or timeout

### 7.3 Idempotency and Reconciliation

- **Idempotency Key**: `tx_hash` (unique constraint on `payment_intent_metadata.tx_hash`)
- Duplicate verification requests return existing result
- Polling endpoint (`/payments/status/{order_id}`) is safe to call repeatedly
- **Reconciliation Job** (post-MVP): Scheduled job to re-verify `pending` payments past threshold

### 7.4 Order Routing After Payment

**Post-Payment Workflow:**

1. **Order Created** (Payment verified)
   - Seller receives notification with:
     - Order details
     - Delivery hub (grocery store Place) with address and hours
     - Delivery deadline

2. **Seller Dispatches** (`PATCH /orders/{id}/activity`)
   - Seller marks order as "Dispatched to Hub"
   - FleetOps order status: `dispatched_to_hub`
   - Grocery store receives notification of incoming order
   - Optional: Assign driver for delivery (seller self-delivers for MVP)

3. **Arrival at Grocery Store**
   - Seller/driver updates status: "At Delivery Point"
   - FleetOps order status: `at_delivery_point`
   - Grocery store confirms receipt (optional POD)

4. **Ready for Customer Pickup**
   - Grocery store marks: "Ready for Pickup"
   - FleetOps order status: `ready_for_pickup`
   - Customer receives notification with pickup code

5. **Customer Pickup**
   - Customer presents pickup code at grocery store
   - Grocery store confirms handoff
   - FleetOps order status: `completed`
   - Seller receives completion notification

---

## 8. Observability and Audit Architecture

### 8.1 Observability

- **NFR-030**: Correlate logs by `member_uuid`, `product_uuid`, `issue_uuid`, `order_uuid`, `fleetops_order_uuid`, `tx_hash`, `correlation_id`.
- **NFR-031**: Emit Laravel events for all critical operations:
  - Product lifecycle (created/published/updated/suspended)
  - Order routing (dispatched/at_hub/ready/completed)
  - Payment status (confirmed/failed/pending)
  - Moderation actions
  - Membership verification
  - Delivery point notifications
- **NFR-032**: Use Laravel's logging system with structured context.
- **NFR-033**: Integrate with monitoring tools (Laravel Telescope for dev, Sentry for production).
- **NFR-034**: Track performance metrics: API response times, RPC latency, payment verification duration.

### 8.2 Audit Events (Minimum)

**Laravel Events to Log:**
- `ProductCreated`, `ProductPublished`, `ProductUpdated`, `ProductSuspended`, `ProductUnpublished` (Storefront + Governance)
- `IssueReported`, `IssueStatusChanged`
- `ModerationActionApplied` (with actor, rationale, state transition)
- `OrderDispatchedToHub`, `OrderAtDeliveryPoint`, `OrderReadyForPickup`, `OrderCompleted` (FleetOps integration)
- `PaymentIntentCreated`, `PaymentVerified`, `PaymentFailed`
- `MemberVerified`, `MemberVerificationFailed`
- `DeliveryPointNotified`, `CustomerNotifiedPickup`

**Audit Storage:**
- Option 1: Dedicated `audit_events` table with JSON payload
- Option 2: Laravel's event log with structured context
- Option 3: External audit service (e.g., LogStash, Datadog)

### 8.3 Operational Dashboards

**Admin Dashboard Views:**
- **Product Lifecycle**: Status distribution, publish rate, suspension rate (governance)
- **Order Routing**: Active orders by status (dispatched/at_hub/ready), delivery time metrics
- **Delivery Point Performance**: Orders per hub, average processing time, capacity utilization
- **Moderation Backlog**: Pending issues, response time, action distribution
- **Payment Outcomes**: Success rate, failure reasons, pending count
- **Authorization Denials**: Failed access attempts by reason (non-member, wrong owner, etc.)
- **Member Activity**: Verification rate, active members, top sellers, store participation in Network
- **Network Health**: Total stores, active stores, order volume by store

**Implementation:**
- Use Fleetbase's dashboard widgets system
- Query aggregated data from models with Eloquent
- Real-time updates via Laravel Echo/Redis

---

## 9. Non-Functional Architecture Controls

### 9.1 Security

- **Membership Verification**: Server-side NFT ownership checks gate all protected operations
- **Ownership Enforcement**: Service-layer guards validate seller ownership before mutations
- **Secret Management**: RPC endpoints, API keys, and private credentials in `.env` only
- **Authorization**: Deny-by-default for non-member and cross-seller access
- **Input Validation**: Laravel Form Requests validate all user input
- **SQL Injection Prevention**: Eloquent ORM and parameterized queries only
- **CSRF Protection**: Laravel's CSRF middleware on state-changing routes
- **Rate Limiting**: Apply Laravel rate limiters on public endpoints

### 9.2 Reliability

- **Idempotency**: Payment verification and publish operations are idempotent by design
- **Database Transactions**: Use Laravel DB transactions for multi-step operations
- **Retry Safety**: All state transitions are deterministic and safe to retry
- **Queue Jobs**: Use Laravel queues for async operations (payment polling, notifications)
- **Job Retries**: Configure retry logic with exponential backoff
- **Error Handling**: Graceful degradation with meaningful error messages

### 9.3 Performance

- **Query Optimization**: 
  - Eager load relationships with `with()` to avoid N+1 queries
  - Use database indexes on frequently queried fields
  - Paginate all list endpoints (listings, orders, issues)
- **Indexed Fields**: `wallet_address`, `seller_member_profile_uuid`, `status`, `tx_hash`, `created_at`, `order_uuid`
- **Caching Strategy**:
  - Membership verification results (TTL: 5-15 minutes)
  - Published listings (cache invalidation on publish/suspend)
  - Use Redis for cache backend
- **Connection Pooling**: Configure Laravel database connection pooling
- **CDN**: Static assets and media served via CDN

### 9.4 Observability (continued from Section 8)

- **Error Tracking**: Integrate Sentry or similar for production error monitoring
- **Performance Monitoring**: Laravel Telescope for development, APM tools for production
- **Database Query Monitoring**: Log slow queries, set query time thresholds
- **RPC Monitoring**: Track Solana RPC latency and failures
- **Health Checks**: Endpoints for liveness and readiness probes

---

## 10. Deployment and Environment Strategy

### 10.1 Environments

- **Local**: Docker-based Fleetbase instance for development
- **Staging**: Cloud deployment (test DAO NFT collection)
- **Production**: Production Fleetbase instance with production DAO
- Same migration lineage across all environments

### 10.2 Deployment Options

**Self-Hosted:**
- Docker Compose (recommended for MVP)
- Kubernetes (via Fleetbase Helm charts)

**Configuration Management:**
- `.env` files for environment-specific settings
- Laravel config files for extension settings

### 10.3 Required Configuration

**Environment Variables:**

```bash
# DAO Governance
DAO_ADDRESS=D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq
DAO_NFT_COLLECTION=3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c

# ELURC Token
ELURC_TOKEN_ADDRESS={elurc_token_mint_address}
ELURC_NETWORK=mainnet-beta # or devnet for testing
ELURC_PLATFORM_WALLET={platform_recipient_wallet}

# Solana RPC
SOLANA_RPC_URL=https://api.mainnet-beta.solana.com
SOLANA_CONFIRMATION_DEPTH=10 # confirmations required
SOLANA_VERIFICATION_TIMEOUT=300 # seconds

# Fleetbase
FLEETBASE_URL=https://yourdomain.com
FLEETBASE_API_KEY={storefront_api_key}
```

### 10.4 Release Gate for MVP

**End-to-End Test Suite:**

1. Member wallet connects → NFT verification → member profile created
2. Member creates listing → publishes listing → listing visible in marketplace
3. Member buyer adds to cart → initiates checkout
4. ELURC payment via Phantom → transaction broadcast
5. Payment verification → order completion → seller notification

**Automated Test Suites:**

- **Members-only access**: Non-members denied on protected routes
- **Seller ownership isolation**: Seller A cannot access Seller B's resources
- **DAO issue intervention**: Moderator can flag/suspend listings with rationale
- **ELURC-only enforcement**: Non-ELURC payment attempts fail gracefully
- **Audit trail**: All governance/payment events logged

**Performance Baselines:**
- Membership verification: < 2s (RPC latency dependent)
- Listing queries: < 600ms P95
- Payment verification: < 5s (confirmation depth dependent)

---

## 11. Implementation Roadmap (Architecture-First)

### Phase 1: Foundation (Weeks 1-2)

1. **Setup Fleetbase Instance**
   - Install Fleetbase via Docker
   - Configure environment variables
   - Verify **Storefront extension** is installed
   - Verify **FleetOps extension** is installed
   - Install **Pallet extension** (optional for MVP)

2. **Configure Network Architecture**
   - Create Stalabard DAO Network in Fleetbase Console
   - Set network currency to ELURC (or USD with custom payment override)
   - Generate Network Key for Storefront App
   - Configure network invitation settings

3. **Setup Delivery Points (Places)**
   - Create FleetOps Places for grocery store delivery hubs
   - Tag places: `type: 'delivery_hub'`, `subtype: 'grocery_store'`
   - Configure operating hours and service areas
   - Define geographic zones for routing

4. **Create Extension Skeletons**
   - Use Fleetbase CLI to scaffold extensions:
     - `fleetbase/membership`
     - `fleetbase/dao-governance`
     - `fleetbase/elurc-payment`
   - Setup directory structure per section 3.3

5. **Database Migrations**
   - Create migrations for custom models (ProductGovernance, ProductIssue, etc.)
   - Define foreign keys to Storefront Product and FleetOps Order
   - Create indexes for performance
   - Run migrations on local environment

### Phase 2: Core Services (Weeks 3-4)

6. **Implement Membership Extension**
   - `MemberIdentity` and `MemberProfile` models
   - `MembershipVerificationService` with Solana RPC integration
   - `VerifyMemberMiddleware`
   - Network store creation workflow (invite sellers to network)
   - Test NFT verification flow

7. **Implement DAO Governance Extension**
   - `ProductGovernance`, `ProductIssue`, `ModerationAction` models
   - `ProductGovernanceService` extending Storefront Product operations
   - `ProductOwnershipGuard` for seller-scoped access
   - `ModerationService` for reactive governance
   - Event listeners for audit trail
   - Integration with native Storefront Product model

8. **Implement FleetOps Order Routing Integration**
   - `OrderRoutingService` for 3-leg delivery workflow
   - Custom FleetOps Order Config with activity flow statuses
   - Integration with Storefront Order creation
   - Notification service for seller/hub/customer updates

9. **Implement ELURC Payment Provider**
   - `PaymentIntentMetadata` model
   - `ElurCPaymentService` with transaction verification
   - `SolanaRpcService`
   - Register as Fleetbase payment provider
   - Integration with order routing initialization

### Phase 3: API Layer (Weeks 5-6)

10. **Implement Storefront API Routes**
    - Membership verification routes
    - Product creation/management (extends Storefront)
    - Product governance routes (publish with checks)
    - Issue reporting routes
    - Order activity update routes (dispatch to hub)
    - Payment verification endpoints
    - Delivery point (Places) read routes
    - Apply authentication + authorization middleware

11. **Implement Admin/Moderation Routes**
    - Network management routes (invite stores, view network)
    - Delivery point management (create/update Places)
    - Moderation queue and action routes
    - Order oversight routes (network-wide view)
    - Admin oversight routes
    - Apply role-based authorization

### Phase 4: Integration & Testing (Weeks 7-8)

12. **Storefront App Customization**
    - Configure app for Network mode (network key)
    - Integrate Phantom wallet SDK
    - Implement membership verification flow
    - Build seller product management UI (native Storefront + governance)
    - Implement delivery point selection UI (browse Places)
    - Implement 3-leg order tracking UI (status updates)
    - Implement ELURC checkout flow with delivery point selection

13. **Testing & QA**
    - Unit tests for all services (governance, routing, payment)
    - Integration tests for API routes
    - FleetOps order routing tests (3-leg workflow)
    - E2E test suite (see section 10.4)
    - Security audit (ownership isolation, auth, network boundaries)

14. **Observability**
    - Configure structured logging with FleetOps correlation
    - Setup audit event listeners (products, orders, payments)
    - Create admin dashboards:
      - Network health (stores, orders)
      - Delivery point performance
      - Order routing status
      - Moderation queue
      - Payment outcomes

### Phase 5: Staging & Production (Weeks 9-10)

15. **Staging Deployment**
    - Deploy to staging environment
    - Configure testnet DAO NFT collection
    - Create test Network with sample stores
    - Setup test delivery points (Places)
    - Run full E2E test suite (3-leg order flow)
    - Load testing

16. **Production Launch**
    - Deploy to production
    - Configure mainnet settings (DAO NFT, ELURC token)
    - Create production Network "Stalabard DAO Marketplace"
    - Invite initial sellers to network
    - Setup production grocery store delivery points
    - Monitor error logs and performance
    - Monitor FleetOps order routing metrics
    - Gradual rollout to DAO members

---

## 12. Open Decisions to Finalize

1. **Membership Verification Caching**
   - Cache TTL for NFT ownership checks (recommended: 5-15 minutes)
   - Refresh triggers (on critical actions like payment/moderation)
   - Fallback behavior on RPC failure (deny access vs. cached status)

2. **Payment Confirmation Policy**
   - Required confirmations (recommended: 10 for mainnet, 1 for devnet)
   - Verification timeout threshold (recommended: 5 minutes)
   - Retry strategy for pending transactions
   - Reconciliation job frequency

3. **MVP Tenancy Depth**
   - **Option A**: Member-as-seller scope only (strict PRD baseline)
     - Simpler MVP, faster to market
     - Each member is independent seller
   - **Option B**: Organization boundary metadata (future-ready)
     - Add `organization_uuid` to models
     - Keep direct publish for MVP
     - Enable multi-seller organizations post-MVP
   - **Recommendation**: Option A for MVP

4. **Post-MVP Governance Pipeline**
   - Proposal-first listing approval (toggle in admin)
   - DAO voting integration (Realms, Squads, or custom)
   - Automated moderation rules engine

5. **Storefront App Deployment**
   - White-label app or fork of `fleetbase/storefront-app`
   - App store distribution strategy
   - Web app deployment (PWA vs. traditional SPA)

---

## 13. Final Architecture Statement

Stalabard MVP will be implemented as a Fleetbase-based extensible marketplace platform where membership-gated access, ownership-scoped seller operations, reactive DAO moderation, and ELURC-only non-custodial checkout are enforced server-side through service-layer business logic and event-driven architecture. 

The platform leverages:
- **Fleetbase's Storefront extension** for core e-commerce functionality
- **Custom PHP extensions** for DAO-specific governance and blockchain integration
- **Eloquent ORM** for data persistence and relationships
- **Laravel's event system** for audit trails and notifications
- **Fleetbase's IAM** for role-based access control
- **React Native Storefront app** for member buyer/seller experiences

The architecture prioritizes:
- Server-side security boundaries (no client-side trust)
- Deterministic state transitions (idempotent operations)
- Auditable governance/payment trails (immutable event logs)
- Extensibility for future multi-tenant and proposal-first governance
- Non-custodial blockchain integration (user owns private keys)
