---
stepsCompleted:
  - step-01-init
  - step-02-discovery
  - step-03-success
  - step-04-journeys
  - step-05-domain
  - step-06-innovation
  - step-07-project-type
  - step-08-scoping
  - step-09-functional
  - step-10-nonfunctional
  - step-11-polish
  - step-12-complete
inputDocuments:
  - c:/Projects/Stalabard/_bmad-output/planning-artifacts/product-brief-Stalabard-2026-02-14.md
  - c:/Projects/Stalabard/.env
workflowType: 'prd'
date: '2026-02-14'
classification:
  domain: general
  projectType: blockchain_web3
documentCounts:
  briefCount: 1
  researchCount: 0
  brainstormingCount: 0
  projectDocsCount: 0
---

# Product Requirements Document - Stalabard

**Author:** Thomas  
**Date:** 2026-02-14  
**Updated:** 2026-02-16 (Fleetbase-native: Networks, Places, 3-leg delivery)

## Executive Summary

Stalabard is a members-only marketplace for the Elurc DAO ecosystem built on Fleetbase's extensible logistics and supply chain operating system. The platform leverages **Fleetbase Networks** for multi-vendor architecture, **FleetOps Places** for grocery store delivery points, and **3-leg order routing** for seller → delivery hub → customer pickup flows.

Verified members (DAO NFT badge holders) can both sell and buy. Sellers operate independent stores within the Stalabard Network, managing products via Fleetbase's native Storefront. Orders are delivered to designated grocery stores where customers pick them up.

The MVP enforces server-side membership verification, strict seller ownership boundaries (store-scoped), reactive DAO moderation for flagged products, and ELURC-only non-custodial checkout with Phantom wallet.

## 1) Product Context

Stalabard is a DAO-governed marketplace built on Fleetbase's modular platform, leveraging **Storefront** (products, orders, networks), **FleetOps** (places, order routing), and custom extensions for DAO membership and ELURC payments.

Access is members-only: people holding the DAO NFT badge can use the platform. Every verified member can both sell and buy.

**Key Architecture Decisions:**
- **Fleetbase Network**: "Stalabard DAO Marketplace" operates as a multi-vendor Network where each seller is an invited Store
- **Native Products**: Sellers create products using Fleetbase's Storefront Product model, extended with DAO governance metadata
- **Delivery Points**: Orders are routed through grocery stores configured as FleetOps Places
- **3-Leg Delivery**: Seller → Grocery Store (delivery hub) → Customer pickup

### 1.1 Objectives

- Enable verified members to join Network as sellers with independent stores.
- Enable sellers to create and publish products directly using native Fleetbase Storefront.
- Route orders through grocery store delivery hubs for customer pickup.
- Let DAO moderators intervene only when a product is flagged or policy violation detected.
- Restrict all platform usage to verified community members.
- Ensure sellers only access and manage their own store resources (store-scoped).
- Support ELURC-only checkout with non-custodial wallets.
- Start wallet support with Phantom, then extend later.

### 1.2 Constraints

- **Payment**: ELURC only for MVP.
- **Wallet**: Non-custodial (no private key custody by platform).
- **Membership**: Established through DAO NFT badge ownership.
- **Access**: Only verified members can access platform features.
- **Seller Scoping**: Sellers scoped to their own store resources (products, store orders).
- **Delivery**: Orders must route through grocery store delivery points (FleetOps Places).
- **Network**: All sellers operate within "Stalabard DAO Marketplace" Network.
- **DAO governance scope** and membership proofs are tied to:
  - DAO address: `D6d8TZrNFwg3QG97JLLfTjgdJequ84wMhQ8f12UW56Rq`
  - NFT collection: `3e22667e998143beef529eda8c84ee394a838aa705716c3b6fe0b3d5f913ac4c`

## 2) Personas and Key Jobs

### 2.1 Community Member (Seller + Buyer)

**As Seller:**
- Joins Stalabard Network by creating/joining a Store
- Creates and publishes products using native Fleetbase Storefront
- Manages only own store resources (products, store orders)
- Receives orders and dispatches to designated delivery hubs (grocery stores)
- Tracks order routing status (dispatched → at hub → ready → completed)
- Receives payment settlement after order completion

**As Buyer:**
- Browses products across all stores in the Network
- Selects delivery point (grocery store Place) during checkout
- Completes ELURC payment via Phantom wallet
- Tracks order delivery status
- Picks up order from designated grocery store

### 2.2 DAO Moderator

- Intervenes on flagged product issues or policy violations.
- Can suspend/unpublish products with policy rationale.
- Views moderation queue across entire Network.
- Needs auditable history of all governance actions.
- Can approve/suspend stores in Network if needed.

### 2.3 Platform Operator (Secondary)

- Maintains Network configuration (invites initial sellers).
- Manages delivery point (Place) configuration (grocery stores).
- Monitors system health and order routing performance.
- Does not bypass membership and governance rules.

### 2.4 Grocery Store (Delivery Hub)

- Receives notifications of incoming orders from sellers.
- Confirms order receipt when seller/driver delivers.
- Marks orders "Ready for Pickup" when customer can collect.
- Confirms customer pickup with pickup code.

## 3) Core User Journeys

### Journey A: Member Becomes Seller

1. Verified member connects Phantom wallet and passes membership verification (NFT badge check).
2. Member requests to join Stalabard Network as seller.
3. Platform creates Store for member within Network.
4. Seller gains access to Fleetbase Console dashboard for product management.
5. Seller can now create and publish products.

### Journey B: Seller Creates Product

1. Seller logs into Fleetbase Console (or uses Storefront App seller view).
2. Seller creates product using native Storefront Product interface.
3. System creates ProductGovernance record linking to seller's MemberProfile.
4. Seller publishes product directly (governance status: approved).
5. Product is available to buyers across entire Network.

### Journey C: Member Buying Flow with 3-Leg Delivery

1. Verified member connects Phantom wallet.
2. Platform verifies member status (NFT badge ownership).
3. Buyer browses published products across all Network stores.
4. Buyer adds items to cart and proceeds to checkout.
5. **Buyer selects delivery point** (grocery store Place from available hubs).
6. System creates ELURC payment intent + FleetOps order with 3-leg routing.
7. Buyer signs and broadcasts payment transaction via Phantom.
8. Platform verifies transaction and initializes order routing.

### Journey D: 3-Leg Order Fulfillment

1. **Seller Dispatch**: Seller receives order notification with delivery hub details.
2. **Leg 1**: Seller dispatches order to designated grocery store (updates status: "Dispatched to Hub").
3. **Leg 2**: Seller/driver delivers to grocery store (updates status: "At Delivery Point").
4. **Hub Confirmation**: Grocery store confirms receipt and stores order.
5. **Leg 3**: Grocery store marks order "Ready for Pickup" when customer can collect.
6. **Customer Pickup**: Customer arrives with pickup code, grocery store confirms handoff.
7. **Completion**: Order status updated to "Completed", seller receives completion notification.

### Journey E: Seller Ownership Scope

1. Seller views own store's products/orders in Fleetbase Console (store-scoped).
2. Seller attempts to access another store's resources → request is denied by Network permissions.
3. DAO moderator can view/act on products across entire Network for governance.

## 4) Domain Model (MVP)

### 4.1 Native Fleetbase Entities (Leveraged)

- **Network**: Multi-vendor marketplace container ("Stalabard DAO Marketplace")
- **Store**: Individual seller's storefront within the Network (one per seller)
- **Product**: Native Storefront product model (seller's listed items)
- **Order**: Native Storefront order model
- **Place**: FleetOps delivery point model (grocery stores configured as delivery hubs)
- **FleetOps Order**: Routing and activity tracking for 3-leg delivery
- **Customer**: Native Storefront customer model (linked to MemberProfile)

### 4.2 Custom Extension Entities

**Membership Extension:**
- **MemberIdentity**: Membership verification state and wallet linkage
- **MemberProfile**: User-facing profile, member metadata, linked to Store (for sellers)

**DAO Governance Extension:**
- **ProductGovernance**: Extends Product with DAO governance metadata (status, seller attribution)
- **ProductIssue**: Reported issue attached to a Product
- **ModerationAction**: DAO intervention action and rationale

**ELURC Payment Extension:**
- **PaymentIntentMetadata**: Transaction hash, chain/network, wallet address, token amount, verification status

### 4.3 Key Relationships

**Membership:**
- MemberIdentity 1..1 MemberProfile
- MemberProfile 1..1 Store (for sellers)
- MemberProfile 1..1 Customer (for buyers)

**Products & Governance:**
- Store 1..* Product (native Storefront)
- Product 1..1 ProductGovernance (governance metadata)
- ProductGovernance N..1 MemberProfile (seller attribution)
- Product 1..* ProductIssue
- ProductIssue 1..* ModerationAction

**Orders & Delivery:**
- Customer 1..* Order (native Storefront)
- Order 1..1 PaymentIntentMetadata
- Order 1..1 FleetOps Order (routing and tracking)
- FleetOps Order N..1 Place (delivery hub)

**Network Structure:**
- Network 1..* Store (multi-vendor)
- Network 1..* Place (delivery points)

## 5) Functional Requirements

### 5.1 Membership and Access Control

- **FR-001**: System SHALL allow platform access only to verified community members.
- **FR-002**: System SHALL verify membership using DAO NFT badge ownership.
- **FR-003**: Every verified member SHALL be able to act as both seller and buyer.
- **FR-004**: Seller views SHALL only expose that seller's own listings and seller operations.
- **FR-005**: System SHALL deny non-member access to protected routes and actions.

### 5.2 Product Publication and DAO Intervention

- **FR-010**: Verified member (seller with Store) SHALL create product with title, description, category, price, media, and metadata using native Fleetbase Storefront.
- **FR-011**: Seller SHALL publish product directly without pre-approval (governance status: approved).
- **FR-012**: DAO moderator SHALL intervene only when a product is flagged or policy violation is detected.
- **FR-013**: Intervention SHALL support actions such as warning, suspend, or unpublish with explicit rationale.
- **FR-014**: All intervention actions SHALL be auditable and visible to the product owner (seller).

### 5.3 Products, Orders, and Delivery

- **FR-020**: Buyers SHALL see only currently published and non-suspended products (governance-filtered).
- **FR-021**: Sellers SHALL manage only their own store's products and orders (store-scoped).
- **FR-022**: Orders SHALL preserve buyer and seller attribution.
- **FR-023**: Buyers SHALL select a delivery point (grocery store Place) during checkout.
- **FR-024**: Orders SHALL route through selected delivery point for customer pickup.
- **FR-025**: System SHALL track 3-leg order flow: seller → delivery hub → customer pickup.
- **FR-026**: Sellers SHALL receive order notifications with delivery hub details (address, hours).
- **FR-027**: Sellers SHALL be able to update order activity status (dispatched to hub, at delivery point).
- **FR-028**: Grocery stores SHALL be able to mark orders ready for pickup.
- **FR-029**: Customers SHALL receive pickup code and notification when order is ready.

### 5.4 ELURC Payment (Non-Custodial)

- **FR-030**: Checkout SHALL allow ELURC-only payment for MVP.
- **FR-031**: Checkout SHALL support Phantom as first wallet option for verified members.
- **FR-032**: Platform SHALL NOT custody private keys or sign user transactions.
- **FR-033**: Payment flow SHALL verify transaction outcome before order completion.
- **FR-034**: Payment record SHALL store token address, wallet address, tx hash, amount, and network.
- **FR-035**: If payment provider is not ELURC, checkout SHALL fail with explicit error.

### 5.5 Auditability and Admin

- **FR-040**: System SHALL log product lifecycle transitions (created, published, suspended, unpublished).
- **FR-041**: System SHALL log order routing transitions (dispatched, at hub, ready, completed).
- **FR-042**: System SHALL log DAO moderation actions with actor, timestamp, action, and rationale.
- **FR-043**: System SHALL expose operational summaries for products, orders, delivery points, issues, moderation actions, and payment outcomes.
- **FR-044**: System SHALL provide Network-wide order visibility for platform operators.
- **FR-045**: System SHALL track delivery point performance metrics (orders per hub, processing time).

## 6) Non-Functional Requirements

### 6.1 Security

- **NFR-001**: Enforce role-based authorization on all custom APIs.
- **NFR-002**: Enforce server-side ownership checks for seller-scoped resources.
- **NFR-003**: Keep secrets server-side only; avoid exposing sensitive credentials in public runtime variables.
- **NFR-004**: Include request-level audit metadata for governance and payment operations.
- **NFR-005**: Membership verification checks MUST be server-side enforced for protected routes.

### 6.2 Reliability

- **NFR-010**: Payment verification path MUST be idempotent.
- **NFR-011**: Listing publish and moderation workflows MUST be retry-safe.
- **NFR-012**: API responses for moderation actions SHOULD return deterministic state transitions.

### 6.3 Performance

- **NFR-020**: P95 response time <= 600ms for primary read/list APIs under MVP load.
- **NFR-021**: Listing and order views SHOULD paginate and filter efficiently.

### 6.4 Observability

- **NFR-030**: Correlate logs by member_id, product_id, store_id, place_id, issue_id, order_id, fleetops_order_id, payment_session_id.
- **NFR-031**: Emit structured events for product published/updated/suspended, order routing status changes, delivery point notifications, and payment confirmed/failed.
- **NFR-032**: Track order routing metrics (time at each leg, delivery hub utilization).
- **NFR-033**: Use Laravel's logging system with structured context.
- **NFR-034**: Integrate with monitoring tools (Laravel Telescope for dev, Sentry for production).

## 7) API/Workflow Requirements (Fleetbase-focused)

### 7.1 Native Fleetbase Configuration

- **Network Setup**: Create "Stalabard DAO Marketplace" Network in Fleetbase Console.
  - Configure network currency and invitation settings.
  - Generate Network Key for Storefront App.
- **FleetOps Places**: Configure grocery stores as delivery point Places.
  - Tag: `type: 'delivery_hub'`, `subtype: 'grocery_store'`.
  - Set operating hours and service areas.
- **FleetOps Order Config**: Create custom 3-leg activity flow.
  - Statuses: created → dispatched_to_hub → at_delivery_point → ready_for_pickup → completed.
  - Custom fields: grocery_store_place_uuid, pickup_code, seller_store_uuid.

### 7.2 Custom Extension Requirements

- Build **Membership Extension** for member identity verification (NFT badge ownership checks).
  - Integrates with Fleetbase's IAM system for role-based access control.
  - Provides middleware for membership verification on protected routes.
  - Manages Network store creation for verified sellers.
- Build **DAO Governance Extension** for product governance, issues, and moderation actions.
  - Extends native Storefront Product model with ProductGovernance metadata.
  - Integrates with core Product/Order models from Storefront.
  - Enforces store-scoped access control.
- Build **ELURC Payment Provider Extension** for blockchain payments.
  - Extends Fleetbase's payment gateway system.
  - Integrates with order routing initialization.
- Build **Order Routing Integration** for FleetOps.
  - Creates FleetOps orders on Storefront order completion.
  - Manages 3-leg activity flow updates.
  - Sends notifications to seller/hub/customer.

### 7.3 Workflow and Hook Requirements

- Middleware to verify membership before protected API actions.
- Workflow to create seller Store within Network for verified members.
- API workflow to create and publish product (native Storefront + governance metadata).
- API workflow to report product issue.
- API workflow to perform DAO moderation action on product issue.
- Workflow to create FleetOps order on Storefront checkout with delivery point selection.
- Workflow to update FleetOps order activity (seller dispatch, hub arrival, ready for pickup).
- Payment provider hook to enforce ELURC-only checkout.
- Webhook/event listener to verify non-custodial payment transaction before order completion.
- Event hooks for product lifecycle (published, suspended, flagged).
- Event hooks for order routing (dispatched to hub, at delivery point, ready for pickup, completed).
- Notification hooks for seller/grocery store/customer at each order leg.

### 7.4 API Route Requirements

- **Membership Routes**:
  - `POST /membership/v1/verify` - Verify NFT badge ownership.
  - `GET /membership/v1/status` - Check membership status.
  - `POST /membership/v1/store/create` - Create seller store in Network.
- **Product Routes** (Native Storefront + Governance):
  - `POST /storefront/v1/products` - Create product (members only, with governance metadata).
  - `PATCH /storefront/v1/products/:id` - Update own product.
  - `POST /storefront/v1/products/:id/publish` - Publish product directly.
  - `GET /storefront/v1/products/mine` - List own products (store-scoped).
  - `GET /storefront/v1/products` - Browse published products (network-wide, governance-filtered).
  - `POST /storefront/v1/products/:id/issues` - Report product issue.
- **Order & Delivery Routes**:
  - `POST /storefront/v1/checkout` - Create checkout with delivery point selection.
  - `GET /storefront/v1/orders/mine` - List own orders.
  - `GET /storefront/v1/orders/:id` - Get order details with delivery status.
  - `PATCH /storefront/v1/orders/:id/activity` - Update order activity (seller dispatch).
  - `GET /storefront/v1/places` - List available delivery points.
  - `GET /storefront/v1/places/:id` - Get delivery point details.
- **Moderation Routes** (Internal):
  - `GET /int/v1/moderation/issues` - List flagged product issues.
  - `POST /int/v1/moderation/issues/:id/actions` - Apply moderation action.
  - `GET /int/v1/network` - View network details and stores.
  - `POST /int/v1/places` - Create/manage delivery points.
- **Payment Routes**:
  - `POST /payments/v1/elurc/intent` - Create ELURC payment intent.
  - `POST /payments/v1/verify` - Submit tx_hash for verification.
  - `GET /payments/v1/status/:order_id` - Check payment/checkout status.

## 8) MVP Scope and Release

### 8.1 In Scope (MVP)

- **Network Architecture**: Multi-vendor marketplace using Fleetbase Networks.
- **Members-only access**: Based on DAO NFT badge ownership.
- **Seller Stores**: Verified members can create stores within the Network.
- **Native Products**: Direct product creation/publication using Fleetbase Storefront (extended with governance).
- **3-Leg Delivery**: Orders route through grocery store delivery points (FleetOps Places).
- **Order Routing**: Seller → Delivery Hub → Customer Pickup workflow.
- **DAO intervention**: Reactive governance for flagged product issues only.
- **Seller ownership scoping**: Store-scoped access (own store's products/orders only).
- **Phantom-first non-custodial checkout**: ELURC-only payments.
- **ELURC Payment Provider**: Custom blockchain payment integration.
- **Audit/event logging**: Product, order routing, payment, and moderation events.
- **Custom Extensions**: Membership, DAO Governance, ELURC Payment, Order Routing.
- **Integration**: Fleetbase core API, Storefront extension, FleetOps extension.

### 8.2 Out of Scope (MVP)

- Additional wallet providers beyond Phantom.
- Multi-token or fiat rails.
- Driver assignment and routing optimization (sellers self-deliver to hubs).
- Pallet extension integration for inventory management across hubs.
- Full dispute/returns management automation.
- Advanced recommendation/personalization.
- Real-time delivery tracking (available post-MVP via Navigator App).

### 8.3 Release Readiness Criteria

- **End-to-end 3-leg delivery scenario passes**:
  1. Membership verification (NFT badge check)
  2. Seller creates store in Network
  3. Product creation/publication (Storefront + governance)
  4. Buyer selects delivery point during checkout
  5. ELURC payment via Phantom
  6. Order routing initialized (FleetOps order created)
  7. Seller dispatches to delivery hub
  8. Hub confirms receipt and marks ready
  9. Customer picks up with code
  10. Order completion logged
- **Store ownership-scope test suite passes** (sellers cannot access other stores' resources).
- **Members-only access test suite passes** (non-members denied).
- **DAO governance test suite passes** (product flagging, moderation actions).
- **ELURC-only enforcement tests pass** (other payment methods rejected).
- **Audit/event trail available** for products, order routing, payments, and moderation.
- **Delivery point configuration verified** (grocery stores as Places with operating hours).

## 9) Acceptance Criteria (System Level)

1. **Membership**: Given a non-member wallet, when attempting platform actions, then access is denied.
2. **Store Creation**: Given a verified member, when requesting to become seller, then system creates Store within Network and grants Console access.
3. **Product Publication**: Given a seller with Store, when creating and publishing product, then product is visible to all verified members across Network (governance-filtered).
4. **Store Scoping**: Given seller A, when requesting seller B's store products/orders, then API returns forbidden.
5. **Delivery Point Selection**: Given a buyer at checkout, when browsing delivery points, then only active grocery store Places with operating hours are shown.
6. **3-Leg Order Flow**: Given a completed ELURC payment, when order is created, then FleetOps order is initialized with seller → delivery hub → customer routing.
7. **Order Routing**: Given seller receives order, when seller dispatches to hub, then grocery store is notified and order status updates to "At Delivery Point".
8. **Customer Pickup**: Given order ready at hub, when customer arrives with pickup code, then grocery store confirms handoff and order completes.
9. **DAO Moderation**: Given a flagged product issue, when DAO moderator applies action, then product status updates and audit trail includes rationale.
10. **Payment Verification**: Given a verified member buyer, when ELURC payment is confirmed on-chain, then order completes and PaymentIntentMetadata is persisted with tx_hash.
11. **Audit Trail**: Given moderation action, when decision is recorded, then audit history includes actor, timestamp, previous status, new status, and rationale.

## 10) Risks and Mitigations

- **Risk**: Membership verification latency or reliability issues.
  - **Mitigation**: deterministic verification step with clear failure modes and retries.
- **Risk**: Harmful listings remain live before moderation.
  - **Mitigation**: issue reporting, rapid moderator intervention tools, and clear policy rules.
- **Risk**: Ownership leaks through custom query paths.
  - **Mitigation**: centralized ownership guard and integration tests.
- **Risk**: On-chain confirmation delays degrade UX.
  - **Mitigation**: explicit pending state and retry-safe status polling.

## 11) Open Decisions (to finalize during architecture)

1. Exact membership verification mechanism and cache policy for NFT ownership checks.
2. Intervention policy model (manual moderation only vs configurable policy automation later).
3. Exact payment verification strategy (RPC confirmation depth, timeout, and reconciliation behavior).
4. Delivery hub capacity management (max concurrent orders per grocery store).
5. Order timeout policies (max time at each routing leg before escalation).
6. Grocery store onboarding and training process for order handling.

## 12) Traceability Matrix

- **Members-only access** → FR-001, FR-002, FR-005, NFR-005, Acceptance #1
- **Network multi-vendor** → Product Context 1.1, Domain Model 4.1 (Network/Store)
- **Every member can sell and buy** → FR-003, Journey A (Seller), Journey C (Buyer)
- **Store ownership scoping** → FR-004, FR-021, Acceptance #4
- **Native Products with governance** → FR-010..FR-014, Domain Model 4.2 (ProductGovernance)
- **3-Leg delivery workflow** → FR-023..FR-029, Journey D, Acceptance #6-#8
- **Delivery point selection** → FR-023, FR-024, Acceptance #5
- **DAO moderation** → FR-012..FR-014, FR-042, Acceptance #9
- **ELURC-only + Phantom non-custodial** → FR-030..FR-035, NFR-010, Acceptance #10
- **Order routing audit** → FR-041, FR-044, FR-045, NFR-030..NFR-032
